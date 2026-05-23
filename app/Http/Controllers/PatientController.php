<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientClinical;
use App\Models\Insurance;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class PatientController extends Controller
{
    /* /
    //Patient related functions     /
    //                              /
    */                             //
    /**
     * Display a paginated and searchable list of patients.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Nurses have a dedicated view — redirect them
        if (Auth::user()->is_nurse) {
            return redirect()->route('nurse.patients.index', $request->all());
        }

        // First, check if the search query looks like a patient ID (a number)
        if ($request->has('q') && is_numeric($request->input('q'))) {
            return redirect()->route('patients.show', ['patient_id' => $request->input('q')]);
        }

        // Define the number of records to show per page
        $perPage = 20;

        // Start with a base query on the Patient model
        $query = Patient::query();

        // Check for a search query and apply it
        if ($request->has('q')) {
            $searchQuery = $request->input('q');
            $query->where('name', 'like', '%' . $searchQuery . '%')
                  ->orWhere('patient_id', 'like', '%' . $searchQuery . '%')
                  ->orWhere('acc_no', 'like', '%' . $searchQuery . '%');
        }

        // Apply sorting based on user request, with a default
        $sortBy = $request->input('sort_by', 'patient_id');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validate sort columns to prevent SQL injection
        if (!in_array($sortBy, ['patient_id', 'name', 'acc_no', 'tel', 'active'])) {
            $sortBy = 'patient_id';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Paginate the results
        $patients = $query->paginate($perPage);

        // Fetch recent patients for the left sidebar (as per previous logic)
        
        $lastUpdatedPatients = PatientClinical::select(
            'patients.patient_id',
            'patients.name',
            'patients.tel',
            'patients_clinical.time_stamp'
        )
            ->join('patients', 'patients_clinical.patient_id', '=', 'patients.patient_id')
            ->where('patients_clinical.time_stamp', '>=', Carbon::now()->subDays(4))
            ->orderBy('patients_clinical.time_stamp', 'desc')
            ->get();
        
        // dd($recentPatients); // <-- The first test, which worked.


       

        return view('patients.index', compact('patients', 'lastUpdatedPatients'));

        
    }

    /**
     * Handle the patient search and display records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    
    /**
     * Display the specified patient record.
     */
    

    public function show(Request $request, $patient_id)
    {
        // Nurses have a dedicated clinical-free view — redirect them
        if (Auth::user()->is_nurse) {
            return redirect()->route('nurse.patients.show', ['patient_id' => $patient_id]);
        }

        $patient = Patient::where('patient_id', $patient_id)->first();

        if (!$patient) {
        return redirect()->route('patients.index');
        }

        // 💡 Use the Eloquent relationship for cleaner retrieval
        $insuranceProvider = $patient->insurance; 

        $showAllRecords = ($request->query('showAllRecords') === 'true');


        $allRecords = PatientClinical::where('patient_id', $patient->patient_id)
        ->orderBy('date', 'desc')
        ->get();

        return view('patients.show', compact('patient', 'allRecords', 'insuranceProvider', 'showAllRecords'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit($id, Request $request)
    {
        $showAllRecords = $request->query('showAllRecords') === 'true';

        // We need to find the single record to be edited.
        // We look for it in the PatientClinical model as both record types (clinical and estimate)
        // are stored in the same `patients_clinical` table.
        $record = PatientClinical::where('patient_clinic_id', $id)->firstOrFail();

        // Pass the patient and record data to the view.
        $patient = Patient::where('patient_id', $record->patient_id)->firstOrFail();

        // Determine the record type for the view logic
        // We'll check for a non-empty `estimate` field or other estimate fields
        $isEstimate = (
            !empty($record->estimate) ||
            !empty($record->estimate_description) ||
            !empty($record->estimate_cost) ||
            !empty($record->estimate_paid) ||
            !empty($record->estimate_balance)
        );

        $isClinical = !$isEstimate;
        // If $isEstimate is TRUE, then $isClinical becomes FALSE.
        // If $isEstimate is FALSE, then $isClinical becomes TRUE.

        // Pass the patient's active insurance record so the edit view can show the "Bill to Insurance" option.
        $activeInsurance = Insurance::where('ver_patient_id', $patient->patient_id)
            ->where('policy_status', 1)
            ->first();

        return view('patients.edit', compact('patient', 'record', 'isClinical', 'showAllRecords', 'activeInsurance'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRecord(Request $request, $id)
    {
        $record = PatientClinical::findOrFail($id);

        // Get all fields from the request except form-specific and insurance fields
        // (insurance fields are handled explicitly below to prevent form manipulation)
        $dataToUpdate = $request->except(['_token', '_method', 'record_type', 'is_insurance_claim', 'insurance_provider_id']);

        // Handle insurance claim flag server-side
        $claimRequested = $request->boolean('is_insurance_claim');

        if ($claimRequested) {
            // Derive the provider from the patient's currently active insurance — never from the form
            $activeInsurance = Insurance::where('ver_patient_id', $record->patient_id)
                ->where('policy_status', 1)
                ->first();

            if ($activeInsurance) {
                $dataToUpdate['is_insurance_claim']    = 1;
                $dataToUpdate['insurance_provider_id'] = $activeInsurance->provider_id;
            }
        } else {
            $dataToUpdate['is_insurance_claim']    = 0;
            $dataToUpdate['insurance_provider_id'] = null;
        }

        $record->update($dataToUpdate);

        return redirect()->route('patients.show', [
            'patient_id' => $record->patient_id,
            'showAllRecords' => $request->query('showAllRecords')
        ])->with('success', 'Record updated successfully!');
    }
    /**
     * Show the form for creating a new patient clinical record.
     */
    public function create($patient_id)
    {
        $patient = Patient::where('patient_id', $patient_id)->firstOrFail();
        return view('patients.create', compact('patient'));
    }

    /**
     * Store a newly created patient clinical record in storage.
     */
    public function store(Request $request, $patient_id)
    {
        $patient = Patient::where('patient_id', $patient_id)->firstOrFail();
        
        // Resolve insurance claim flag for new clinical records
        $isInsuranceClaim = 0;
        $insuranceProviderId = null;

        if ($request->boolean('is_insurance_claim')) {
            $activeInsurance = Insurance::where('ver_patient_id', $patient->patient_id)
                ->where('policy_status', 1)
                ->first();

            if ($activeInsurance) {
                $isInsuranceClaim    = 1;
                $insuranceProviderId = $activeInsurance->provider_id;
            }
        }

        PatientClinical::create([
            'patient_id' => $patient->patient_id,
            'patient_id_ver' => $patient->patient_id,
            'acc_no' => $patient->acc_no,
            'date' => $request->date,
            'diagnostic' => $request->diagnostic,
            'description' => $request->description ?? '',
            'tooth' => $request->tooth ?? '',
            'amount' => $request->amount ?? '',
            'paid' => $request->paid ?? '',
            'balance' => $request->balance ?? '',
            'estimate_description' => $request->estimate_description ?? '',
            'estimate' => $request->estimate ?? '',
            'estimate_cost' => $request->estimate_cost ?? '',
            'estimate_paid' => $request->estimate_paid ?? '',
            'estimate_balance' => $request->estimate_balance ?? '',
            'remarks' => $request->remarks ?? '',
            'time_stamp' => Carbon::now(),
            'is_insurance_claim'    => $isInsuranceClaim,
            'insurance_provider_id' => $insuranceProviderId,
        ]);

        return redirect()->route('patients.show', ['patient_id' => $patient->patient_id])->with('success', 'Record added successfully.');
    }
    
    
    // app/Http/Controllers/PatientController.php

    public function register()
    {
        // Fetch all active providers from your new lookup table
        // We use 'orderBy' so they appear alphabetically, like a civilized list.
        $providers = DB::table('insurance_providers')
                        ->where('active', 1)
                        ->orderBy('provider_name', 'asc')
                        ->get();

        // Pass the providers to the view
        return view('patients.register', compact('providers'));
    }

    // app/Http/Controllers/PatientController.php
    public function saveNewPatient(Request $request)
    {
        // 1. Separate Validation Rules for Patient and Insurance data
        $patientValidationRules = [
            'name'            => 'required|string|max:255',
            'date_of_birth'   => 'nullable|date_format:d/m/Y',
            'gender'          => 'nullable|string|max:50',
            // TARGET the correct input/column name and validate its format
            //'opened' => 'required|date_format:Y-m-d', // Assuming the registration date is required
    
            'location'        => 'nullable|string|max:255',
            'pobox'           => 'nullable|string|max:20',
            'town'            => 'nullable|string|max:100',
            'tel'             => 'nullable|string|max:50',
            'active'          => 'required|integer|in:1,2', // Accepts only integer 1 (Arusha) or 2 (Dar es Salaam)
            'email'           => 'nullable|email|max:255',
            'occupation'      => 'nullable|string|max:255',
            'remarks'         => 'nullable|string',
        ];

        $insuranceValidationRules = [
            'insurance_no' => 'nullable|integer',
            //'insurance_serial' => 'nullable|string|max:255',
            //'insurance_provider' => 'nullable|string|max:255',
            'insurance_provider' => 'nullable|exists:insurance_providers,provider_name', // Validates against the lookup table
            'insurance_remarks' => 'nullable|string',
            'insurance_id_no' => 'nullable|string|max:255',
        ];

        $validatedPatientData = $request->validate($patientValidationRules);
        $validatedInsuranceData = $request->validate($insuranceValidationRules);

        // Check if the current date is added to the data array
        //$validatedPatientData['opened'] = \Carbon\Carbon::now()->format('Y-m-d'); 

        DB::beginTransaction();
            try {
                // 1. Save the Patient (This step remains the same)
                $validatedPatientData['opened'] = now()->toDateString();
                $patient = Patient::create($validatedPatientData);

                // 2. Conditional Check for Insurance Data (THE KEY CHANGE)
                // We check if the most important identifying field(s) were provided.
                if (!empty($validatedInsuranceData['insurance_no']) || !empty($validatedInsuranceData['insurance_provider'])) {
                    
                    // Retrieve the Patient's acc_no (assuming it's available after creation, 
                    // either auto-generated or calculated in Patient::create)
                    $patient_acc_no = $patient->acc_no ?? null; 
                    
                    // Save the Insurance Record ONLY IF there is actual data
                    $patient->insurance()->create([
                        'ver_patient_id' => $patient->patient_id,
                        'ver_acc_no' => $patient_acc_no, // Ensure this field is set if needed
                        'insurance_no' => $validatedInsuranceData['insurance_no'] ?? null,
                        //'insurance_serial' => $validatedInsuranceData['insurance_serial'] ?? null,
                        'insurance_provider' => $validatedInsuranceData['insurance_provider'] ?? null,
                        'insurance_remarks' => $validatedInsuranceData['insurance_remarks'] ?? null,
                        'insurance_id_no' => $validatedInsuranceData['insurance_id_no'] ?? null,
                    ]);
                    
                    $successMessage = 'New Patient and Insurance records created successfully.';
                } else {
                    // No insurance data provided, so we skip creating the Insurance record.
                    $successMessage = 'New Patient record created successfully (No insurance details provided).';
                }

                DB::commit();

                // Redirect nurse to their dedicated view
                if (Auth::user()->is_nurse) {
                    return redirect()->route('nurse.patients.show', ['patient_id' => $patient->patient_id])
                                    ->with('success', $successMessage);
                }

                // 3. Redirect
                return redirect()->route('patients.show', ['patient_id' => $patient->patient_id])
                                ->with('success', $successMessage);
            } catch (\Exception $e) {
                    DB::rollBack();

                    // 🚨 TEMPORARY: Log the error for debugging (RECOMMENDED)
                    \Log::error("Patient Registration Failed: " . $e->getMessage());
                    
                    // 🚨 TEMPORARY: Display the error message to the screen (QUICKEST WAY TO FIND OUT)
                    // Replace the generic return statement with this:
                    // return redirect()->back()->withInput()->with('error', 'Registration Failed: ' . $e->getMessage()); 
                    
                    // Let's use the quick display method for now:
                    return redirect()->back()
                                    ->withInput()
                                    ->with('error', 'Registration Failed: ' . $e->getMessage());
                }
    }

    

    /**
     * Update or create the insurance details for the specified patient.
     */
   






    // A new method to show all records after a key check
    public function showAllRecords(Request $request, $patient_id)
    {
        $accessKey = $request->input('access_key');
        $secureKey = config('app.all_records_key');
        
        // Perform the security check on the server
        if ($accessKey === $secureKey) {
            // Find the patient and load all records
            $patient = Patient::findOrFail($patient_id);
            $allRecords = PatientClinical::where('patient_id', $patient_id)->orderBy('date', 'desc')->get();

            // Add this line to fetch the insurance provider
            $insuranceProvider = Insurance::where('ver_patient_id', $patient_id)->first();
        
            // Add this variable to mark the tab as active
            $showAllRecords = true;
            
            // Pass all the necessary variables to the view
            return view('patients.show', compact('patient', 'allRecords', 'insuranceProvider', 'showAllRecords'));
     }

        // If the key is incorrect, redirect back with an error
        return redirect()->route('patients.show', ['patient_id' => $patient_id])
                        ->with('error', 'Incorrect key. Access denied.');
    }


    /**
     * Display the specified patient record for editing.
     * Laravel automatically resolves the Patient based on the ID in the route.
     * The variable name here MUST match the route placeholder: {patient}
     */
    public function editPatient(Patient $patient)
    {
        // $patient is now the fully loaded Patient model instance (fetched by patient_id).
        
        // Fetch the insurance record using the relationship. 
        // This is necessary for pre-filling the form and providing the insurance_id 
        // for the updatePatient method.
        // This answers your question directly: yes, it retrieves the record if it exists!
        $insuranceRecord = $patient->insurance;

        // Fetch the list of providers for the dropdown
        $providers = DB::table('insurance_providers')
                        ->where('active', 1)
                        ->orderBy('provider_name', 'asc')
                        ->get();
        
        // Pass the patient data and the insurance record (which might be null if none exists) to the view
        //return view('patients.patient_details', compact('patient', 'insuranceRecord'));
        return view('patients.patient_details', compact('patient', 'insuranceRecord', 'providers'));
    }

    public function updatePatient(Request $request, Patient $patient)
    {
        // 1. Validation Rules (Added 'delete' to the action enum)
        $rules = [
            'name'               => 'required|string|max:255',
            'gender'             => 'nullable|in:M,F',
            'location'           => 'nullable|string|max:255',
            'pobox'              => 'nullable|string|max:50',
            'town'               => 'nullable|string|max:255',
            'tel'                => 'nullable|string|max:50',
            'active'             => 'required|in:1,2', 
            'email'              => 'nullable|email|max:255',
            'occupation'         => 'nullable|string|max:255',
            'remarks'            => 'nullable|string',
            'date_of_birth'      => 'nullable|date_format:Y-m-d', 
            
            // Insurance fields
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_no'       => 'nullable|integer',
            'insurance_id_no'    => 'nullable|string|max:255',
            'insurance_remarks'  => 'nullable|string',
            'insurance_action'   => 'nullable|in:create,update,delete', // Added delete
            'insurance_id'       => 'nullable|numeric',
        ];

        $validatedData = $request->validate($rules);
        
        // 2. Separate Data
        $patientData = $request->only(['name', 'gender', 'location', 'pobox', 'town', 'tel', 'active', 'email', 'occupation', 'remarks', 'date_of_birth']);
        $insuranceData = $request->only(['insurance_provider', 'insurance_no', 'insurance_id_no', 'insurance_remarks']);
        
        $insuranceAction = $request->input('insurance_action');
        $insuranceID = $request->input('insurance_id');

        // NEW LOGIC: If we have an ID but no action was sent (checkbox wasn't ticked), 
        // it's an update by default.
        if (!$insuranceAction && $insuranceID) {
            $insuranceAction = 'update';
        }
        
        $successMessage = 'Patient record updated successfully.';

        DB::beginTransaction();

        try {
            // 3. Update Patient
            $patient->update($patientData);

            // 4. Handle Insurance (The "Precise" Part)
            if ($insuranceAction === 'delete' && $insuranceID) {
                // CASE: DELETE
                Insurance::destroy($insuranceID);
                $successMessage = 'Patient updated and insurance record removed.';

            } elseif ($insuranceAction === 'update' && $insuranceID) {
                // CASE: UPDATE
                $insuranceRecord = Insurance::find($insuranceID);
                if ($insuranceRecord) {
                    // Keep provider_id in sync if the provider name changed
                    $providerRow = DB::table('insurance_providers')
                        ->where('provider_name', $insuranceData['insurance_provider'])
                        ->first();
                    $insuranceRecord->update(array_merge($insuranceData, [
                        'provider_id' => $providerRow ? $providerRow->id : $insuranceRecord->provider_id,
                    ]));
                    $successMessage = 'Patient and insurance details updated.';
                }

            } elseif ($insuranceAction === 'create' && !empty(array_filter($insuranceData))) {
                // CASE: CREATE — resolve provider_id from the providers lookup table
                $providerRow = DB::table('insurance_providers')
                    ->where('provider_name', $insuranceData['insurance_provider'])
                    ->first();

                $patient->insurance()->create(array_merge($insuranceData, [
                    'ver_patient_id' => $patient->patient_id,
                    'ver_acc_no'     => $patient->acc_no,
                    'provider_id'    => $providerRow ? $providerRow->id : 0,
                    'policy_status'  => 1,
                ]));
                $successMessage = 'Patient updated and new insurance policy added.';
            }

            DB::commit();
            return redirect()->route('patient.edit', $patient)->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Update Failed: " . $e->getMessage()); 
            return redirect()->back()->withInput()->with('error', 'Update Failed! Check logs.');
        }
    }

    // --- PARTNER CLINIC METHODS (SANDBOX) ---

    public function partnerSearch()
    {
        return view('patients.partner_search');
    }

    public function partnerResults(Request $request)
    {
        $q = $request->input('q');

        if (empty($q)) {
            return view('patients.partner_results', ['remotePatients' => collect([])]);
        }

        $query = Patient::on('partner');

        if (is_numeric($q)) {
            $query->where('patient_id', $q);
        } else {
            $query->where('name', 'LIKE', '%' . $q . '%');
        }

        $remotePatients = $query->get();
        return view('patients.partner_results', compact('remotePatients'));
    }

    /**
     * Display a READ-ONLY view of a partner record.
     * Points to show_partner.blade.php for safe theme experimentation.
     */
    public function partnerShow(Request $request, $patient_id)
    {
        // Use Eloquent with the partner connection so encrypted fields decrypt transparently
        $patient = Patient::on('partner')->where('patient_id', $patient_id)->first();

        if (!$patient) {
            return redirect()->route('patients.partner.search')
                             ->with('error', 'Patient not found in partner clinic.');
        }

        $allRecords = PatientClinical::on('partner')
            ->where('patient_id', $patient_id)
            ->orderBy('date', 'desc')
            ->get();

        $insuranceProvider = Insurance::on('partner')
            ->where('ver_patient_id', $patient_id)
            ->first();

        $showAllRecords = ($request->query('showAllRecords') === 'true');

        return view('patients.show_partner', compact('patient', 'allRecords', 'insuranceProvider', 'showAllRecords'));
    }

    public function partnerShowAllRecords(Request $request, $patient_id)
    {
        $accessKey = $request->input('access_key');
        $secureKey = config('app.all_records_key');

        if ($accessKey !== $secureKey) {
            return redirect()->route('patients.partner.show', ['patient_id' => $patient_id])
                             ->with('error', 'Incorrect key. Access denied.');
        }

        $patient = Patient::on('partner')->where('patient_id', $patient_id)->first();

        if (!$patient) {
            return redirect()->route('patients.partner.search')
                             ->with('error', 'Patient not found in partner clinic.');
        }

        $allRecords = PatientClinical::on('partner')
            ->where('patient_id', $patient_id)
            ->orderBy('date', 'desc')
            ->get();

        $insuranceProvider = Insurance::on('partner')
            ->where('ver_patient_id', $patient_id)
            ->first();

        $showAllRecords = true;

        return view('patients.show_partner', compact('patient', 'allRecords', 'insuranceProvider', 'showAllRecords'));
    }

    /**
     * Step 1: Check for similar local patients before importing.
     * If a ≥75% name match is found, redirect to the confirmation page.
     */
    public function importPatient(Request $request, $remotePatientId)
    {
        $remotePatient = DB::connection('partner')
            ->table('patients')
            ->where('patient_id', $remotePatientId)
            ->first();

        if (!$remotePatient) {
            return back()->with('error', 'Patient record not found in the partner clinic.');
        }

        // Similarity check against all local patient names (name is not encrypted)
        $remoteName     = strtolower(trim($remotePatient->name));
        $localPatients  = Patient::select('patient_id', 'name')->get();
        $similarPatients = [];

        foreach ($localPatients as $local) {
            similar_text($remoteName, strtolower(trim($local->name)), $percent);
            if ($percent >= 75) {
                $similarPatients[] = [
                    'patient_id' => $local->patient_id,
                    'name'       => $local->name,
                    'similarity' => round($percent),
                ];
            }
        }

        // Sort by similarity descending
        usort($similarPatients, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        if (!empty($similarPatients)) {
            // Store matches in session and redirect to confirmation page
            session(['import_similar_patients' => $similarPatients]);
            return redirect()->route('patients.partner.import.confirm', ['patient_id' => $remotePatientId]);
        }

        // No similar patient — import directly
        $patient = $this->doImport($remotePatient);

        return redirect()->route('patients.show', ['patient_id' => $patient->patient_id])
            ->with('success', 'Patient imported successfully from partner clinic.');
    }

    /**
     * Step 2: Show the confirmation page when similar patients were found.
     */
    public function importConfirm(Request $request, $remotePatientId)
    {
        $similarPatients = session('import_similar_patients');

        if (empty($similarPatients)) {
            // Session expired or accessed directly — go back
            return redirect()->route('patients.partner.search')
                ->with('error', 'Import session expired. Please try again.');
        }

        $remotePatient = DB::connection('partner')
            ->table('patients')
            ->where('patient_id', $remotePatientId)
            ->first();

        if (!$remotePatient) {
            return redirect()->route('patients.partner.search')
                ->with('error', 'Partner patient record no longer found.');
        }

        return view('patients.partner_import_confirm', compact('remotePatient', 'remotePatientId', 'similarPatients'));
    }

    /**
     * Step 3: Force-import despite similarity — flagged in the audit log.
     */
    public function importForce(Request $request, $remotePatientId)
    {
        $similarPatients = session('import_similar_patients', []);

        $remotePatient = DB::connection('partner')
            ->table('patients')
            ->where('patient_id', $remotePatientId)
            ->first();

        if (!$remotePatient) {
            return redirect()->route('patients.partner.search')
                ->with('error', 'Partner patient record no longer found.');
        }

        // Build the flag reason
        $flagParts = array_map(
            fn ($s) => "{$s['similarity']}% match with local Patient #{$s['patient_id']} ({$s['name']})",
            $similarPatients
        );
        $flagReason = 'Imported by ' . (Auth::user()->name ?? 'unknown')
            . ' despite similar existing records — '
            . implode('; ', $flagParts);

        $patient = $this->doImport($remotePatient, flagged: true, flagReason: $flagReason);

        session()->forget('import_similar_patients');

        return redirect()->route('patients.show', ['patient_id' => $patient->patient_id])
            ->with('warning', 'Patient imported and flagged in the audit log — a similar record already exists.');
    }

    /**
     * Shared import logic: decrypt partner fields, create via Eloquent, optionally flag audit entry.
     */
    private function doImport(object $remotePatient, bool $flagged = false, string $flagReason = ''): Patient
    {
        // Fields encrypted with the shared APP_KEY — decrypt before passing to Eloquent
        $encryptedFields = ['date_of_birth', 'tel', 'email', 'gender', 'location', 'pobox', 'town', 'occupation', 'remarks'];

        $data = [
            'name'   => $remotePatient->name,
            'active' => config('app.clinic_id'),
            'opened' => now()->toDateString(),
        ];

        foreach ($encryptedFields as $field) {
            $raw = $remotePatient->$field ?? null;
            if ($raw !== null && $raw !== '' && str_starts_with($raw, 'eyJ')) {
                try {
                    $data[$field] = Crypt::decryptString($raw);
                } catch (\Exception) {
                    $data[$field] = null;
                }
            } else {
                $data[$field] = ($raw === '') ? null : $raw;
            }
        }

        // Patient::create triggers the PatientObserver → audit log entry written
        $patient = Patient::create($data);

        if ($flagged) {
            // Mark the freshly written audit log entry as a red flag
            AuditLog::where('model_type', 'Patient')
                ->where('model_id', (string) $patient->patient_id)
                ->where('action', 'created')
                ->orderByDesc('created_at')
                ->first()
                ?->update(['is_flagged' => true, 'flag_reason' => $flagReason]);
        }

        return $patient;
    }

    // --- GENERAL FUNCTIONS ---

    public function dashboard()
    {
        return view('dashboard'); 
    }

} // End of PatientController
