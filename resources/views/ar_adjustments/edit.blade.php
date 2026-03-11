@extends('layouts.app')

@section('title', 'Edit AR Adjustment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">EDIT AR ADJUSTMENT</h1>
            <div>
                <span class="px-3 py-1 rounded text-sm bg-gray-700 text-gray-300">Reference: {{ $adjustment->reference_number }}</span>
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

        <form action="{{ route('ar_adjustments.update', $adjustment->id) }}" method="POST" id="adjustmentForm">
            @csrf
            @method('PUT')

            <!-- Transaction Date & Reference Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Transaction Date: <span class="text-red-400">*</span></label>
                    <input type="date" name="transaction_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('transaction_date', $adjustment->transaction_date->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Reference Number: <span class="text-red-400">*</span></label>
                    <input type="text" name="reference_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_number', $adjustment->reference_number) }}" required>
                </div>
            </div>

            <!-- Transaction Type & Customer Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Transaction Type: <span class="text-red-400">*</span></label>
                    <select name="transaction_type" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        <option value="">-- Select Transaction Type --</option>
                        <option value="atd" {{ old('transaction_type', $adjustment->transaction_type) == 'atd' ? 'selected' : '' }}>ATD (Authority to Debit)</option>
                        <option value="offset" {{ old('transaction_type', $adjustment->transaction_type) == 'offset' ? 'selected' : '' }}>Offset</option>
                        <option value="credit_memo" {{ old('transaction_type', $adjustment->transaction_type) == 'credit_memo' ? 'selected' : '' }}>Credit Memo</option>
                        <option value="debit_memo" {{ old('transaction_type', $adjustment->transaction_type) == 'debit_memo' ? 'selected' : '' }}>Debit Memo</option>
                        <option value="adjustment" {{ old('transaction_type', $adjustment->transaction_type) == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        <option value="write_off" {{ old('transaction_type', $adjustment->transaction_type) == 'write_off' ? 'selected' : '' }}>Write-off</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Customer Name: <span class="text-red-400">*</span></label>
                    <input type="text" name="customer_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('customer_name', $adjustment->customer_name) }}" required>
                </div>
            </div>

            <!-- Customer Code & Branch -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Customer Code:</label>
                    <input type="text" name="customer_code" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('customer_code', $adjustment->customer_code) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Branch:</label>
                    <input type="text" name="branch" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('branch', $adjustment->branch) }}">
                </div>
            </div>

            <!-- DR & Invoice Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DR Number:</label>
                    <input type="text" name="dr_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('dr_no', $adjustment->dr_no) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Invoice Number:</label>
                    <input type="text" name="invoice_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('invoice_number', $adjustment->invoice_number) }}">
                </div>
            </div>

            <!-- Amount & GL Account -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Amount: <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-400 text-lg">₱</span>
                        <input type="text" name="amount" id="amountInput" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 pl-8 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount', ($adjustment->is_decrease ? '-' : '') . abs($adjustment->amount)) }}" required>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Current: <span class="{{ $adjustment->is_decrease ? 'text-red-400' : 'text-green-400' }}">{{ ($adjustment->is_decrease ? '-' : '+') }}₱{{ number_format(abs($adjustment->amount), 2) }}</span></p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">GL Account: <span class="text-red-400">*</span></label>
                    <input type="text" name="gl_account" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('gl_account', $adjustment->gl_account) }}" required>
                </div>
            </div>

            <!-- Signed By -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">Signed By: <span class="text-red-400">*</span></label>
                <input type="text" name="signed_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('signed_by', $adjustment->signed_by) }}" required>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">Remarks:</label>
                <textarea name="remarks" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('remarks', $adjustment->remarks) }}</textarea>
            </div>

            <!-- Info Box -->
            <div class="mb-6 p-4 bg-blue-900/20 border border-blue-700 rounded">
                <p class="text-blue-300 text-sm"><i class="fas fa-info-circle mr-2"></i><strong>Created:</strong> {{ $adjustment->created_at->format('F d, Y h:i A') }} by {{ $adjustment->created_by }}</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('ar_adjustments.show', $adjustment->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Adjustment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
