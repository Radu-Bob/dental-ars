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

        // Share $reports with all report views — list is defined in config/reports.php
        View::composer([
            'patients.reports.*',
            'patients.reports.partials.*',
        ], fn ($view) => $view->with('reports', config('reports')));

        View::addNamespace('reports', resource_path('views/patients/reports'));
    }
}