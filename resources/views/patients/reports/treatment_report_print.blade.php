<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['report_type'] ?? 'Report' }} — {{ $data['report_number'] ?? '' }}</title>
    <style>
        /* ---- FORCE EXACT COLOUR & BORDER RENDERING ON ALL BROWSERS ---- */
        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ---- RESET & BASE ---- */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            color: #000;
            background: #fff;
            /* top reduced by 1/4 (10→7mm); right 15mm; left 35mm */
            padding: 7mm 15mm 20mm 35mm;
        }

        /* ---- PRINT CONTROLS (hidden when printing) ---- */
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

        /* ---- META + INFO BOX — same row, meta left / box right ---- */
        .meta-and-info {
            display: grid;
            grid-template-columns: 2fr 3fr;
            gap: 14pt;
            margin-bottom: 14pt;
            align-items: start;
        }

        /* ---- REPORT META ---- */
        .report-meta { line-height: 1.8; }
        .report-meta .meta-row { display: flex; gap: 8pt; }
        .report-meta .label { font-weight: bold; min-width: 88pt; }

        /* ---- SINGLE INFO BOX — narrowed by 1/4, pushed right, shifted down one row ---- */
        .info-box {
            border: 1px solid #000;
            min-height: 60pt;
            padding: 6pt 8pt;
            font-size: 9.5pt;
            white-space: pre-wrap;
            line-height: 1.5;
            width: 75%;
            margin-left: auto;   /* pushes the box to the right of its column */
            margin-top: 19pt;    /* one meta row down (1.8 × 10.5pt) */
        }

        /* ---- PATIENT NAME BOX (under Report for:) ---- */
        .patient-name-box {
            border: 1px solid #000;
            padding: 3pt 8pt;
            font-size: 10.5pt;
            font-weight: 500;
            min-height: 16pt;
            margin-top: 3pt;
        }

        /* ---- LINE ITEMS TABLE ---- */
        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 14pt;
            font-size: 10pt;
        }
        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 4pt 6pt;
            vertical-align: top;
        }
        table.items th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }
        table.items .col-sn    { width: 26pt;  text-align: center; }
        table.items .col-qty   { width: 42pt;  text-align: center; }
        table.items .col-price { width: 72pt;  text-align: right;  }
        table.items .col-total { width: 72pt;  text-align: right;  }

        table.items tfoot tr td {
            font-weight: bold;
            border-top: 2px solid #000;
        }
        table.items tfoot .grand-label { text-align: left; padding-left: 6pt; }
        table.items tfoot .grand-amount { text-align: right; }

        /* Blank filler rows — visible borders but invisible text */
        table.items tbody tr.filler td { color: #fff; }

        /* ---- BOTTOM SECTION: notes wider by 1/6, bank narrower by 1/6 ---- */
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
            text-align: left;
        }
        .bottom-box-content {
            white-space: pre-wrap;  /* pre-wrap only on the content div, not the container */
        }
        .bottom-box.bank-details { font-size: 8.5pt; }
        .bottom-box-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            color: #555;
            margin-bottom: 3pt;
        }

        /* ---- SIGNATURE — two rows lower (7 + 2×13pt), tighter rows ---- */
        .signature-block {
            margin-top: 33pt;
            font-size: 10pt;
            line-height: 1.3;
            text-align: left;
            padding-left: 5px;
        }
        .signature-block .sig-label {
            font-size: 9pt;
            color: #555;
            margin-bottom: 1pt;
        }
        .sig-text {
            white-space: pre-wrap;  /* pre-wrap only on the content, not the container */
        }

        /* ---- PRINT MEDIA ---- */
        @media print {
            .print-controls { display: none !important; }
            body { padding: 0; }

            @page {
                /* top reduced by 1/4: 45→34mm; right 14mm; left 31mm */
                margin: 34mm 14mm 18mm 31mm;
            }

            /* Force borders in print — some browsers suppress them */
            table.items,
            table.items th,
            table.items td {
                border: 1px solid #000 !important;
            }
            table.items tfoot tr td {
                border-top: 2px solid #000 !important;
            }
            table.items th {
                background: #f0f0f0 !important;
            }
            .info-box,
            .bottom-box {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>
<body>

    {{-- ===== PRINT CONTROLS (not printed) ===== --}}
    <div class="print-controls">
        <button onclick="window.close()">✕ Close</button>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>

    {{-- ===== META (left) + SINGLE INFO BOX (right) — same row ===== --}}
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

    {{-- ===== LINE ITEMS TABLE ===== --}}
    @php
        $items      = $data['items'] ?? [];
        $currency   = $data['currency'] ?? 'TZS';
        $grandTotal = floatval($data['grand_total'] ?? 0);

        $filledItems = array_values(array_filter($items, fn($r) => trim($r['description'] ?? '') !== ''));
        // Minimum 9 visible rows; if more than 9 filled, all show with zero filler
        $fillerCount = max(0, 9 - count($filledItems));
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th class="col-sn">s/n</th>
                <th>Description</th>
                <th class="col-qty">Quantity</th>
                <th class="col-price">Unit price</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filledItems as $k => $item)
            <tr>
                <td class="col-sn">{{ $k + 1 }}</td>
                <td>{{ $item['description'] }}</td>
                <td class="col-qty">{{ $item['qty'] ?? '' }}</td>
                <td class="col-price">
                    @if (floatval($item['unit_price'] ?? 0) > 0)
                        {{ number_format(floatval($item['unit_price']), 2) }}
                    @endif
                </td>
                <td class="col-total">
                    @if (floatval($item['total'] ?? 0) > 0)
                        {{ number_format(floatval($item['total']), 2) }}
                    @endif
                </td>
            </tr>
            @endforeach

            @for ($f = 0; $f < $fillerCount; $f++)
            <tr class="filler">
                <td class="col-sn">.</td><td>&nbsp;</td><td></td><td></td><td></td>
            </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="grand-label">GRAND TOTAL</td>
                <td class="grand-amount col-total">
                    {{ $currency }} {{ number_format($grandTotal, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- ===== BOTTOM: NOTES (left) | BANK DETAILS (right) ===== --}}
    <div class="bottom-section">
        <div class="bottom-box">
            <div class="bottom-box-label">Notes</div>
            <div class="bottom-box-content">{{ $data['notes'] ?? '' }}</div>
        </div>
        <div class="bottom-box bank-details">
            <div class="bottom-box-label">Bank Details</div>
            <div class="bottom-box-content">{{ $data['bank_details'] ?? '' }}</div>
        </div>
    </div>

    {{-- ===== SIGNATURE — plain text, left-aligned, below bottom boxes ===== --}}
    @if (!empty(trim($data['signature'] ?? '')))
    <div class="signature-block">
        <div class="sig-label">Signature:</div>
        <div class="sig-text">{{ $data['signature'] }}</div>
    </div>
    @endif

</body>
</html>
