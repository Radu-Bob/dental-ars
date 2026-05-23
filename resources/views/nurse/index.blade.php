@extends('layouts.app')

@section('left_content')
<div class="bg-white p-6 rounded-xl shadow-md space-y-4">
    <h2 class="text-xl font-semibold text-gray-800">Find a Patient</h2>

    <form action="{{ route('nurse.patients.index') }}" method="GET">
        <div class="mb-4">
            <label for="search_input" class="block text-sm font-medium text-gray-700 mb-2">Search by ID or Name:</label>
            <input type="text" id="search_input" name="q"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2"
                style="--tw-ring-color: var(--clinic-primary); border-color: var(--clinic-primary);"
                placeholder="e.g., 101 or John Smith"
                value="{{ request('q') }}">
        </div>
        <button type="submit" class="w-full btn-clinic-standard font-semibold py-2 rounded-lg shadow-md">
            Search
        </button>
    </form>

    <div class="mt-6">
        <a href="{{ route('nurse.patients.index') }}"
            class="w-full text-center block font-semibold py-2 rounded-lg transition duration-300 btn-clinic-grey">
            View All Records
        </a>
    </div>

    <div class="mt-2">
        <a href="{{ route('patients.register') }}"
            class="w-full text-center block font-semibold py-2 rounded-lg shadow-md btn-clinic-standard">
            Register New Patient
        </a>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-md mt-4">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Recently Registered</h2>
    <ul class="list-unstyled space-y-2">
        @forelse($recentPatients as $recent)
            <li class="border-b border-gray-200 last:border-b-0 py-1">
                <a href="{{ route('nurse.patients.show', ['patient_id' => $recent->patient_id]) }}"
                    class="flex justify-between items-center text-gray-700 transition duration-300"
                    onmouseover="this.style.color='var(--clinic-bg-light)'"
                    onmouseout="this.style.color=''">
                    <span class="font-bold uppercase">{{ $recent->name }}</span>
                </a>
            </li>
        @empty
            <li class="text-gray-500">No recent patients found.</li>
        @endforelse
    </ul>
</div>
@endsection

@section('content')

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-4 shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-4 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <h2 class="text-2xl font-bold text-gray-800 mb-6">Patient Records</h2>

    @if(isset($patients) && $patients->isNotEmpty())
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('nurse.patients.index', ['q' => request('q'), 'sort_by' => 'name', 'sort_order' => request('sort_order', 'asc') == 'asc' ? 'desc' : 'asc']) }}"
                                class="flex items-center">
                                Name
                                @if(request('sort_by') === 'name')
                                    <span class="ml-1">@if(request('sort_order') === 'asc') &darr; @else &uarr; @endif</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('nurse.patients.index', ['q' => request('q'), 'sort_by' => 'tel', 'sort_order' => request('sort_order', 'asc') == 'asc' ? 'desc' : 'asc']) }}"
                                class="flex items-center">
                                Telephone
                                @if(request('sort_by') === 'tel')
                                    <span class="ml-1">@if(request('sort_order') === 'asc') &darr; @else &uarr; @endif</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clinic</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($patients as $patient)
                        <tr>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                <a href="{{ route('nurse.patients.show', ['patient_id' => $patient->patient_id]) }}"
                                    class="flex justify-between items-center text-gray-700 transition duration-300"
                                    onmouseover="this.style.color='var(--clinic-primary)'"
                                    onmouseout="this.style.color=''">
                                    <span class="font-bold uppercase">{{ $patient->name }}</span>
                                </a>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $patient->tel }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($patient->active == 2) DSM
                                @elseif($patient->active == 1) ARS
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6 flex justify-center bg-gray-100 rounded-lg p-2 text-sm text-gray-700">
            {{ $patients->appends(request()->input())->links('pagination.custom-pagination') }}
        </div>
    @else
        <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg">
            <p>No patients found. Please try a different search or click "View All Records".</p>
        </div>
    @endif

@endsection
