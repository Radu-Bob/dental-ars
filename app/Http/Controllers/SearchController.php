<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PatientClinical;
use Illuminate\Support\Facades\DB;
use App\Models\Patient;

class SearchController extends Controller
{
    /**
     * Handle the patient search request.
     * It can search by either patient_id (redirecting to show view) or by patient_name (returning results to index view).
     */
    public function search(Request $request)
    {
        $patientId = $request->input('patient_id');
        $patientName = $request->input('patient_name');

        // Check if the user is searching by patient ID.
        // If so, we redirect them to the patient's individual show page.
        if (!empty($patientId)) {
            // First, check if the patient ID exists to avoid a 404 error
            $patient = Patient::where('patient_id', $patientId)->first();

            if ($patient) {
                // If the patient exists, redirect to the show page with the correct ID.
                return redirect()->route('patients.show', ['patient_id' => $patientId]);
            } else {
                // If the patient does not exist, you can return an error message to the index view.
                return redirect()->route('patients.index')->with('error', 'Patient not found.');
            }
        }

        // If the user is searching by name, we proceed with the original search logic.
        $query = PatientClinical::select(
            'patients_clinical.patient_clinic_id',
            'patients_clinical.patient_id',
            'patients_clinical.date',
            'patients_clinical.diagnostic',
            'patients_clinical.description',
            'patients_clinical.amount',
            'patients_clinical.paid',
            'patients_clinical.balance',
            'patients.name',
            'patients.tel',
            'patients.active' // We now also need the 'active' status
        )
            ->join('patients', 'patients.patient_id', '=', 'patients_clinical.patient_id')
            ->orderBy('patients_clinical.time_stamp', 'desc')
            ->whereNotNull('patients_clinical.time_stamp');

        // Filter by name if the name is provided
        if (!empty($patientName)) {
            $query->where('patients.name', 'LIKE', '%' . $patientName . '%');
        }

        // We fetch the records here, which will be passed to the view
        $records = $query->get();

        // Return the main index view and pass the search results to it.
        return view('patients.index', compact('records'));
    }
}
