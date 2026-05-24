<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['report_type'] ?? 'Report' }} — {{ $data['report_number'] ?? '' }}</title>
    <style>
        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            color: #000;
            background: #fff;
            padding: 7mm 15mm 20mm 35mm;
        }

        /* ---- PRINT CONTROLS ---- */
        .print-controls {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-bottom: 18pt;
        }
        .print-controls button {
            padding: 6px 18px;
            border: 1px solid #aaa;
            border-radius: 5px;
            background: #f5f5f5;
            font-size: 10pt;
            cursor: pointer;
        }
        .print-controls .btn-print {
            background: #1d4ed8;
            color: #fff;
            border-color: #1d4ed8;
            font-weight: bold;
        }

        /* ---- META + INFO BOX ---- */
        .meta-and-info {
            display: grid;
            grid-template-columns: 2fr 3fr;
            gap: 14pt;
            margin-bottom: 14pt;
            align-items: start;
        }

        .report-meta { line-height: 1.8; }
        .report-meta .meta-row { display: flex; gap: 8pt; }
        .report-meta .label { font-weight: bold; min-width: 88pt; }

        .info-box {
            border: 1px solid #000;
            min-height: 60pt;
            padding: 6pt 8pt;
            font-size: 9.5pt;
            white-space: pre-wrap;
            line-height: 1.5;
            width: 75%;
            margin-left: auto;
            margin-top: 19pt;
        }

        .patient-name-box {
            border: 1px solid #000;
            padding: 3pt 8pt;
            font-size: 10.5pt;
            font-weight: 500;
            min-height: 16pt;
            margin-top: 3pt;
        }

        /* ---- REPORT BODY ---- */
        .report-body-section {
            margin-bottom: 14pt;
        }
        .report-body-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            color: #555;
            margin-bottom: 4pt;
        }
        .report-body-content {
            border: 1px solid #000;
            min-height: 160pt;
            padding: 8pt 10pt;
            font-size: 10.5pt;
            white-space: pre-wrap;
            line-height: 1.7;
        }

        /* ---- BOTTOM SECTION ---- */
        .bottom-section {
            display: grid;
            grid-template-columns: 7fr 5fr;
            gap: 12pt;
            margin-top: 10pt;
        }
        .bottom-box {
            border: 1px solid #000;
            padding: 6pt 8pt 6pt 5px;
            min-height: 60pt;
            font-size: 9pt;
            line-height: 1.6;
        }
        .bottom-box-content { white-space: pre-wrap; }
        .bottom-box.bank-details { font-size: 8.5pt; }
        .bottom-box-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            color: #555;
            margin-bottom: 3pt;
        }

        /* ---- SIGNATURE ---- */
        .signature-block {
            margin-top: 33pt;
            font-size: 10pt;
            line-height: 1.3;
            padding-left: 5px;
        }
        .signature-block .sig-label {
            font-size: 9pt;
            color: #555;
            margin-bottom: 1pt;
        }
        .sig-text { white-space: pre-wrap; }

        /* ---- PRINT MEDIA ---- */
        @media print {
            .print-controls { display: none !important; }
            body { padding: 0; }

            @page {
                margin: 34mm 14mm 18mm 31mm;
            }

            .info-box,
            .patient-name-box,
            .report-body-content,
            .bottom-box {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>
<body>

    {{-- ===== PRINT CONTROLS ===== --}}
    <div class="print-controls">
        <button onclick="window.close()">✕ Close</button>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>

    {{-- ===== META (left) + INFO BOX (right) ===== --}}
    <div class="meta-and-info">

        <div class="report-meta">
            <div class="meta-row">
                <span class="label">Report no.:</span>
                <span>{{ $data['report_number'] ?? '—' }}</span>
            </div>
            <div class="meta-row">
                <span class="label">Date:</span>
                <span>
                    @if (!empty($data['report_date']))
                        {{ \Carbon\Carbon::parse($data['report_date'])->format('d/m/Y') }}
                    @else —
                    @endif
                </span>
            </div>
            <div class="meta-row">
                <span class="label">{{ $data['report_type'] ?? 'Report for' }}:</span>
            </div>
            <div class="patient-name-box">{{ $data['patient_name'] ?? '' }}</div>
        </div>

        <div class="info-box">{{ $data['info_box'] ?? '' }}</div>

    </div>

    {{-- ===== REPORT BODY ===== --}}
    <div class="report-body-section">
        <div class="report-body-label">{{ $data['report_type'] ?? 'Report' }}</div>
        <div class="report-body-content">{{ $data['report_body'] ?? '' }}</div>
    </div>

    {{-- ===== BOTTOM: NOTES placeholder (left) | BANK DETAILS (right) ===== --}}
    <div class="bottom-section">
        <div class="bottom-box">
            <div class="bottom-box-label">Notes</div>
            <div class="bottom-box-content"></div>
        </div>
        <div class="bottom-box bank-details">
            <div class="bottom-box-label">Bank Details</div>
            <div class="bottom-box-content">{{ $data['bank_details'] ?? '' }}</div>
        </div>
    </div>

    {{-- ===== SIGNATURE ===== --}}
    @if (!empty(trim($data['signature'] ?? '')))
    <div class="signature-block">
        <div class="sig-label">Signature:</div>
        <div class="sig-text">{{ $data['signature'] }}</div>
    </div>
    @endif

</body>
</html>
