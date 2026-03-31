@extends('layouts.app')

@section('title', 'Edit Cash Advance Request')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT CASH ADVANCE REQUEST</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">CAR NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $car->car_no }}</span>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('cash_advance_requests.update', $car->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Main Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYEE: <span class="text-red-700">*</span></label>
                    <input type="text" name="payee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payee', $car->payee) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DEPARTMENT: <span class="text-red-700">*</span></label>
                    <input type="text" name="department" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('department', $car->department) }}" required>
                </div>
            </div>

            <!-- Purpose -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PURPOSE: <span class="text-red-700">*</span></label>
                <textarea name="purpose" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter purpose of cash advance..." required>{{ old('purpose', $car->purpose) }}</textarea>
            </div>

            <!-- Dates and Amount -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DATE REQUESTED: <span class="text-red-700">*</span></label>
                    <input type="date" name="date_requested" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_requested', $car->date_requested ? $car->date_requested->format('Y-m-d') : '') }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DATE NEEDED: <span class="text-red-700">*</span></label>
                    <input type="date" name="date_needed" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_needed', $car->date_needed ? $car->date_needed->format('Y-m-d') : '') }}" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">AMOUNT ADVANCED: <span class="text-red-700">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-gray-300">&#8369;</span>
                    <input type="number" step="0.01" name="amount_advanced" class="w-full bg-gray-900 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount_advanced', $car->amount_advanced) }}" required>
                </div>
            </div>

            <!-- Signature Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REQUESTED BY:</label>
                    <input type="text" name="requested_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requested_by', $car->requested_by) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CHECKED BY (Department Head):</label>
                    <input type="text" name="checked_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('checked_by', $car->checked_by) }}">
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">REMARKS:</label>
                <textarea name="remarks" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Optional remarks...">{{ old('remarks', $car->remarks) }}</textarea>
            </div>

            <!-- Fine Print -->
            <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
                <p class="text-gray-300 text-sm italic">
                    I hereby acknowledge receipt of the above sum of money and hereby agree to liquidate in 5 calendar days after the cash advance serve its purpose and provide receipts to document the expenditures.
                </p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('cash_advance_requests.show', $car->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Cash Advance Request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
