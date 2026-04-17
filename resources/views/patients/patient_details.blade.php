@extends('layouts.app') 

@section('left_content')
    @if ($patient)
        <div class="bg-white p-6 rounded-xl shadow-lg space-y-5">
            <h2 class="text-xl font-bold text-gray-800 border-b pb-2">Patient Overview</h2>
            
            <div class="space-y-3">
                <p class="text-lg font-bold uppercase text-green-700 block">{{ $patient->name }}</p> 
                
                <hr class="border-gray-100">

                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <div>
                        <span class="text-gray-900 block">ID: {{ $patient->patient_id }}</span> 
                    </div>
                    <div>
                        {{-- Calculating age on the fly for the edit screen context --}}
                        <span class="text-gray-900 block">Age: {{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : 'N/A' }}</span>
                    </div>
                    <div class="col-span-2 mt-2">
                        <span class="text-gray-900 block">Tel: {{ $patient->tel ?? 'N/A' }}</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-gray-900 block truncate" title="{{ $patient->email }}">Email: {{ $patient->email ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            
            <hr class="border-gray-200">

            {{-- Remarks Display (Kept for clinical context while editing) --}}
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                <h3 class="text-sm font-semibold text-red-600 mb-1">Current Remarks</h3>
                <p class="text-xs text-gray-700 italic">
                    {{ $patient->remarks ?? 'No special remarks recorded.' }}
                </p>
            </div>

            {{-- Consolidated Action Buttons --}}
            <div class="pt-2 space-y-2">
                {{-- Primary Action: Return to the records (Green) --}}
                <a href="{{ route('patients.show', ['patient_id' => $patient->patient_id]) }}" 
                    class="w-full text-center block bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg transition duration-150 shadow-md">
                    <i class="fas fa-history mr-1"></i> View Clinical History
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
<div class="container">
    <h2>Edit Patient Record: {{ $patient->name }} (ID: {{ $patient->patient_id }})</h2>
    
    {{-- Messages omitted for brevity --}}
     @if (session('success'))
        <div class="alert alert-success d-flex justify-content-between align-items-center" role="alert">
            <span>{{ session('success') }}</span>
            <a href="{{ route('patients.show', ['patient_id' => $patient->patient_id]) }}" class="alert-link ml-auto text-success font-weight-bold">
                &larr; GO TO: {{ $patient->name }}'s Records
            </a>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('patient.update', $patient) }}">
        @csrf
        @method('PUT') 
        
        {{-- ROW 1: Name (1/2), DOB (1/4), Gender (1/4) --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
            <div class="md:col-span-6 form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                       value="{{ old('name', $patient->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-3 form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date"
                    class="form-control @error('date_of_birth') is-invalid @enderror"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth', ($patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('Y-m-d') : null) ) }}"
                    placeholder="YYYY-MM-DD">
                @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-3 form-group">
                <label for="gender">Gender</label>
                <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender">
                    <option value="">Select Gender</option>
                    <option value="M" {{ old('gender', $patient->gender) == 'M' ? 'selected' : '' }}>Male</option>
                    <option value="F" {{ old('gender', $patient->gender) == 'F' ? 'selected' : '' }}>Female</option>
                </select>
                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div> {{-- END ROW 1 --}}

        {{-- ROW 2: Telephone (1/2), Email (1/2) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="tel">Telephone</label>
                <input type="text" class="form-control @error('tel') is-invalid @enderror" id="tel" name="tel"
                       value="{{ old('tel', $patient->tel) }}">
                @error('tel') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                       value="{{ old('email', $patient->email) }}">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div> {{-- END ROW 2 --}}

        {{-- ROW 3: Occupation (2/3), Assigned Clinic (1/3) --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
            <div class="md:col-span-8 form-group">
                <label for="occupation">Occupation</label>
                <input type="text" class="form-control @error('occupation') is-invalid @enderror" id="occupation" name="occupation"
                       value="{{ old('occupation', $patient->occupation) }}">
                @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-4 form-group">
                <label for="active">Assigned Clinic</label>
                <select class="form-control @error('active') is-invalid @enderror" id="active" name="active" required>
                    <option value="2" {{ old('active', $patient->active) == 2 ? 'selected' : '' }}>DSM (2)</option>
                    <option value="1" {{ old('active', $patient->active) == 1 ? 'selected' : '' }}>ARS (1)</option>
                </select>
                @error('active') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div> {{-- END ROW 3 --}}

        {{-- ROW 4: Location, Town, PO Box (each 1/3) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" class="form-control @error('location') is-invalid @enderror" id="location" name="location"
                    value="{{ old('location', $patient->location) }}">
                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="town">Town</label>
                <input type="text" class="form-control @error('town') is-invalid @enderror" id="town" name="town"
                    value="{{ old('town', $patient->town) }}">
                @error('town') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="pobox">PO Box</label>
                <input type="text" class="form-control @error('pobox') is-invalid @enderror" id="pobox" name="pobox"
                    value="{{ old('pobox', $patient->pobox) }}">
                @error('pobox') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div> {{-- END ROW 4 --}}

        {{-- ROW 5: Remarks (full width) --}}
        <div class="form-group mb-4">
            <label for="remarks">Remarks</label>
            <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="5">{{ old('remarks', $patient->remarks) }}</textarea>
            @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div> {{-- END ROW 5 --}}
        
        <hr class="my-4">
        
        {{-- INSURANCE SECTION (Remains the same as it was correctly structured) --}}
        @if($patient->insurance)
            
            <div class="border p-4 mb-4 bg-gray-50 rounded-lg">
                <h5 class="mb-3">Editing Existing Insurance</h5>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="insurance_action" value="delete" id="delete_insurance">
                    <label class="form-check-label text-danger" for="delete_insurance">
                        <strong>Remove/Delete this Insurance Policy</strong>
                    </label>
                </div>

                <p>Current Policy: <strong>{{ $patient->insurance->insurance_provider }}</strong> (Policy #{{ $patient->insurance->insurance_no }})</p>

                {{-- Row 1: Provider (1/2), Policy Number (1/4), Insurance ID (1/4) --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                    <div class="md:col-span-6 form-group">
                        <label for="insurance_provider">Insurance Provider</label>
                        <select id="insurance_provider" name="insurance_provider"
                                class="form-control @error('insurance_provider') is-invalid @enderror">
                            <option value="">-- Select Provider --</option>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->provider_name }}"
                                    {{ old('insurance_provider', $patient->insurance->insurance_provider) == $provider->provider_name ? 'selected' : '' }}>
                                    {{ $provider->provider_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('insurance_provider') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="md:col-span-3 form-group">
                        <label for="insurance_no">Policy Number</label>
                        <input type="tel" class="form-control @error('insurance_no') is-invalid @enderror" id="insurance_no" name="insurance_no" pattern="[0-9]*"
                               value="{{ old('insurance_no', $patient->insurance->insurance_no) }}">
                        @error('insurance_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="md:col-span-3 form-group">
                        <label for="insurance_id_no">Insurance ID</label>
                        <input type="text" class="form-control @error('insurance_id_no') is-invalid @enderror" id="insurance_id_no" name="insurance_id_no"
                               value="{{ old('insurance_id_no', $patient->insurance->insurance_id_no) }}">
                        @error('insurance_id_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Row 2: Remarks full width --}}
                <div class="form-group mb-3">
                    <label for="insurance_remarks">Insurance Archive / Remarks</label>
                    <textarea class="form-control @error('insurance_remarks') is-invalid @enderror" id="insurance_remarks" name="insurance_remarks" rows="3"
                              placeholder="e.g. Policy closed Jan 2026, patient moved to provider X...">{{ old('insurance_remarks', $patient->insurance->insurance_remarks) }}</textarea>
                    @error('insurance_remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- <input type="hidden" name="insurance_action" value="update"> -->
                <input type="hidden" name="insurance_id" value="{{ $patient->insurance->InsuranceID }}">
            </div>
            
        @else
            
            <p id="insurance-status">This patient is currently registered without an insurance policy.</p>

            <button type="button" class="btn btn-info btn-sm mb-3" id="add-insurance-btn"
                    onclick="document.getElementById('insurance-fields-add').style.display='block'; this.style.display='none';">
                Add Insurance Policy Now
            </button>

            <div id="insurance-fields-add" style="display: none;">
                
                    <h5 class="mb-3">Register New Policy</h5>
                    
                    <div class="row"> 
                        <div class="col-md-4 form-group mb-3">
                            <label for="insurance_provider_new">Insurance Provider Name</label>
                            <select id="insurance_provider_new" name="insurance_provider" 
                                    class="form-control @error('insurance_provider') is-invalid @enderror">
                                <option value="">-- Select Provider --</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->provider_name }}" 
                                        {{ (old('insurance_provider', $insuranceRecord->insurance_provider ?? '') == $provider->provider_name) ? 'selected' : '' }}>
                                        {{ $provider->provider_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('insurance_provider') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>

                        <div class="col-md-4 form-group mb-3">
                            <label for="insurance_no_new">Policy Number (insurance_no)</label>
                            <input type="tel" class="form-control @error('insurance_no') is-invalid @enderror" id="insurance_no_new" name="insurance_no" pattern="[0-9]*"
                                   value="{{ old('insurance_no') }}">
                            @error('insurance_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="col-md-4 form-group mb-3">
                            <label for="insurance_id_no_new">Insurance ID (insurance_id_no)</label>
                            <input type="text" class="form-control @error('insurance_id_no') is-invalid @enderror" id="insurance_id_no_new" name="insurance_id_no"
                                   value="{{ old('insurance_id_no') }}">
                            @error('insurance_id_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12 form-group mb-3">
                            <label for="insurance_remarks_new">Insurance Archive / Remarks</label>
                            <textarea class="form-control @error('insurance_remarks') is-invalid @enderror" id="insurance_remarks_new" name="insurance_remarks" rows="3"
                                      placeholder="e.g. Policy closed Jan 2026, patient moved to provider X...">{{ old('insurance_remarks') }}</textarea>
                            @error('insurance_remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <input type="hidden" name="insurance_action" value="create">
            </div>
            
        @endif

        <button type="submit" class="btn btn-primary mt-3">Update Patient Details</button>
        
    </form>
    
    {{-- System fields omitted for brevity --}}
    <div class="mt-5 border-top pt-3">
        <p><strong>System ID (acc_no):</strong> {{ $patient->acc_no }}</p>
        <p><strong>Record Opened:</strong> {{ $patient->opened }}</p>
        <p><strong>Created At:</strong> {{ $patient->created_at }}</p>
        <p><strong>Last Updated At:</strong> {{ $patient->updated_at }}</p>
    </div>

</div>

@endsection