<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Patient;
use App\Models\PatientClinical;
use App\Models\Insurance;
use App\Models\AuditLog;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display the main Reports Dashboard view.
     * * @return \Illuminate\View\View
     */
    public function index()
    {
        $reports = config('reports');
        return view('reports::index', compact('reports'));
    }
    
    /**
     * Display the 'Patients Attending' Report.
     * This will correspond to the reports.patients_attending route.
     */
    public function patientsAttending()
    {
        // Logic to fetch and process attendance data
        return view('reports::patients_attending');
    }

    /**
     * Display the 'Payments Ledger' Report.
     * This will correspond to the reports.payments_ledger route.
     */
    public function paymentsLedger()
    {
        // Logic to fetch and process payment data
        return view('reports::payments_ledger');
    }

    /**
     * Display the 'Estimate Report'.
     * This will correspond to the reports.estimate_report route.
     */
    public function estimateReport()
    {
        // Logic to fetch and process estimate data
        return view('reports::estimate_ledger');
    }
    
    /**
     * Display the 'Patient Demographics' Report (Patient Report).
     * This will correspond to the reports.patient_report route.
     */
    public function patientReport()
    {
        // Logic to fetch and process patient demographic data
        return view('reports::patients_demographics');
    }

    /**
     * Display the 'Clinical Procedures Summary' Report.
     * This will correspond to the reports.clinical_summary route.
     */
    public function clinicalSummary()
    {
        // Logic to fetch and process clinical procedure data
        return view('reports::clinical_summary');
    }

    /**
     * Report function: Lists patients with insurance, ordered by latest clinical record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function insuranceReport()
    {
        $patients = Patient::select([
            'patients.patient_id',
            'patients.name',
            'patients.tel',
            'patients.active',
            'i.insurance_no as policy_number',
            DB::raw('MAX(pc.time_stamp) as latest_record_timestamp'),
            DB::raw('SUM(CASE WHEN pc.is_insurance_claim = 1 THEN CAST(pc.balance AS DECIMAL(10,2)) ELSE 0 END) as insurance_balance'),
        ])
        // Use INNER JOIN to ensure we only get patients who HAVE an insurance record
        ->join('insurance as i', 'patients.patient_id', '=', 'i.ver_patient_id')
        // Filter out the zero entries
        ->where('i.insurance_no', '!=', '0')
        // LEFT JOIN patients_clinical to find the latest record date and sum INS balances
        ->leftJoin('patients_clinical as pc', 'patients.patient_id', '=', 'pc.patient_id')
        // Group by all non-aggregated columns
        ->groupBy('patients.patient_id', 'patients.name', 'patients.tel', 'patients.active', 'i.insurance_no')
        ->orderByDesc('latest_record_timestamp')
        ->paginate(20);

        return view('reports::insurance_report', compact('patients'));
    }

    

    /**
     * Export the Insurance Report data to CSV.
     */
    public function exportInsuranceReport()
    {
        // 🚨 CRITICAL: Authorization Check
        // Ensures only authenticated users marked as 'admin' (or similar role/permission)
        // can access the report export.
        if (!Auth::check() || (!Auth::user()->is_admin && !Auth::user()->is_doctor)) {
            // If the user is not logged in or is not an admin, they receive a 403 Forbidden error.
            abort(403, 'Unauthorized action. Admin privileges are required to export this report.');
        }

        // Use a direct JOIN to filter (where i.insurance_no != '0') and select the column (i.insurance_no) in one go.
        $query = Patient::select([
                'patients.patient_id',
                'patients.name',
                'patients.tel',
                'patients.active',
                'i.insurance_no as policy_number',
                DB::raw('MAX(pc.time_stamp) as latest_record_timestamp'),
                DB::raw('SUM(CASE WHEN pc.is_insurance_claim = 1 THEN CAST(pc.balance AS DECIMAL(10,2)) ELSE 0 END) as insurance_balance'),
            ])
            ->join('insurance as i', 'patients.patient_id', '=', 'i.ver_patient_id')
            ->where('i.insurance_no', '!=', '0')
            ->leftJoin('patients_clinical as pc', 'patients.patient_id', '=', 'pc.patient_id')
            ->groupBy('patients.patient_id', 'patients.name', 'patients.tel', 'patients.active', 'i.insurance_no')
            ->orderByDesc('latest_record_timestamp');

        // Get all results without pagination
        $patients = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="insurance_report_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function() use ($patients)
        {
            // Open stream to output
            $file = fopen('php://output', 'w');
            
            // Output UTF-8 BOM to ensure proper character encoding in Excel/Calc
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Headers
            fputcsv($file, [
                'Patient ID',
                'Name',
                'Telephone',
                'Insurance No.',
                'Location',
                'INS Balance',
                'Last Visit',
            ]);

            // CSV Data Rows
            foreach ($patients as $patient) {
                $location = ($patient->active == 1) ? 'Arusha' : (($patient->active == 2) ? 'Dar es Salaam' : 'Unknown');

                fputcsv($file, [
                    $patient->patient_id,
                    $patient->name,
                    $patient->tel,
                    $patient->policy_number,
                    $location,
                    $patient->insurance_balance > 0 ? number_format($patient->insurance_balance) : '',
                    $patient->latest_record_timestamp ? Carbon::parse($patient->latest_record_timestamp)->format('d/m/Y') : 'N/A',
                ]);
            }

            fclose($file);
        };

        AuditLog::create([
            'user_id'         => Auth::id(),
            'user_name'       => Auth::user()?->name,
            'action'          => 'exported',
            'action_category' => 'export',
            'model_type'      => 'InsuranceReport',
            'model_id'        => '0',
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
            'context'         => 'Insurance CSV export, ' . $patients->count() . ' rows',
        ]);

        // Use the global response helper to stream the file download
        return response()->stream($callback, 200, $headers);
    }
    /**
     * Display the System Audit Log (admin only).
     */
    public function systemAudit(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Access restricted to administrators.');
        }

        $logs = AuditLog::orderByDesc('created_at')->paginate(15);

        // Recent red flags for the left-column panel (last 5 days, max 5 shown)
        $recentFlags = AuditLog::where('is_flagged', true)
            ->where('created_at', '>=', now()->subDays(5))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('reports::system_audit', compact('logs', 'recentFlags'));
    }

    /**
     * Display all red-flagged audit entries (admin only).
     */
    public function auditFlags(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Access restricted to administrators.');
        }

        $flags = AuditLog::where('is_flagged', true)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('reports::audit_flags', compact('flags'));
    }

    // =========================================================================
    // TREATMENT REPORT / INVOICE
    // =========================================================================

    /**
     * Landing page — lets the user choose between Invoice and Clinical Report.
     */
    public function treatmentReportIndex()
    {
        return view('reports::treatment_report_index');
    }

    /**
     * Display the Invoice form.
     */
    public function treatmentReport()
    {
        $reportNumber   = $this->nextReportNumber();
        $bankOptions    = $this->parseMarkdownSections(storage_path('app/reports/bank_details.md'));
        $signatureOptions = $this->parseMarkdownSections(storage_path('app/reports/signatures.md'));

        return view('reports::treatment_report', compact('reportNumber', 'bankOptions', 'signatureOptions'));
    }

    /**
     * Render the print preview in a new tab (POST — receives all form data).
     */
    public function treatmentReportPreview(Request $request)
    {
        $data = $request->only([
            'report_number', 'report_date', 'report_type', 'patient_name',
            'info_box', 'notes', 'items', 'currency', 'grand_total',
            'bank_details', 'signature',
        ]);

        $this->logReportNumber(
            $data['report_number'] ?? '',
            $data['report_type']   ?? '',
            $data['patient_name']  ?? ''
        );

        AuditLog::create([
            'user_id'         => Auth::id(),
            'user_name'       => Auth::user()?->name,
            'action'          => 'printed',
            'action_category' => 'print',
            'model_type'      => 'TreatmentReport',
            'model_id'        => $data['report_number'] ?? '0',
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
            'context'         => 'Treatment report print: ' . ($data['report_type'] ?? 'N/A') . ' — Patient: ' . ($data['patient_name'] ?? 'N/A') . ', #' . ($data['report_number'] ?? 'N/A'),
        ]);

        return view('reports::treatment_report_print', compact('data'));
    }

    /**
     * Display the Clinical Report / Prescription form.
     */
    public function clinicalReport()
    {
        $reportNumber     = $this->nextReportNumber();
        $bankOptions      = $this->parseMarkdownSections(storage_path('app/reports/bank_details.md'));
        $signatureOptions = $this->parseMarkdownSections(storage_path('app/reports/signatures.md'));

        return view('reports::clinical_report', compact('reportNumber', 'bankOptions', 'signatureOptions'));
    }

    /**
     * Render the Clinical Report print preview in a new tab.
     */
    public function clinicalReportPreview(Request $request)
    {
        $data = $request->only([
            'report_number', 'report_date', 'report_type', 'patient_name',
            'info_box', 'report_body', 'notes', 'bank_details', 'signature',
        ]);

        $this->logReportNumber(
            $data['report_number'] ?? '',
            $data['report_type']   ?? '',
            $data['patient_name']  ?? ''
        );

        AuditLog::create([
            'user_id'         => Auth::id(),
            'user_name'       => Auth::user()?->name,
            'action'          => 'printed',
            'action_category' => 'print',
            'model_type'      => 'ClinicalReport',
            'model_id'        => $data['report_number'] ?? '0',
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
            'context'         => 'Clinical report print: ' . ($data['report_type'] ?? 'N/A') . ' — Patient: ' . ($data['patient_name'] ?? 'N/A') . ', #' . ($data['report_number'] ?? 'N/A'),
        ]);

        return view('reports::clinical_report_print', compact('data'));
    }

    /**
     * AJAX patient name search — returns JSON array [{patient_id, name, acc_no}].
     */
    public function patientSearch(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $patients = Patient::select('patient_id', 'name', 'acc_no')
            ->where('name', 'like', '%' . $q . '%')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($patients);
    }

    /**
     * AJAX patient summary — returns a brief read-only overview for the info modal.
     */
    public function patientSummary(Request $request)
    {
        // Patient base data + last visit (no insurance join — encrypted fields need model decryption)
        $patient = Patient::select([
                'patients.patient_id',
                'patients.name',
                'patients.acc_no',
                'patients.tel',
                'patients.location',
                'patients.pobox',
                'patients.town',
                'patients.active',
                DB::raw('MAX(pc.time_stamp) as last_visit'),
            ])
            ->leftJoin('patients_clinical as pc', 'patients.patient_id', '=', 'pc.patient_id')
            ->where('patients.patient_id', $request->get('id'))
            ->groupBy(
                'patients.patient_id', 'patients.name', 'patients.acc_no',
                'patients.tel', 'patients.location', 'patients.pobox', 'patients.town',
                'patients.active'
            )
            ->first();

        if (!$patient) {
            return response()->json(null, 404);
        }

        // Insurance fetched separately so encrypted fields decrypt via model casts
        $insurance = Insurance::where('ver_patient_id', $patient->patient_id)->first();
        $hasInsurance = $insurance && $insurance->insurance_no && $insurance->insurance_no !== '0';

        return response()->json([
            'name'               => $patient->name,
            'acc_no'             => $patient->acc_no,
            'tel'                => $patient->tel      ?: null,
            'location'           => $patient->location ?: null,
            'pobox'              => $patient->pobox    ?: null,
            'town'               => $patient->town     ?: null,
            'clinic'             => $patient->active == 1 ? 'Arusha' : ($patient->active == 2 ? 'Dar es Salaam' : 'Unknown'),
            'last_visit'         => $patient->last_visit
                                        ? Carbon::parse($patient->last_visit)->format('d/m/Y')
                                        : null,
            'insurance_no'       => $hasInsurance ? $insurance->insurance_no       : null,
            'insurance_provider' => $hasInsurance ? ($insurance->insurance_provider ?: null) : null,
            'insurance_id_no'    => $hasInsurance ? ($insurance->insurance_id_no    ?: null) : null,
            'policy_status'      => $hasInsurance ? ($insurance->policy_status == 1 ? 'Active' : 'Inactive') : null,
        ]);
    }

    /**
     * AJAX clinical records for the floating panel — read-only, decrypted via model casts.
     */
    public function patientClinicalRecords(Request $request)
    {
        $records = PatientClinical::where('patient_id', $request->get('id'))
            ->orderByDesc('time_stamp')
            ->limit(30)
            ->get(['time_stamp', 'diagnostic', 'description', 'amount', 'paid', 'balance']);

        return response()->json($records->map(function ($r) {
            return [
                'date'        => $r->time_stamp
                                    ? Carbon::parse($r->time_stamp)->format('d/m/Y')
                                    : '—',
                'diagnostic'  => $r->diagnostic  ?: '—',
                'description' => $r->description
                                    ? mb_substr($r->description, 0, 100)
                                    : '—',
                'amount'      => is_numeric($r->amount)  ? number_format((float)$r->amount,  2) : ($r->amount  ?: '—'),
                'paid'        => is_numeric($r->paid)    ? number_format((float)$r->paid,    2) : ($r->paid    ?: '—'),
                'balance'     => is_numeric($r->balance) ? number_format((float)$r->balance, 2) : ($r->balance ?: '—'),
            ];
        }));
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Parse a markdown file into sections: [['title' => '...', 'body' => '...'], ...]
     * Sections are separated by lines beginning with "## ".
     */
    private function parseMarkdownSections(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $sections = [];
        $parts = preg_split('/^## /m', file_get_contents($path));

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            $lines = explode("\n", $part);
            $title = trim(array_shift($lines));
            $body  = trim(implode("\n", $lines));

            if ($title) {
                $sections[] = ['title' => $title, 'body' => $body];
            }
        }

        return $sections;
    }

    /**
     * Generate the next available report number for today in YYMMDDxx format (xx = 01–99).
     */
    private function nextReportNumber(): string
    {
        $today = now()->format('ymd');  // e.g. 260327
        $month = now()->format('ym');   // e.g. 2603
        $path  = storage_path("app/reports/numbers/{$month}.md");

        $max = 0;
        if (file_exists($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (preg_match('/^' . preg_quote($today, '/') . '-(\d{2})/', $line, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }
        }

        return $today . '-' . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Append a one-line entry to the monthly report number log.
     */
    private function logReportNumber(string $number, string $type, string $name): void
    {
        if (empty($number)) return;

        $month = substr($number, 0, 4); // "2603" from "260327-01"
        $dir   = storage_path('app/reports/numbers');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            "{$dir}/{$month}.md",
            "{$number} | {$type}: {$name}\n",
            FILE_APPEND | LOCK_EX
        );
    }

}
