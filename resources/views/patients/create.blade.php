@extends('layouts.app')

@section('left_content')
    @if ($patient)
        <div class="bg-white p-6 rounded-xl shadow-lg space-y-5">
            <div class="p-3 bg-green-50 rounded-lg border border-green-100">
                <h3 class="text-sm font-semibold text-green-700 mb-1">
                    <i class="fas fa-plus-circle mr-1"></i> New Entry
                </h3>
                <p class="text-xs text-gray-700">Adding a new record for this patient.</p>
            </div>

            <div class="space-y-3">
                <p class="text-lg font-bold uppercase text-green-700 block">{{ $patient->name }}</p> 
                <hr class="border-gray-100">
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <div><span class="text-gray-900 block">ID: {{ $patient->patient_id }}</span></div>
                    <div><span class="text-gray-900 block">Age: {{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : 'N/A' }}</span></div>
                </div>
            </div>
            
            <hr class="border-gray-200">
            <div class="pt-2">
                <a href="{{ route('patients.show', ['patient_id' => $patient->patient_id]) }}" 
                    class="w-full text-center block bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Cancel & Back
                </a>
            </div>
        </div>
    @endif
@endsection

@section('content')
    {{-- IMPORTANT: This div MUST have the ID 'form-container' --}}
    <div id="form-container" class="patient-form-wrapper transition-all duration-500 p-8 rounded-xl bg-white shadow-lg">
        <h1 class="text-2xl font-bold mb-6">Add New Record</h1>

        <form action="{{ route('patients.store', ['patient_id' => $patient->patient_id]) }}" method="POST">
            @csrf
            <input type="hidden" id="record_type" name="record_type" value="clinical">

            <div class="tabs mb-6 flex space-x-2">
                <div id="tab-clinical" class="tab-button active cursor-pointer px-4 py-2" onclick="showTab('clinical')">
                    Clinical Record
                </div>
                <div id="tab-estimate" class="tab-button cursor-pointer px-4 py-2" onclick="showTab('estimate')">
                    Estimate
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>Tooth:</label>
                    <input type="text" name="tooth" class="form-control">
                </div>
            </div>

            <div class="form-group mb-4">
                <label>Diagnostic:</label>
                <input type="text" name="diagnostic" class="form-control">
            </div>

            {{-- Clinical Section --}}
            <div id="clinical-fields">
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3" class="form-control break-words"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group"><label>Amount:</label><input type="text" name="amount" class="form-control"></div>
                    <div class="form-group"><label>Paid:</label><input type="text" name="paid" class="form-control"></div>
                    <div class="form-group"><label>Balance:</label><input type="text" name="balance" class="form-control"></div>
                </div>
            </div>

            {{-- Estimate Section --}}
            <div id="estimate-fields" style="display:none;">
                <div class="form-group">
                    <label class="text-orange-900 font-bold">Estimate Description:</label>
                    <textarea name="estimate_description" rows="3" class="form-control border-orange-300 break-words"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group"><label>Cost (Est):</label><input type="text" name="estimate_cost" class="form-control border-orange-300"></div>
                    <div class="form-group"><label>Paid (Est):</label><input type="text" name="estimate_paid" class="form-control border-orange-300"></div>
                    <div class="form-group"><label>Balance (Est):</label><input type="text" name="estimate_balance" class="form-control border-orange-300"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="form-group"><label>Notes:</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
                <div class="form-group"><label>Remarks:</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
            </div>
            
            <button type="submit" class="btn-primary w-full py-3 mt-4 font-bold uppercase tracking-wider">Save New Record</button>
        </form>
    </div>

    <script>
        function showTab(tabName) {
            const clinicalFields = document.getElementById('clinical-fields');
            const estimateFields = document.getElementById('estimate-fields');
            const clinicalBtn = document.getElementById('tab-clinical');
            const estimateBtn = document.getElementById('tab-estimate');
            const typeInput = document.getElementById('record_type');
            const container = document.getElementById('form-container');

            if (tabName === 'clinical') {
                clinicalFields.style.display = 'block';
                estimateFields.style.display = 'none';
                clinicalBtn.classList.add('active');
                estimateBtn.classList.remove('active');
                typeInput.value = 'clinical';
                
                // Reset to white
                container.style.backgroundColor = '#ffffff'; 
                container.classList.remove('bg-orange-50');
            } else {
                clinicalFields.style.display = 'none';
                estimateFields.style.display = 'block';
                clinicalBtn.classList.remove('active');
                estimateBtn.classList.add('active');
                typeInput.value = 'estimate';
                
                // Force Peach Color via hex code to bypass any CSS conflicts
                container.style.backgroundColor = '#fff7ed'; // This is Tailwind's orange-50
            }
        }
    </script>
@endsection