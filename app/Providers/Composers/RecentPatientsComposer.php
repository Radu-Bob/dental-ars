<?php

namespace App\Providers\Composers;

use App\Models\Patient;
use App\Models\PatientClinical;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecentPatientsComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Define the minimum timestamp for a record to be considered 'recent'.
        // We'll use 30 days as a standard for now.
        //$thirtyDaysAgo = Carbon::now()->subDays(30);
        $threeDaysAgo = Carbon::now()->subDays(3);

        // Fetch recent patients with their latest clinical record's time_stamp.
        $recentPatients = PatientClinical::select(
                'patients.patient_id',
                'patients.name',
                'patients.tel',
                'patients_clinical.time_stamp'
            )
            ->join('patients', 'patients_clinical.patient_id', '=', 'patients.patient_id')
            ->where('patients_clinical.time_stamp', '>=', $threeDaysAgo)
            ->groupBy('patients.patient_id', 'patients.name', 'patients.tel', 'patients_clinical.time_stamp')
            ->orderBy('patients_clinical.time_stamp', 'desc')
            ->get();
            
        $view->with('recentPatients', $recentPatients);
    }
}
