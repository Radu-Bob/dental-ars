@extends('layouts.app')

@section('title', 'Treatment Report / Invoice')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

<div class="p-6 bg-white rounded-xl shadow-lg max-w-2xl mx-auto mt-6">

    <h1 class="text-2xl font-bold mb-2 text-gray-800">Treatment Report / Invoice</h1>
    <p class="text-sm text-gray-500 mb-8">Choose the document type you want to create.</p>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

        {{-- INVOICE card --}}
        <a href="{{ route('reports.invoice') }}"
           class="group flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl hover:border-clinic hover:shadow-md transition">
            <div class="text-3xl">🧾</div>
            <div>
                <div class="text-lg font-bold text-gray-800 group-hover:text-clinic transition">Invoice</div>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Create a financial document for a patient — Invoice, Receipt, or Pro-forma.
                    Includes itemised line-items with quantities, unit prices, and a grand total.
                </p>
            </div>
            <div class="mt-auto text-sm font-medium text-clinic opacity-0 group-hover:opacity-100 transition">
                Open Invoice →
            </div>
        </a>

        {{-- REPORT / PRESCRIPTION card --}}
        <a href="{{ route('reports.clinical_report') }}"
           class="group flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl hover:border-clinic hover:shadow-md transition">
            <div class="text-3xl">📋</div>
            <div>
                <div class="text-lg font-bold text-gray-800 group-hover:text-clinic transition">Report / Prescription</div>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Compose a clinical report or a prescription for a patient.
                    Free-text format — no pricing table.
                </p>
            </div>
            <div class="mt-auto text-sm font-medium text-clinic opacity-0 group-hover:opacity-100 transition">
                Open Report →
            </div>
        </a>

    </div>

</div>

@endsection
