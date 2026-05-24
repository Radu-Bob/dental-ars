<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NurseController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;

// ROOT REDIRECT: Correctly redirects to dashboard if logged in, otherwise to login.
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// ===================================
// 1. UNAUTHENTICATED ROUTES
// ===================================
// Authentication Routes (MUST be outside the 'auth' middleware)
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ===================================
// 2. NURSE ROUTE GROUP
// ===================================
Route::middleware(['auth', 'role:nurse'])->prefix('nurse')->name('nurse.')->group(function () {
    Route::get('/reports',                 [NurseController::class, 'reports'])->name('reports');
    Route::get('/patients',                [NurseController::class, 'index'])->name('patients.index');
    Route::get('/patients/{patient_id}',   [NurseController::class, 'show'])->name('patients.show');
    Route::get('/patients/{patient}/edit', [NurseController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{patient}',      [NurseController::class, 'update'])->name('patients.update');
});


// ===================================
// 3. AUTHENTICATED GROUP (MAIN APPLICATION)
// ===================================
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');

    // --- HIGHLY SPECIFIC STATIC PATIENT ROUTES (MUST be first) ---
    Route::get('/patients/search', [SearchController::class, 'search'])->name('patients.search');
    Route::get('/patients/register', [PatientController::class, 'register'])->name('patients.register');
    Route::post('/patients/register', [PatientController::class, 'saveNewPatient'])->name('patients.saveNewPatient');
    Route::get('/patients/create/{patient_id}', [PatientController::class, 'create'])->name('patients.create')->middleware('role:admin,doctor');

    // --- INDEX ROUTES ---
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/reports', [PatientController::class, 'reportsIndex'])->name('reports.index')->middleware('role:admin,doctor');
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('role:admin,doctor');


    // --- USER PROFILE ROUTES ---
    // User edits own profile (static route)
    Route::get('/profile/edit', [UserController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [UserController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password.update');
    
    // Admin edits another user's profile (wildcard route - kept specific for clarity)
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('role:admin,doctor');
    Route::post('/users/{user}/update', [UserController::class, 'update'])->name('users.update')->middleware('role:admin,doctor');


    // --- PATIENT WILDCARD ROUTES (MOST GENERIC, MUST BE LAST) ---
    
    // Edit form (e.g., /patient/123/edit)
    Route::get('/patient/{patient}/edit', [PatientController::class, 'editPatient'])->name('patient.edit');
    // Update submission
    Route::put('/patient/{patient}', [PatientController::class, 'updatePatient'])->name('patient.update'); 
    
    // Route for adding records to an existing patient
    Route::post('/patients/{patient_id}', [PatientController::class, 'store'])->name('patients.store')->middleware('role:admin,doctor');

    // Route for showing all records for a patient
    Route::post('/patients/{patient_id}/all-records', [App\Http\Controllers\PatientController::class, 'showAllRecords'])->name('patients.all_records')->middleware('role:admin,doctor');
    
    // The show/detail route (e.g., /patients/123 - MUST be the absolute last)
    Route::get('/patients/{patient_id}', [PatientController::class, 'show'])->name('patients.show');

    // 1. Route for displaying the specific record edit form (records.edit)
    Route::get('/records/{record}/edit', [PatientController::class, 'edit'])->name('records.edit')->middleware('role:admin,doctor');

    // CORRECTED UPDATE ROUTE: Calls the existing 'update' method.
    Route::put('/records/{record}', [PatientController::class, 'updateRecord'])->name('records.update')->middleware('role:admin,doctor');

    // --- Insurance Routes ---
    // {patient} placeholder uses Route Model Binding to fetch the Patient by patient_id
    Route::get('/patients/{patient}/insurance/edit', [PatientController::class, 'editInsurance'])->name('insurance.edit')->middleware('role:admin,doctor');
    Route::put('/patients/{patient}/insurance', [PatientController::class, 'updateInsurance'])->name('insurance.update')->middleware('role:admin,doctor');

     // NEW REPORTING ROUTES
    //Route::get('/reports', [PatientController::class, 'reportsIndex'])->name('reports.index');
    //Route::get('/reports/insurance', [PatientController::class, 'insuranceReport'])->name('reports.insurance');


    




    // Group all report routes under the 'reports' URI prefix and the 'reports.' name prefix
    Route::middleware('role:admin,doctor')->name('reports.')->prefix('reports')->group(function () {
        
        // 1. Reports Dashboard Index (URL: /reports | Name: reports.index)
        Route::get('/', [ReportController::class, 'index'])->name('index');

        // 2. Report Links (All names are reports.____)
        //Route::get('insurance-report', [ReportController::class, 'insuranceReport'])->name('insurance_report');
        Route::get('patients-attending', [ReportController::class, 'patientsAttending'])->name('patients_attending');
        Route::get('payments-ledger', [ReportController::class, 'paymentsLedger'])->name('payments_ledger');
        Route::get('estimate-report', [ReportController::class, 'estimateReport'])->name('estimate_report');
        Route::get('patient-demographics', [ReportController::class, 'patientReport'])->name('patients_demographics');
        Route::get('clinical-summary', [ReportController::class, 'clinicalSummary'])->name('clinical_summary');

        // 3. *** NEW REPORT ROUTE (reports.insurance) ***
        // This is the link for the report you added to the dashboard list. 
        // We'll map it to the insuranceReport method for now, or you can create a dedicated method later.
        Route::get('patients-with-insurance', [ReportController::class, 'insuranceReport'])->name('insurance');
        
        // 4. Exporting to CSV (URL: /reports/insurance/export | Name: reports.insurance.export)
        // CRITICAL FIX: The path is now relative (no leading slash / and no redundant /reports)
        // CRITICAL FIX: The name is simplified to 'insurance.export' since 'reports.' is prefixed automatically.
        Route::get('insurance/export', [ReportController::class, 'exportInsuranceReport'])->name('insurance.export');

        // System Audit Log (admin only)
        Route::get('system-audit', [ReportController::class, 'systemAudit'])->name('system_audit');

        // Red-Flag Import Log (admin only)
        Route::get('audit-flags', [ReportController::class, 'auditFlags'])->name('audit_flags');

        // Treatment Report / Invoice — landing page (choice between Invoice and Report)
        Route::get('treatment-report', [ReportController::class, 'treatmentReportIndex'])->name('treatment_report');

        // Invoice form
        Route::get('invoice', [ReportController::class, 'treatmentReport'])->name('invoice');
        Route::post('invoice/preview', [ReportController::class, 'treatmentReportPreview'])->name('invoice.preview');

        // Clinical Report / Prescription form
        Route::get('clinical-report', [ReportController::class, 'clinicalReport'])->name('clinical_report');
        Route::post('clinical-report/preview', [ReportController::class, 'clinicalReportPreview'])->name('clinical_report.preview');

        // AJAX patient lookup (GET — no CSRF needed)
        Route::get('patient-search', [ReportController::class, 'patientSearch'])->name('patient_search');
        Route::get('patient-summary', [ReportController::class, 'patientSummary'])->name('patient_summary');
        Route::get('patient-records', [ReportController::class, 'patientClinicalRecords'])->name('patient_records');

    });


    // --- Partner Clinic Connection Routes ---
    Route::group(['prefix' => 'patients/partner', 'as' => 'patients.partner.', 'middleware' => 'role:admin,doctor'], function () {
        // The main landing page for partner search
        Route::get('search', [PatientController::class, 'partnerSearch'])->name('search');

        // The results page (handling the 'q' input)
        Route::get('results', [PatientController::class, 'partnerResults'])->name('results');

        // The new "Read-Only" detailed view
        Route::get('show/{patient_id}', [PatientController::class, 'partnerShow'])->name('show');

        // The POST route for the actual import action (checks for similar patients)
        Route::post('import/{patient_id}', [PatientController::class, 'importPatient'])->name('import');

        // Confirmation page shown when a similar local patient is found
        Route::get('import/{patient_id}/confirm', [PatientController::class, 'importConfirm'])->name('import.confirm');

        // Force-import despite similar patient — flagged in audit log
        Route::post('import/{patient_id}/force', [PatientController::class, 'importForce'])->name('import.force');

        // All records (incl. estimates) for a partner patient — key-gated
        Route::post('all-records/{patient_id}', [PatientController::class, 'partnerShowAllRecords'])->name('all_records');
    });

});