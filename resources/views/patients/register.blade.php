@extends('layouts.app')

@section('title', 'Register New Patient')

{{-- ============================================================ --}}
{{-- LEFT CONTENT: Date/Time + Instructions                       --}}
{{-- ============================================================ --}}
@section('left_content')
<div class="space-y-4">

    {{-- Date & Time Card --}}
    <div class="bg-white p-4 rounded-xl shadow-lg">
        <h2 class="text-sm font-bold text-gray-700 border-b pb-2 mb-3">Session Info</h2>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Date</p>
        <p class="text-sm font-semibold text-gray-800 mb-3">{{ now()->format('l, d M Y') }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Time</p>
        <p class="text-sm font-semibold text-gray-800" id="register-live-clock">--:--:--</p>
    </div>

    {{-- Instructions Box — styled to match Insurance Policy enclosure in patient show view --}}
    {{-- Instructions Box — Refined Professional Style --}}
    
        <div class="relative p-4 rounded-xl border-l-4 border-clinic"
             style="background: linear-gradient(135deg, var(--clinic-tint), white)">
            <div class="flex items-center mb-3">
                <svg class="w-5 h-5 text-clinic mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-sm font-bold uppercase tracking-wider text-clinic-bold">Submission Protocol</h3>
            </div>

            <ul class="text-xs text-gray-600 space-y-3">
                <li class="flex items-start">
                    <span class="text-clinic mr-2">•</span>
                    <span>Verify the <strong class="text-gray-900 font-semibold underline" style="text-decoration-color: var(--clinic-tint)">email address</strong> to prevent duplicate patient records.</span>
                </li>
                <li class="flex items-start">
                    <span class="text-clinic mr-2">•</span>
                    <span>Ensure the <strong class="text-gray-900 font-semibold">Gender</strong> selection is captured accurately.</span>
                </li>
                <li class="flex items-start">
                    <span class="text-clinic mr-2">•</span>
                    <span>The <strong class="text-gray-900 font-semibold">Primary Clinic Location</strong> is locked to your current site; modify only with authorisation.</span>
                </li>
                <li class="flex items-start">
                    <span class="text-clinic mr-2">•</span>
                    <span class="bg-clinic-tint px-1 rounded text-clinic-bold italic">For Insurance Claims:</span>
                    <span class="ml-1 text-gray-700 font-medium italic">Complete every field in the Insurance section to ensure billing compliance.</span>
                </li>
            </ul>
        </div>
    

</div>

<script>
    (function () {
        function updateClock() {
            const el = document.getElementById('register-live-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('en-GB');
        }
        updateClock();
        setInterval(updateClock, 1000);
    })();
</script>
@endsection

{{-- ============================================================ --}}
{{-- MAIN CONTENT: New Patient Form                               --}}
{{-- ============================================================ --}}
@section('content')

<div class="bg-white p-6 rounded-xl shadow-lg">

    <h1 class="text-2xl font-bold text-gray-800 mb-1">Register New Patient</h1>
    <p class="text-sm text-gray-500 mb-6">Required fields are marked with an asterisk (*).</p>

    {{-- Session error --}}
    @if (session('error'))
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-4 mb-4 text-sm">
            <strong>System Error:</strong> {{ session('error') }}
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-4 mb-6 text-sm">
            <strong>Please fix the following errors:</strong>
            <ul class="list-disc list-inside mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('patients.saveNewPatient') }}" method="POST">
        @csrf

        {{-- ── PERSONAL DETAILS ────────────────────────────────── --}}
        <h2 class="text-base font-bold text-clinic-bold border-b border-gray-200 pb-2 mb-4">Personal Details</h2>

        {{-- Row 1: Name --}}
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Patient Name (*)</label>
            <input type="text" id="name" name="name" required value="{{ old('name') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 @error('name') border-red-400 @enderror">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Row 2: Date of Birth + Gender --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth (DD/MM/YYYY)</label>
                <input type="text" id="date_of_birth" name="date_of_birth"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 flatpickr-input @error('date_of_birth') border-red-400 @enderror"
                       placeholder="DD/MM/YYYY" data-date-format="d/m/Y" value="{{ old('date_of_birth') }}">
                @error('date_of_birth') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select id="gender" name="gender"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 @error('gender') border-red-400 @enderror">
                    <option value="">Select Gender</option>
                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                </select>
                @error('gender') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── ADDRESS & CONTACT ───────────────────────────────── --}}
        <h2 class="text-base font-bold text-clinic-bold border-b border-gray-200 pb-2 mb-4 mt-6">Address &amp; Contact</h2>

        {{-- Row 3: Telephone + Email --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="tel" class="block text-sm font-medium text-gray-700 mb-1">Telephone</label>
                <input type="text" id="tel" name="tel" value="{{ old('tel') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 @error('email') border-red-400 @enderror">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Row 4: Location + Town + PO Box --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" id="location" name="location" value="{{ old('location') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2">
            </div>
            <div>
                <label for="town" class="block text-sm font-medium text-gray-700 mb-1">Town</label>
                <input type="text" id="town" name="town" value="{{ old('town') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2">
            </div>
            <div>
                <label for="pobox" class="block text-sm font-medium text-gray-700 mb-1">P.O. Box</label>
                <input type="text" id="pobox" name="pobox" value="{{ old('pobox') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2">
            </div>
        </div>

        {{-- ── OTHER DETAILS ────────────────────────────────────── --}}
        <h2 class="text-base font-bold text-clinic-bold border-b border-gray-200 pb-2 mb-4 mt-6">Other Details</h2>

        {{-- Row 5: Occupation (2/3) + Primary Clinic (1/3) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="md:col-span-2">
                <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                <input type="text" id="occupation" name="occupation" value="{{ old('occupation') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2">
            </div>
            <div>
                <label for="active" class="block text-sm font-medium text-gray-700 mb-1">Primary Clinic Location (*)</label>
                <select id="active" name="active" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 @error('active') border-red-400 @enderror">
                    <option value="">-- Select Clinic --</option>
                    <option value="1" {{ old('active') == '1' ? 'selected' : '' }}>Arusha (Code 1)</option>
                    <option value="2" {{ (old('active') == '2' || old('active') === null) ? 'selected' : '' }}>Dar es Salaam (Code 2)</option>
                </select>
                @error('active') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Row 6: Remarks --}}
        <div class="mb-6">
            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2">{{ old('remarks') }}</textarea>
        </div>

        {{-- ── INSURANCE SECTION ────────────────────────────────── --}}
        <div class="border border-orange-400 rounded-lg p-4 bg-gray-50">

            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-orange-700">Insurance Information</h2>
                <label class="flex items-center gap-2 cursor-pointer select-none text-sm text-gray-700 font-medium">
                    <input type="checkbox" id="insurance-toggle" class="w-4 h-4 accent-orange-500"
                           {{ (old('insurance_provider') || old('insurance_no')) ? 'checked' : '' }}>
                    This patient has health insurance
                </label>
            </div>

            <div id="insurance-fields" style="{{ (old('insurance_provider') || old('insurance_no')) ? '' : 'display:none;' }}">

                <p class="text-xs text-orange-600 italic mb-4">Fill in all available details if the patient intends to use their policy for treatments.</p>

                {{-- Provider --}}
                <div class="mb-4">
                    <label for="insurance_provider" class="block text-sm font-medium text-gray-700 mb-1">Insurance Provider</label>
                    <select id="insurance_provider" name="insurance_provider"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 @error('insurance_provider') border-red-400 @enderror">
                        <option value="">-- Select Provider --</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->provider_name }}"
                                {{ old('insurance_provider') == $provider->provider_name ? 'selected' : '' }}>
                                {{ $provider->provider_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('insurance_provider') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Policy Number + Insurance ID --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="insurance_no" class="block text-sm font-medium text-gray-700 mb-1">Policy Number</label>
                        <input type="tel" id="insurance_no" name="insurance_no" pattern="[0-9]*"
                               value="{{ old('insurance_no') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 @error('insurance_no') border-red-400 @enderror">
                        @error('insurance_no') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="insurance_id_no" class="block text-sm font-medium text-gray-700 mb-1">Insurance ID / Membership No.</label>
                        <input type="text" id="insurance_id_no" name="insurance_id_no"
                               value="{{ old('insurance_id_no') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2">
                    </div>
                </div>

                {{-- Insurance Remarks --}}
                <div>
                    <label for="insurance_remarks" class="block text-sm font-medium text-gray-700 mb-1">Insurance Remarks</label>
                    <textarea id="insurance_remarks" name="insurance_remarks" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2"
                              placeholder="e.g. Corporate plan via employer, individual policy...">{{ old('insurance_remarks') }}</textarea>
                </div>

            </div>{{-- /#insurance-fields --}}
        </div>{{-- /.insurance-border-box --}}

        {{-- Submit --}}
        <div class="mt-6">
            <button type="submit" class="btn-clinic-primary px-6 py-2 rounded-lg font-semibold shadow-md">
                Create New Patient
            </button>
        </div>

    </form>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr("#date_of_birth", {
                dateFormat: "d/m/Y",
                allowInput: true,
                placeholder: "DD/MM/YYYY"
            });

            const toggle = document.getElementById('insurance-toggle');
            const fields = document.getElementById('insurance-fields');
            toggle.addEventListener('change', function () {
                fields.style.display = this.checked ? 'block' : 'none';
            });
        });
    </script>
@endpush

@endsection
