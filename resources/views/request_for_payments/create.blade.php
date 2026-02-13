@extends('layouts.app')

@section('title', 'Create Request for Payment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">REQUEST FOR PAYMENT</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">RFP NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $rfpNo }}</span>
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

        <form action="{{ route('request_for_payments.store') }}" method="POST" id="rfpForm">
            @csrf

            <!-- Company Selection -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-300 mb-3">SELECT COMPANY: <span class="text-red-400">*</span></label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($companies as $company)
                        <label class="flex items-center p-3 bg-gray-800 border border-gray-700 rounded hover:bg-gray-700 cursor-pointer transition">
                            <input type="radio" name="company" value="{{ $company }}" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500" {{ old('company', $selectedPO->company ?? '') == $company ? 'checked' : '' }} required>
                            <span class="ml-3 text-white">{{ $company }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Payment Methods & Dates Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column - Payment Methods -->
                <div class="bg-gray-900 border border-gray-700 rounded p-4">
                    <label class="block font-semibold text-gray-300 mb-3">PAYMENT METHODS:</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="managers_check" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Manager's Check</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="regular_check" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Regular Check</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="wire_transfer" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Wire Transfer</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="fund_transfer" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Fund Transfer</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="pdc" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">PDC (Post-Dated Check)</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="cash" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Cash</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="auto_debit" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Auto Debit</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="others" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Others</span>
                        </label>
                    </div>
                </div>

                <!-- Right Column - Dates and Reference Numbers -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DATE: <span class="text-red-400">*</span></label>
                        <input type="date" name="date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DUE DATE:</label>
                        <input type="date" name="due_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('due_date') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">RFP#:</label>
                        <input type="text" readonly class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-gray-400" value="{{ $rfpNo }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">LINKED PO:</label>
                        <input type="hidden" name="purchase_order_id" value="{{ old('purchase_order_id', $selectedPO->id ?? '') }}">
                        @if($selectedPO)
                            <div class="p-3 bg-green-900/20 border border-green-700 rounded text-green-300">
                                <i class="fas fa-link mr-2"></i>{{ $selectedPO->po_no }}
                            </div>
                        @else
                            <div class="p-3 bg-gray-900 border border-gray-700 rounded text-gray-400">
                                No PO linked
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Main Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYEE: <span class="text-red-400">*</span></label>
                    <input type="text" name="payee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payee', $selectedPO->supplier ?? '') }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">AMOUNT: <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-400">₱</span>
                        <input type="number" step="0.01" name="amount" class="w-full bg-gray-900 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount') }}" required>
                    </div>
                </div>
            </div>

            <!-- Particulars -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PARTICULARS:</label>
                <textarea name="particulars" rows="5" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter payment particulars...">{{ old('particulars') }}</textarea>
            </div>

            <!-- Bank -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">BANK/S:</label>
                <input type="text" name="bank" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank') }}" placeholder="Bank name and account details">
            </div>

            <!-- APV and CV Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">APV NO. (Account Payable Voucher):</label>
                    <input type="text" name="apv_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('apv_no') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CV NO. (Check Voucher):</label>
                    <input type="text" name="cv_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('cv_no') }}">
                </div>
            </div>

            <!-- Requestor and Checker -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REQUESTED BY (Requestor):</label>
                    <input type="text" name="requested_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requested_by') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CHECKED BY (Department Head):</label>
                    <input type="text" name="checked_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('checked_by') }}">
                </div>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-700 rounded">
                    <div class="bg-gray-900 border-b border-gray-700 px-4 py-2 text-center font-semibold text-yellow-400">
                        <i class="fas fa-exclamation-triangle mr-2"></i>FOR FINANCE USE ONLY
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By (Php 50,000 above):</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-700 text-gray-300 text-xs italic">
                                <td class="border border-gray-700 px-4 py-2 text-center">Finance Manager</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">CFO / President</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('request_for_payments.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Request for Payment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
