<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Insurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NurseController extends Controller
{
    public function reports()
    {
        return view('nurse.reports');
    }

    public function index(Request $request)
    {
        $perPage = 20;
        $query = Patient::query();

        if ($request->has('q') && $request->input('q') !== '') {
            $searchQuery = $request->input('q');

            if (is_numeric($searchQuery)) {
                return redirect()->route('nurse.patients.show', ['patient_id' => $searchQuery]);
            }

            $query->where('name', 'like', '%' . $searchQuery . '%')
                  ->orWhere('patient_id', 'like', '%' . $searchQuery . '%')
                  ->orWhere('acc_no', 'like', '%' . $searchQuery . '%');
        }

        $sortBy = $request->input('sort_by', 'patient_id');
        $sortOrder = $request->input('sort_order', 'desc');

        if (!in_array($sortBy, ['patient_id', 'name', 'acc_no', 'tel', 'active'])) {
            $sortBy = 'patient_id';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        $query->orderBy($sortBy, $sortOrder);
        $patients = $query->paginate($perPage);

        // Recent registrations — uses Patient only, no clinical data
        $recentPatients = Patient::orderByDesc('created_at')->limit(10)->get();

        return view('nurse.index', compact('patients', 'recentPatients'));
    }

    public function show($patient_id)
    {
        $patient = Patient::where('patient_id', $patient_id)->first();

        if (!$patient) {
            return redirect()->route('nurse.patients.index')->with('error', 'Patient not found.');
        }

        $insuranceProvider = $patient->insurance;

        return view('nurse.show', compact('patient', 'insuranceProvider'));
    }

    public function edit(Patient $patient)
    {
        $insuranceRecord = $patient->insurance;

        $providers = DB::table('insurance_providers')
                        ->where('active', 1)
                        ->orderBy('provider_name', 'asc')
                        ->get();

        return view('nurse.edit', compact('patient', 'insuranceRecord', 'providers'));
    }

    public function update(Request $request, Patient $patient)
    {
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
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_no'       => 'nullable|integer',
            'insurance_id_no'    => 'nullable|string|max:255',
            'insurance_remarks'  => 'nullable|string',
            'insurance_action'   => 'nullable|in:create,update,delete',
            'insurance_id'       => 'nullable|numeric',
        ];

        $request->validate($rules);

        $patientData    = $request->only(['name', 'gender', 'location', 'pobox', 'town', 'tel', 'active', 'email', 'occupation', 'remarks', 'date_of_birth']);
        $insuranceData  = $request->only(['insurance_provider', 'insurance_no', 'insurance_id_no', 'insurance_remarks']);
        $insuranceAction = $request->input('insurance_action');
        $insuranceID    = $request->input('insurance_id');

        if (!$insuranceAction && $insuranceID) {
            $insuranceAction = 'update';
        }

        $successMessage = 'Patient record updated successfully.';

        DB::beginTransaction();

        try {
            $patient->update($patientData);

            if ($insuranceAction === 'delete' && $insuranceID) {
                Insurance::destroy($insuranceID);
                $successMessage = 'Patient updated and insurance record removed.';

            } elseif ($insuranceAction === 'update' && $insuranceID) {
                $insuranceRecord = Insurance::find($insuranceID);
                if ($insuranceRecord) {
                    $providerRow = DB::table('insurance_providers')
                        ->where('provider_name', $insuranceData['insurance_provider'])
                        ->first();
                    $insuranceRecord->update(array_merge($insuranceData, [
                        'provider_id' => $providerRow ? $providerRow->id : $insuranceRecord->provider_id,
                    ]));
                    $successMessage = 'Patient and insurance details updated.';
                }

            } elseif ($insuranceAction === 'create' && !empty(array_filter($insuranceData))) {
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
            return redirect()->route('nurse.patients.show', $patient->patient_id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Nurse patient update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Update failed. Please try again.');
        }
    }
}
