@extends('layouts.app')

@section('title', 'Edit Check Voucher')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">EDIT CHECK VOUCHER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-500">CV NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-50 border border-gray-200 text-gray-800 rounded">{{ $voucher->cv_no }}</span>
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

        @if($voucher->accountsPayableInvoice)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded">
            <div class="flex items-center text-green-700 mb-2">
                <i class="fas fa-link mr-2"></i>
                <span class="font-semibold">Linked to APV: {{ $voucher->accountsPayableInvoice->apv_no }}</span>
            </div>
            <div class="text-sm text-gray-500">
                Vendor: {{ $voucher->accountsPayableInvoice->vendor_name }} | Grand Total: {{ $voucher->accountsPayableInvoice->currency }} {{ number_format($voucher->accountsPayableInvoice->grand_total, 2) }}
            </div>
        </div>
        @endif

        <form action="{{ route('check_vouchers.update', $voucher->id) }}" method="POST" id="cvForm">
            @csrf
            @method('PUT')

            <!-- CV Date and Check Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">CV DATE: <span class="text-red-700">*</span></label>
                    <input type="date" name="cv_date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('cv_date', $voucher->cv_date->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">CHECK DATE: <span class="text-red-700">*</span></label>
                    <input type="date" name="check_date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('check_date', $voucher->check_date->format('Y-m-d')) }}" required>
                </div>
            </div>

            <!-- Supplier Information -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">SUPPLIER INFORMATION</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">SUPPLIER CODE:</label>
                        <input type="text" name="supplier_code" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_code', $voucher->supplier_code) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">SUPPLIER NAME: <span class="text-red-700">*</span></label>
                        <input type="text" name="supplier_name" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_name', $voucher->supplier_name) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-500 mb-2">SUPPLIER ADDRESS:</label>
                        <textarea name="supplier_address" rows="2" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('supplier_address', $voucher->supplier_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">SUPPLIER TIN:</label>
                        <input type="text" name="supplier_tin" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_tin', $voucher->supplier_tin) }}">
                    </div>
                </div>
            </div>

            <!-- Check Details -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">CHECK DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">CHECK NO:</label>
                        <input type="text" name="check_no" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('check_no', $voucher->check_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">BANK (G/L Account): <span class="text-red-700">*</span></label>
                        <input type="hidden" name="gl_account_id" id="cv_gl_account_id" value="{{ old('gl_account_id', $voucher->gl_account_id) }}">
                        <input type="hidden" name="bank" id="cv_bank_name" value="{{ old('bank', $voucher->bank) }}">
                        <div class="relative">
                            <input type="text" id="cv_gl_search" autocomplete="off"
                                   class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   value="{{ old('bank', $voucher->bank) }}" placeholder="Search G/L Account (e.g., CIB - Peso - BPI...)">
                            <div id="cv_gl_dropdown" class="hidden absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded shadow-lg max-h-56 overflow-y-auto"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">BRANCH:</label>
                        <input type="text" name="branch" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('branch', $voucher->branch) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">CHECK AMOUNT: <span class="text-red-700">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">₱</span>
                            <input type="number" step="0.01" name="check_amount" class="w-full bg-white border border-gray-200 rounded pl-8 pr-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('check_amount', $voucher->check_amount) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">PAYMENT DETAILS</h3>
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 border border-gray-200 text-gray-500">Date</th>
                                <th class="px-4 py-2 border border-gray-200 text-gray-500">Type</th>
                                <th class="px-4 py-2 border border-gray-200 text-gray-500">Reference No.</th>
                                <th class="px-4 py-2 border border-gray-200 text-gray-500">APV No.</th>
                                <th class="px-4 py-2 border border-gray-200 text-gray-500">Paid Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 border border-gray-200">
                                    <input type="date" name="payment_date" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ old('payment_date', $voucher->payment_date ? $voucher->payment_date->format('Y-m-d') : '') }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-200">
                                    <input type="text" name="payment_type" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ old('payment_type', $voucher->payment_type) }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-200">
                                    <input type="text" name="reference_no" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ old('reference_no', $voucher->reference_no) }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-200">
                                    <input type="text" name="apv_no" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ old('apv_no', $voucher->apv_no) }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-200">
                                    <input type="number" step="0.01" name="paid_amount" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ old('paid_amount', $voucher->paid_amount) }}" required>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Particulars -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">PARTICULARS: <span class="text-red-700">*</span></label>
                <textarea name="particulars" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" required>{{ old('particulars', $voucher->particulars) }}</textarea>
            </div>

            <!-- Journal Entry -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">JOURNAL ENTRY</h3>
                <div id="journalEntriesContainer">
                    <div class="overflow-x-auto">
                        <table class="w-full border border-gray-200 mb-4">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 border border-gray-200 text-gray-500">Account Code</th>
                                    <th class="px-4 py-2 border border-gray-200 text-gray-500">Account Name</th>
                                    <th class="px-4 py-2 border border-gray-200 text-gray-500">Debit</th>
                                    <th class="px-4 py-2 border border-gray-200 text-gray-500">Credit</th>
                                    <th class="px-4 py-2 border border-gray-200 text-gray-500">
                                        <button type="button" id="addJournalEntry" class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700">
                                            <i class="fas fa-plus"></i> Add Row
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="journalEntriesBody">
                                @if($voucher->journal_entries && count($voucher->journal_entries) > 0)
                                    @foreach($voucher->journal_entries as $index => $entry)
                                    <tr class="journal-entry-row">
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="text" name="journal_entries[{{ $index }}][account_code]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ $entry['account_code'] ?? '' }}">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="text" name="journal_entries[{{ $index }}][account_name]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ $entry['account_name'] ?? '' }}">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="number" step="0.01" name="journal_entries[{{ $index }}][debit]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ $entry['debit'] ?? '' }}">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="number" step="0.01" name="journal_entries[{{ $index }}][credit]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm" value="{{ $entry['credit'] ?? '' }}">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200 text-center">
                                            <button type="button" class="remove-journal-entry bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr class="journal-entry-row">
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="text" name="journal_entries[0][account_code]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="text" name="journal_entries[0][account_name]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="number" step="0.01" name="journal_entries[0][debit]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200">
                                            <input type="number" step="0.01" name="journal_entries[0][credit]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
                                        </td>
                                        <td class="px-2 py-2 border border-gray-200 text-center">
                                            <button type="button" class="remove-journal-entry bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">PREPARED BY:</label>
                    <input type="text" name="prepared_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('prepared_by', $voucher->prepared_by) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">REVIEWED BY:</label>
                    <input type="text" name="reviewed_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reviewed_by', $voucher->reviewed_by) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">APPROVED BY:</label>
                    <input type="text" name="approved_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('approved_by', $voucher->approved_by) }}">
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('check_vouchers.show', $voucher->id) }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-100 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Check Voucher
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Journal Entry Management
    let journalEntryIndex = {{ $voucher->journal_entries ? count($voucher->journal_entries) : 1 }};

    document.getElementById('addJournalEntry').addEventListener('click', function() {
        const tbody = document.getElementById('journalEntriesBody');
        const newRow = document.createElement('tr');
        newRow.className = 'journal-entry-row';
        newRow.innerHTML = `
            <td class="px-2 py-2 border border-gray-200">
                <input type="text" name="journal_entries[${journalEntryIndex}][account_code]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-200">
                <input type="text" name="journal_entries[${journalEntryIndex}][account_name]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-200">
                <input type="number" step="0.01" name="journal_entries[${journalEntryIndex}][debit]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-200">
                <input type="number" step="0.01" name="journal_entries[${journalEntryIndex}][credit]" class="w-full bg-white border border-gray-300 rounded px-2 py-1 text-gray-800 text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-200 text-center">
                <button type="button" class="remove-journal-entry bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
        journalEntryIndex++;
    });

    document.getElementById('journalEntriesBody').addEventListener('click', function(e) {
        if (e.target.closest('.remove-journal-entry')) {
            const row = e.target.closest('.journal-entry-row');
            if (document.querySelectorAll('.journal-entry-row').length > 1) {
                row.remove();
            } else {
                alert('At least one journal entry row is required.');
            }
        }
    });
});

// G/L Account search for Bank field
(function() {
    const searchInput = document.getElementById('cv_gl_search');
    const dropdown = document.getElementById('cv_gl_dropdown');
    const idInput = document.getElementById('cv_gl_account_id');
    const bankInput = document.getElementById('cv_bank_name');
    let searchTimeout;

    if (!searchInput) return;

    async function fetchGlAccounts(query) {
        try {
            const url = `/ar-adjustments/gl-accounts?search=${encodeURIComponent(query || '')}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success && data.accounts.length > 0) {
                const bankAccounts = data.accounts.filter(a => a.display && a.display.includes('CIB'));
                const list = bankAccounts.length > 0 ? bankAccounts : data.accounts;

                dropdown.innerHTML = list.map(account => `
                    <div class="px-3 py-2 hover:bg-purple-50 cursor-pointer text-gray-800 border-b border-gray-100"
                         onclick="selectCvGlAccount(${account.id}, '${account.display.replace(/'/g, "\\'")}', '${(account.code || '').replace(/'/g, "\\'")}')">
                        <div class="font-semibold text-sm">${account.display}</div>
                        <div class="text-xs text-gray-500">${account.fs_line_item || ''}</div>
                    </div>
                `).join('');
                dropdown.classList.remove('hidden');
            } else {
                dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500">No G/L accounts found</div>';
                dropdown.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error fetching GL accounts:', error);
        }
    }

    searchInput.addEventListener('focus', () => fetchGlAccounts('CIB'));
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        searchTimeout = setTimeout(() => fetchGlAccounts(query || 'CIB'), 200);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
})();

function selectCvGlAccount(id, display, code) {
    document.getElementById('cv_gl_account_id').value = id;
    document.getElementById('cv_bank_name').value = display;
    document.getElementById('cv_gl_search').value = display;
    document.getElementById('cv_gl_dropdown').classList.add('hidden');
}
</script>
@endsection
