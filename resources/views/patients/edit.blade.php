@extends('layouts.app')

@section('left_content')
    {{-- (I have kept your sidebar logic as it was, it's looking quite sharp) --}}
    @if ($patient)
        <div class="bg-white p-6 rounded-xl shadow-lg space-y-5">
            <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                <h3 class="text-sm font-semibold text-blue-700 mb-1">
                    <i class="fas fa-edit mr-1"></i> Editing Record
                </h3>
                <p class="text-xs text-gray-700">
                    You are modifying the <strong>{{ $isClinical ? 'Clinical' : 'Estimate' }}</strong> entry.
                </p>
            </div>

            <div class="space-y-3">
                <p class="text-lg font-bold uppercase text-green-700 block">{{ $patient->name }}</p> 
                
                <hr class="border-gray-100">

                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <div>
                        <span class="text-gray-900 block">ID: {{ $patient->patient_id }}</span> 
                    </div>
                    <div>
                        <span class="text-gray-900 block">Age: {{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : 'N/A' }}</span>
                    </div>
                    <div class="col-span-2 mt-2">
                        <span class="text-gray-900 block">Tel: {{ $patient->tel ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            
            <hr class="border-gray-200">

            <div class="pt-2 space-y-2">
                {{-- Primary Action: Return to the specific patient's history --}}
                <a href="{{ route('patients.show', ['patient_id' => $record->patient_id, 'showAllRecords' => $showAllRecords]) }}" 
                    class="w-full text-center block bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg transition duration-150 shadow-md">
                    <i class="fas fa-arrow-left mr-1"></i> Cancel & Back to History
                </a>
                
                {{-- Secondary Action: Back to Search (Gray) --}}
                <a href="{{ route('patients.index') }}" 
                    class="w-full text-center block bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Back to Search
                </a>
            </div>
        </div>
    @endif
@endsection


@section('content')
    {{-- Added ID and dynamic background check --}}
    <div id="form-container"
         class="container transition-all duration-500 rounded-xl shadow-lg"
         style="background-color: {{ $isClinical ? '#ffffff' : '#fff7ed' }};">
        
        <h1>Editing {{ $patient->name }},&nbsp;#{{ $record->patient_id }}</h1>
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- FIX: Change route name to 'records.update' and parameter key to 'record' --}}
        <form action="{{ route('records.update', ['record' => $record->patient_clinic_id]) }}" method="POST">
            @csrf
            @method('PUT')

            <input type="hidden" id="record_type" name="record_type" value="{{ $isClinical ? 'clinical' : 'estimate' }}">

            <div class="tabs mb-6">
                {{-- Only show the Clinical tab if the record IS clinical --}}
                @if($isClinical)
                    <div id="tab-clinical" class="tab-button active">
                        Clinical Record
                    </div>
                @endif
                
                {{-- Only show the Estimate tab if the record IS an estimate --}}
                @if(!$isClinical)
                    <div id="tab-estimate" class="tab-button active">
                        Estimate Record
                    </div>
                @endif
            </div>

            {{-- ROW 1: Date and Tooth --}}
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="form-group col-span-2">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" class="form-control" value="{{ $record->date }}">
                </div>

                <div class="form-group">
                    <label for="tooth">Tooth:</label>
                    <input type="text" id="tooth" name="tooth" class="form-control" value="{{ $record->tooth }}">
                </div>
            </div> {{-- END ROW 1 --}}

            {{-- ROW 2: Diagnostic --}}
            <div class="form-group mb-4">
                <label for="diagnostic">Diagnostic:</label>
                <input type="text" id="diagnostic" name="diagnostic" class="form-control" value="{{ $record->diagnostic }}">
            </div> {{-- END ROW 2 --}}

            <div id="clinical-fields" class="tab-content" style="display: {{ $isClinical ? 'block' : 'none' }};">
                <div class="form-group mb-4">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="3" class="form-control">{{ $record->description }}</textarea>
                </div>

                {{-- Financial trio --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="form-group">
                        <label for="amount">Amount:</label>
                        <input type="text" id="amount" name="amount" class="form-control" value="{{ $record->amount }}">
                    </div>

                    <div class="form-group">
                        <label for="paid">Paid:</label>
                        <input type="text" id="paid" name="paid" class="form-control" value="{{ $record->paid }}">
                    </div>

                    <div class="form-group">
                        <label for="balance">Balance:</label>
                        <input type="text" id="balance" name="balance" class="form-control" value="{{ $record->balance }}">
                    </div>
                </div>
            </div>

            <div id="estimate-fields" class="tab-content" style="display: {{ $isClinical ? 'none' : 'block' }};">
                <div class="form-group mb-4">
                    <label for="estimate_description">Description (Estimate):</label>
                    <textarea name="estimate_description" id="estimate_description" rows="3" class="form-control">{{ $record->estimate_description }}</textarea>
                </div>

                {{-- Financial trio --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="form-group">
                        <label for="estimate_cost">Amount:</label>
                        <input type="text" id="estimate_cost" name="estimate_cost" class="form-control" value="{{ $record->estimate_cost }}">
                    </div>

                    <div class="form-group">
                        <label for="estimate_paid">Paid:</label>
                        <input type="text" id="estimate_paid" name="estimate_paid" class="form-control" value="{{ $record->estimate_paid }}">
                    </div>

                    <div class="form-group">
                        <label for="estimate_balance">Balance:</label>
                        <input type="text" id="estimate_balance" name="estimate_balance" class="form-control" value="{{ $record->estimate_balance }}">
                    </div>
                </div>
            </div>

            {{-- Insurance Claim Section — Clinical records only, patient must have an active policy --}}
            @if ($isClinical && isset($activeInsurance) && $activeInsurance)
                <div class="form-group mb-4 p-4 rounded-lg border border-orange-300 bg-orange-50">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_insurance_claim"
                               id="is_insurance_claim"
                               value="1"
                               class="w-4 h-4 accent-orange-600"
                               {{ $record->is_insurance_claim ? 'checked' : '' }}>
                        <span class="text-sm font-semibold text-orange-800">Bill or Credit to Insurance</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-7">
                        Provider: <strong>{{ $activeInsurance->insurance_provider }}</strong>
                        &nbsp;&middot;&nbsp; Policy No: <strong>{{ $activeInsurance->insurance_no ?? 'N/A' }}</strong>
                    </p>
                </div>
            @endif

            <div class="form-group mb-4">
                <label for="remarks">Remarks:</label>
                <textarea name="remarks" id="remarks" rows="3" class="form-control">{{ $record->remarks }}</textarea>
            </div>
            <button type="submit" class="btn-primary w-full py-3 mt-4">Update Record</button>
        </form>
    </div>

    <script>
        const ADMIN_PASSWORD = 'moveit'; 

        function checkPasswordAndShow(tabName) {
            const password = prompt('WARNING: Changing record type. Enter admin password:');
            if (password === ADMIN_PASSWORD) {
                showTab(tabName);
            } else if (password !== null) {
                alert('Incorrect password.');
            }
        }

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
                
                // Switch back to white
                container.style.backgroundColor = '#ffffff';
            } else {
                clinicalFields.style.display = 'none';
                estimateFields.style.display = 'block';
                clinicalBtn.classList.remove('active');
                estimateBtn.classList.add('active');
                typeInput.value = 'estimate';
                
                // Switch to Peach (#fff7ed is Tailwind orange-50)
                container.style.backgroundColor = '#fff7ed';
            }
        }
    </script>
@endsection