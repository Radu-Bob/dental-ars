<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Providers\Composers\RecentPatientsComposer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Audit log observers
        \App\Models\Patient::observe(\App\Observers\PatientObserver::class);
        \App\Models\PatientClinical::observe(\App\Observers\PatientClinicalObserver::class);
        \App\Models\Insurance::observe(\App\Observers\InsuranceObserver::class);

        // 1. Share Global Variables (Theme and Clinic)
        view()->share([
            'themeColor' => config('app.theme_color'),
            'clinic_id'  => config('app.clinic_id'),
        ]);

        // 2. Specific View Composers
        View::composer('patients.index', RecentPatientsComposer::class);

        // ────────────────────────────────────────────────
        // NEW: Share $reports array with ALL reports views
        // ────────────────────────────────────────────────
        View::composer([
            'patients.reports.*',                     // main views
            'patients.reports.partials.*',            // partials inside partials/
        ], function ($view) {
            $reports = [
                ['title' => 'Patients Attending',      'route' => 'reports.patients_attending'],
                ['title' => 'Payments Ledger',         'route' => 'reports.payments_ledger'],
                ['title' => 'Estimate Report',         'route' => 'reports.estimate_report'],
                ['title' => 'Patients with Active Insurance', 'route' => 'reports.insurance'],
                ['title' => 'Patient Demographics',    'route' => 'reports.patients_demographics'],
                ['title' => 'Clinical Procedures Summary', 'route' => 'reports.clinical_summary'],
                ['title' => 'System Audit Log',            'route' => 'reports.system_audit'],
                ['title' => 'Import Red Flags',            'route' => 'reports.audit_flags'],
                // add others here
            ];

            $view->with('reports', $reports);
        });

        View::addNamespace('reports', resource_path('views/patients/reports'));
    }
}