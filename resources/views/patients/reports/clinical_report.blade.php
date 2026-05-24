@extends('layouts.app')

@section('title', 'Clinical Report / Prescription')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

<div class="p-6 bg-white rounded-xl shadow-lg">

    <div class="flex items-center gap-3 mb-1">
        <a href="{{ route('reports.treatment_report') }}"
           class="text-sm text-gray-400 hover:text-gray-600 transition">← Back</a>
        <h1 class="text-2xl font-bold text-gray-800">Clinical Report / Prescription</h1>
    </div>
    <p class="text-sm text-gray-500 mb-6">Fill in the form, then click <strong>Preview &amp; Print</strong> to open a clean printable version in a new tab.</p>

    <form id="report-form"
          method="POST"
          action="{{ route('reports.clinical_report.preview') }}"
          target="_blank">
        @csrf

        {{-- ===== ROW 1: Report number + Date ===== --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label for="report_number" class="block text-sm font-medium text-gray-700 mb-1">Report no.</label>
                <input type="text"
                       id="report_number"
                       name="report_number"
                       value="{{ $reportNumber }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-offset-1">
                <p class="text-xs text-gray-400 mt-1">Auto-generated for today. Edit if needed.</p>
            </div>
            <div>
                <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date"
                       id="report_date"
                       name="report_date"
                       value="{{ now()->format('Y-m-d') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-offset-1">
            </div>
        </div>

        {{-- ===== ROW 2: Document type + Patient name ===== --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Document type &amp; recipient</label>
            <div class="flex items-center gap-2">

                <select name="report_type"
                        id="report_type"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-offset-1 shrink-0">
                    <option value="Report for">Report for</option>
                    <option value="Prescription for">Prescription for</option>
                </select>

                <div class="relative flex-1">
                    <input type="text"
                           id="patient-name"
                           name="patient_name"
                           placeholder="Type a name, or search from patient records…"
                           autocomplete="off"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-offset-1">
                    <input type="hidden" id="patient-id" name="patient_id">

                    <div id="patient-suggestions"
                         class="hidden absolute left-0 right-0 top-full z-40 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                    </div>
                </div>

                <button type="button"
                        id="patient-info-btn"
                        title="View patient record"
                        class="hidden shrink-0 btn-clinic-grey text-sm px-3 py-2 rounded-lg"
                        onclick="showPatientInfo()">
                    ℹ Record
                </button>

            </div>
        </div>

        {{-- ===== SINGLE INFO BOX ===== --}}
        <div class="mb-5">
            <label for="info_box" class="block text-sm font-medium text-gray-700 mb-1">Info box</label>
            <textarea id="info_box"
                      name="info_box"
                      rows="4"
                      placeholder="Patient address, contact details, notes for this document…"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-offset-1"></textarea>
        </div>

        {{-- ===== REPORT BODY ===== --}}
        <div class="mb-5">
            <label for="report_body" class="block text-sm font-medium text-gray-700 mb-1">Report / Prescription body</label>
            <textarea id="report_body"
                      name="report_body"
                      rows="12"
                      placeholder="Enter the report findings, treatment plan, or prescription details…"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono resize-y focus:outline-none focus:ring-2 focus:ring-offset-1"></textarea>
        </div>

        {{-- ===== BOTTOM: BANK DETAILS (right) ===== --}}
        <div class="grid grid-cols-2 gap-4 mb-3">

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="notes"
                          name="notes"
                          rows="5"
                          placeholder="Additional notes for this document…"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-offset-1"></textarea>
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <label for="bank_details" class="text-sm font-medium text-gray-700">Bank Details</label>
                    <button type="button" onclick="openModal('bank')"
                            class="text-xs btn-clinic-grey px-2 py-1 rounded">Change…</button>
                </div>
                <textarea id="bank_details"
                          name="bank_details"
                          rows="5"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono resize-y focus:outline-none focus:ring-2 focus:ring-offset-1">{{ $bankOptions[0]['body'] ?? '' }}</textarea>
            </div>

        </div>

        {{-- ===== SIGNATURE — narrow, 2 rows ===== --}}
        <div class="w-1/2 mb-6">
            <div class="flex justify-between items-center mb-1">
                <label for="signature" class="text-sm font-medium text-gray-700">Signature block</label>
                <button type="button" onclick="openModal('signature')"
                        class="text-xs btn-clinic-grey px-2 py-1 rounded">Change…</button>
            </div>
            <textarea id="signature"
                      name="signature"
                      rows="2"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-offset-1">{{ $signatureOptions[0]['body'] ?? '' }}</textarea>
        </div>

        {{-- ===== SUBMIT ===== --}}
        <div class="flex justify-end border-t pt-4">
            <button type="submit" class="btn-clinic-primary px-8 py-2 rounded-lg font-semibold text-sm shadow-md">
                Preview &amp; Print ↗
            </button>
        </div>

    </form>
</div>


{{-- ================================================================
     BANK DETAILS MODAL
================================================================ --}}
<div id="bank-modal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
     role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Select Bank Details</h3>
        <div class="space-y-3 max-h-72 overflow-y-auto">
            @foreach ($bankOptions as $option)
            <button type="button"
                    class="bank-option w-full text-left p-3 border border-gray-200 rounded-lg hover:border-clinic hover:bg-gray-50 transition"
                    data-value="{{ $option['body'] }}">
                <div class="font-semibold text-sm text-clinic-bold mb-1">{{ $option['title'] }}</div>
                <div class="text-xs text-gray-500 whitespace-pre-line leading-relaxed">{{ $option['body'] }}</div>
            </button>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" onclick="closeModal('bank')" class="btn-clinic-grey px-4 py-2 rounded-lg text-sm">Cancel</button>
        </div>
    </div>
</div>


{{-- ================================================================
     SIGNATURE MODAL
================================================================ --}}
<div id="signature-modal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
     role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Select Signature Block</h3>
        <div class="space-y-3">
            @foreach ($signatureOptions as $option)
            <button type="button"
                    class="signature-option w-full text-left p-3 border border-gray-200 rounded-lg hover:border-clinic hover:bg-gray-50 transition"
                    data-value="{{ $option['body'] }}">
                <div class="font-semibold text-sm text-clinic-bold mb-1">{{ $option['title'] }}</div>
                <div class="text-xs text-gray-500 whitespace-pre-line">{{ $option['body'] }}</div>
            </button>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" onclick="closeModal('signature')" class="btn-clinic-grey px-4 py-2 rounded-lg text-sm">Cancel</button>
        </div>
    </div>
</div>


{{-- ================================================================
     PATIENT INFO MODAL
================================================================ --}}
<div id="patient-modal"
     class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center"
     role="dialog" aria-modal="true">
    <div class="bg-gray-50 rounded-2xl p-6 w-full max-w-md shadow-xl border border-gray-200">

        <h3 class="text-lg font-bold text-gray-800 mb-1">Patient Record</h3>
        <p class="text-xs text-gray-400 mb-4">Tick the fields you want copied into the Info box, then click Close.</p>

        <div id="patient-modal-content" class="text-sm space-y-1 min-h-[80px]">
            <p class="text-gray-400 italic">Loading…</p>
        </div>

        <div id="pat-clinical-link" class="hidden mt-4 pt-3 border-t border-gray-200">
            <a href="#"
               onclick="fetchClinicalRecords(); return false;"
               class="text-sm font-medium text-blue-600 hover:text-blue-800">
                📋 View Clinical Records
            </a>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button"
                    onclick="closeModal('patient')"
                    class="btn-clinic-grey px-4 py-2 rounded-lg text-sm">
                Close without copying
            </button>
            <button type="button"
                    onclick="closePatientAndApply()"
                    class="btn-clinic-primary px-4 py-2 rounded-lg text-sm">
                Close &amp; copy to Info box
            </button>
        </div>
    </div>
</div>


{{-- ================================================================
     CLINICAL RECORDS FLOATING PANEL
================================================================ --}}
<div id="clinical-panel"
     style="display:none; position:fixed; top:90px; right:30px; width:680px; max-height:420px;
            z-index:60; flex-direction:column;
            background:#fff; border:1px solid #d1d5db; border-radius:14px;
            box-shadow:0 16px 40px rgba(0,0,0,0.18);">

    <div id="clinical-drag-handle"
         style="cursor:move; background:#f3f4f6; border-radius:14px 14px 0 0;
                padding:10px 16px; display:flex; justify-content:space-between;
                align-items:center; user-select:none; border-bottom:1px solid #e5e7eb;">
        <span style="font-weight:600; font-size:13px; color:#374151;">📋 Clinical Records</span>
        <button onclick="closeClinicalPanel()"
                style="background:#ef4444; color:#fff; border:none; border-radius:6px;
                       padding:3px 10px; font-size:12px; cursor:pointer; font-weight:600;">
            ✕ Close
        </button>
    </div>

    <div id="clinical-panel-content"
         style="overflow-y:auto; padding:12px; flex:1;">
        <p style="color:#9ca3af; font-style:italic;">Loading…</p>
    </div>
</div>

@endsection


@push('scripts')
<script>
// ============================================================
// PATIENT LIVE SEARCH
// ============================================================
const nameInput      = document.getElementById('patient-name');
const suggestions    = document.getElementById('patient-suggestions');
const patientIdInput = document.getElementById('patient-id');
const infoBtnEl      = document.getElementById('patient-info-btn');
let   searchTimer;

nameInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    infoBtnEl.classList.add('hidden');
    patientIdInput.value = '';

    const q = this.value.trim();
    if (q.length < 2) {
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
        return;
    }

    searchTimer = setTimeout(() => {
        fetch(`{{ route('reports.patient_search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(patients => {
            suggestions.innerHTML = '';
            if (!patients.length) { suggestions.classList.add('hidden'); return; }
            patients.forEach(p => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b last:border-b-0 flex justify-between items-center';
                div.innerHTML = `<span class="font-medium">${p.name}</span>
                                 <span class="text-gray-400 text-xs ml-2">${p.acc_no}</span>`;
                div.addEventListener('click', () => {
                    nameInput.value      = p.name;
                    patientIdInput.value = p.patient_id;
                    suggestions.classList.add('hidden');
                    infoBtnEl.classList.remove('hidden');
                });
                suggestions.appendChild(div);
            });
            suggestions.classList.remove('hidden');
        })
        .catch(() => suggestions.classList.add('hidden'));
    }, 300);
});

document.addEventListener('click', function (e) {
    if (!nameInput.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.classList.add('hidden');
    }
});


// ============================================================
// PATIENT INFO MODAL
// ============================================================
function showPatientInfo() {
    const id = patientIdInput.value;
    if (!id) return;

    const content  = document.getElementById('patient-modal-content');
    const clinLink = document.getElementById('pat-clinical-link');
    content.innerHTML = '<p class="text-gray-400 italic">Loading…</p>';
    clinLink.classList.add('hidden');
    document.getElementById('patient-modal').classList.remove('hidden');

    fetch(`{{ route('reports.patient_summary') }}?id=${encodeURIComponent(id)}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(d => {
        const fields = [
            { label: 'Name',               value: d.name },
            { label: 'Acc. No.',           value: d.acc_no },
            { label: 'Clinic',             value: d.clinic },
            { label: 'Telephone',          value: d.tel },
            { label: 'Location',           value: d.location },
            { label: 'P.O.Box',            value: d.pobox },
            { label: 'Town',               value: d.town },
            { label: 'Last visit',         value: d.last_visit },
            { label: 'Insurance No.',      value: d.insurance_no },
            { label: 'Insurance Provider', value: d.insurance_provider },
            { label: 'Insurance ID No.',   value: d.insurance_id_no },
            { label: 'Policy status',      value: d.policy_status },
        ];

        let html = '<div class="space-y-1">';
        fields.forEach((f, i) => {
            if (!f.value) return;
            const cbId = `pat-cb-${i}`;
            const line = `${f.label}: ${f.value}`;
            html += `<label for="${cbId}"
                           class="flex items-center gap-2 cursor-pointer text-sm py-1 px-2 rounded hover:bg-gray-100">
                        <input type="checkbox" id="${cbId}"
                               class="pat-field-cb rounded"
                               data-line="${line.replace(/"/g, '&quot;')}">
                        <span class="text-gray-500 font-medium w-24 shrink-0">${f.label}:</span>
                        <span class="text-gray-800">${f.value}</span>
                    </label>`;
        });
        html += '</div>';
        content.innerHTML = html;
        clinLink.classList.remove('hidden');
    })
    .catch(() => {
        content.innerHTML = '<p class="text-red-500 text-sm">Could not load patient record.</p>';
    });
}

function closePatientAndApply() {
    const checked = document.querySelectorAll('.pat-field-cb:checked');
    if (checked.length > 0) {
        const lines   = Array.from(checked).map(cb => cb.dataset.line);
        const infoBox = document.getElementById('info_box');
        const current = infoBox.value.trim();
        infoBox.value = current ? current + '\n' + lines.join('\n') : lines.join('\n');
    }
    closeModal('patient');
}


// ============================================================
// CLINICAL RECORDS FLOATING PANEL
// ============================================================
const clinicalPanel = document.getElementById('clinical-panel');
const dragHandle    = document.getElementById('clinical-drag-handle');
let isDragging = false, dragOffX = 0, dragOffY = 0;

dragHandle.addEventListener('mousedown', function (e) {
    if (e.target.tagName === 'BUTTON') return;
    isDragging = true;
    const rect = clinicalPanel.getBoundingClientRect();
    dragOffX = e.clientX - rect.left;
    dragOffY = e.clientY - rect.top;
    clinicalPanel.style.right = 'auto';
    e.preventDefault();
});
document.addEventListener('mousemove', function (e) {
    if (!isDragging) return;
    clinicalPanel.style.left = (e.clientX - dragOffX) + 'px';
    clinicalPanel.style.top  = (e.clientY - dragOffY) + 'px';
});
document.addEventListener('mouseup', () => { isDragging = false; });

function fetchClinicalRecords() {
    const id = patientIdInput.value;
    if (!id) return;

    const content = document.getElementById('clinical-panel-content');
    clinicalPanel.style.display = 'flex';
    content.innerHTML = '<p style="color:#9ca3af;font-style:italic;padding:8px;">Loading…</p>';

    fetch(`{{ route('reports.patient_records') }}?id=${encodeURIComponent(id)}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(records => {
        if (!records.length) {
            content.innerHTML = '<p style="color:#6b7280;padding:8px;">No clinical records found.</p>';
            return;
        }
        let html = `<table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;position:sticky;top:0;">
                    <th style="padding:6px 8px;text-align:left;color:#374151;font-weight:600;white-space:nowrap;">Date</th>
                    <th style="padding:6px 8px;text-align:left;color:#374151;font-weight:600;">Diagnostic</th>
                    <th style="padding:6px 8px;text-align:left;color:#374151;font-weight:600;">Description</th>
                    <th style="padding:6px 8px;text-align:right;color:#374151;font-weight:600;">Amount</th>
                    <th style="padding:6px 8px;text-align:right;color:#374151;font-weight:600;">Paid</th>
                    <th style="padding:6px 8px;text-align:right;color:#374151;font-weight:600;">Balance</th>
                </tr>
            </thead>
            <tbody>`;
        records.forEach((r, i) => {
            const bg = i % 2 !== 0 ? 'background:#f9fafb;' : '';
            html += `<tr style="${bg}border-bottom:1px solid #f3f4f6;">
                <td style="padding:5px 8px;white-space:nowrap;">${r.date}</td>
                <td style="padding:5px 8px;">${r.diagnostic}</td>
                <td style="padding:5px 8px;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${r.description}">${r.description}</td>
                <td style="padding:5px 8px;text-align:right;">${r.amount}</td>
                <td style="padding:5px 8px;text-align:right;">${r.paid}</td>
                <td style="padding:5px 8px;text-align:right;">${r.balance}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        content.innerHTML = html;
    })
    .catch(() => {
        content.innerHTML = '<p style="color:#ef4444;padding:8px;">Could not load records.</p>';
    });
}

function closeClinicalPanel() {
    clinicalPanel.style.display = 'none';
}


// ============================================================
// BANK / SIGNATURE MODALS
// ============================================================
function openModal(type) { document.getElementById(type + '-modal').classList.remove('hidden'); }
function closeModal(type) { document.getElementById(type + '-modal').classList.add('hidden'); }

document.querySelectorAll('.bank-option').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('bank_details').value = this.dataset.value;
        closeModal('bank');
    });
});
document.querySelectorAll('.signature-option').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('signature').value = this.dataset.value;
        closeModal('signature');
    });
});

['bank-modal', 'signature-modal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function (e) {
        if (e.target === this) closeModal(id.replace('-modal', ''));
    });
});
</script>
@endpush
