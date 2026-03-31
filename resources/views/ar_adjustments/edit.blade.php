@extends('layouts.app')

@section('title', 'Edit AR Adjustment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">EDIT AR ADJUSTMENT</h1>
            <div>
                <span class="px-3 py-1 rounded text-sm bg-gray-700 text-gray-500">Reference: {{ $adjustment->reference_number }}</span>
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
                    <label class="block font-semibold text-gray-500 mb-2">Transaction Date: <span class="text-red-700">*</span></label>
                    <input type="date" name="transaction_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('transaction_date', $adjustment->transaction_date->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Reference Number: <span class="text-red-700">*</span></label>
                    <input type="text" name="reference_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_number', $adjustment->reference_number) }}" required>
                </div>
            </div>

            <!-- Transaction Type & Customer Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Transaction Type: <span class="text-red-700">*</span></label>
                    <select name="transaction_type" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        <option value="">-- Select Transaction Type --</option>
                        <option value="debit_memo" {{ old('transaction_type', $adjustment->transaction_type) == 'debit_memo' ? 'selected' : '' }}>Debit Memo</option>
                        <option value="credit_memo" {{ old('transaction_type', $adjustment->transaction_type) == 'credit_memo' ? 'selected' : '' }}>Credit Memo</option>
                        <option value="sales_return_allowances" {{ old('transaction_type', $adjustment->transaction_type) == 'sales_return_allowances' ? 'selected' : '' }}>Sales Return and Allowances</option>
                        <option value="price_adjustment" {{ old('transaction_type', $adjustment->transaction_type) == 'price_adjustment' ? 'selected' : '' }}>Price Adjustment</option>
                        <option value="rebates" {{ old('transaction_type', $adjustment->transaction_type) == 'rebates' ? 'selected' : '' }}>Rebates</option>
                        <option value="distribution_fees" {{ old('transaction_type', $adjustment->transaction_type) == 'distribution_fees' ? 'selected' : '' }}>Distribution Fees</option>
                        <option value="penalty" {{ old('transaction_type', $adjustment->transaction_type) == 'penalty' ? 'selected' : '' }}>Penalty</option>
                        <option value="promotional_expenses" {{ old('transaction_type', $adjustment->transaction_type) == 'promotional_expenses' ? 'selected' : '' }}>Promotional Expenses</option>
                        <option value="small_balance_adjustment" {{ old('transaction_type', $adjustment->transaction_type) == 'small_balance_adjustment' ? 'selected' : '' }}>Small balance adjustment</option>
                        <option value="atd" {{ old('transaction_type', $adjustment->transaction_type) == 'atd' ? 'selected' : '' }}>ATD</option>
                        <option value="offset" {{ old('transaction_type', $adjustment->transaction_type) == 'offset' ? 'selected' : '' }}>Offset</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Customer Name: <span class="text-red-700">*</span></label>
                    <input type="text" name="customer_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('customer_name', $adjustment->customer_name) }}" required>
                </div>
            </div>

            <!-- Customer Code & Branch -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Customer Code:</label>
                    <input type="text" name="customer_code" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('customer_code', $adjustment->customer_code) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Branch:</label>
                    <input type="text" name="branch" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('branch', $adjustment->branch) }}">
                </div>
            </div>

            <!-- DR & Invoice Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">DR Number:</label>
                    <input type="text" name="dr_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('dr_no', $adjustment->dr_no) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Invoice Number:</label>
                    <input type="text" name="invoice_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('invoice_number', $adjustment->invoice_number) }}">
                </div>
            </div>

            <!-- Amount & GL Account -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Amount: <span class="text-red-700">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500 text-lg">₱</span>
                        <input type="text" name="amount" id="amountInput" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 pl-8 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount', ($adjustment->is_decrease ? '-' : '') . abs($adjustment->amount)) }}" required>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Current: <span class="{{ $adjustment->is_decrease ? 'text-red-700' : 'text-green-700' }}">{{ ($adjustment->is_decrease ? '-' : '+') }}₱{{ number_format(abs($adjustment->amount), 2) }}</span></p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">GL Account: <span class="text-red-700">*</span></label>
                    <div class="relative">
                        <input type="hidden" name="gl_account_id" id="glAccountId" value="{{ old('gl_account_id', $adjustment->gl_account_id) }}">
                        <input type="hidden" name="gl_account" id="glAccountCode" value="{{ old('gl_account', $adjustment->gl_account) }}">
                        @php
                            $displayValue = '';
                            if ($adjustment->glAccount) {
                                $displayValue = ($adjustment->glAccount->account_code ?? '') . ' - ' . ($adjustment->glAccount->account_name ?? '');
                            } else if ($adjustment->gl_account) {
                                $displayValue = $adjustment->gl_account;
                            }
                        @endphp
                        <input type="text" id="glAccountSearch" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Search GL Account (code/name)" value="{{ old('gl_account', $displayValue) }}" required>
                        <div id="glAccountDropdown" class="absolute top-full left-0 right-0 bg-gray-900 border border-gray-700 rounded max-h-48 overflow-y-auto z-10 hidden mt-1">
                            <!-- Dropdown options will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signed By -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">Signed By: <span class="text-red-700">*</span></label>
                <input type="text" name="signed_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('signed_by', $adjustment->signed_by) }}" required>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">Remarks:</label>
                <textarea name="remarks" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('remarks', $adjustment->remarks) }}</textarea>
            </div>

            <!-- Info Box -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-700 rounded">
                <p class="text-blue-700 text-sm"><i class="fas fa-info-circle mr-2"></i><strong>Created:</strong> {{ $adjustment->created_at->format('F d, Y h:i A') }} by {{ $adjustment->created_by }}</p>
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

<script>
// GL Account searchable dropdown
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('glAccountSearch');
    const dropdown = document.getElementById('glAccountDropdown');
    const idInput = document.getElementById('glAccountId');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length === 0) {
                dropdown.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(async function() {
                try {
                    const response = await fetch(`/ar-adjustments/gl-accounts?search=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success && data.accounts.length > 0) {
                        dropdown.innerHTML = data.accounts.map(account => `
                            <div class="px-3 py-2 hover:bg-purple-100 cursor-pointer text-white" onclick="selectGlAccount(${account.id}, '${account.display.replace(/'/g, "\\'")}', '${(account.code || '').replace(/'/g, "\\'")}')">
                                <div class="font-semibold">${account.display}</div>
                                <div class="text-xs text-gray-500">${account.fs_line_item || 'No FS Item'}</div>
                            </div>
                        `).join('');
                        dropdown.classList.remove('hidden');
                    } else {
                        dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500">No GL accounts found</div>';
                        dropdown.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error fetching GL accounts:', error);
                }
            }, 300);
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== searchInput) {
                dropdown.classList.add('hidden');
            }
        });
    }
});

function selectGlAccount(id, display, code) {
    document.getElementById('glAccountId').value = id;
    document.getElementById('glAccountCode').value = code;
    document.getElementById('glAccountSearch').value = display;
    document.getElementById('glAccountDropdown').classList.add('hidden');
}
</script>
@endsection
