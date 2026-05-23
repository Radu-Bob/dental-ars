<?php

return [
    [
        'title'       => 'Treatment Report / Invoice',
        'description' => 'Compose and print a treatment report, invoice, receipt, or pro-forma for any patient.',
        'route'       => 'reports.treatment_report',
    ],
    [
        'title'       => 'Patients Attending',
        'description' => 'A comprehensive list of active patients and their upcoming or recent attendance.',
        'route'       => 'reports.patients_attending',
    ],
    [
        'title'       => 'Payments Ledger',
        'description' => 'Detailed financial ledger showing all transactions, payments, and account balances.',
        'route'       => 'reports.payments_ledger',
    ],
    [
        'title'       => 'Estimate Report',
        'description' => 'An overview of outstanding, accepted, and rejected treatment estimates.',
        'route'       => 'reports.estimate_report',
    ],
    [
        'title'       => 'Patients with Active Insurance',
        'description' => 'Lists all patients who have an insurance record, sorted by the date of their most recent clinical record.',
        'route'       => 'reports.insurance',
    ],
    [
        'title'       => 'Patient Demographics',
        'description' => 'Full summary of patient demographic data, including insurance and contact details.',
        'route'       => 'reports.patients_demographics',
    ],
    [
        'title'       => 'Clinical Procedures Summary',
        'description' => 'Statistical summary of common clinical procedures performed this quarter.',
        'route'       => 'reports.clinical_summary',
    ],
    [
        'title'       => 'System Audit Log',
        'description' => 'Tamper-evident record of every create, update, and delete action performed on patient data.',
        'route'       => 'reports.system_audit',
    ],
    [
        'title'       => 'Import Red Flags',
        'description' => 'All partner-import events where the user confirmed import despite a similar existing patient record.',
        'route'       => 'reports.audit_flags',
    ],
];
