@extends('layouts.app')

@section('title', 'Create Check Voucher')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CHECK VOUCHER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">CV NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $cvNo }}</span>
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

        <!-- Search APV Section -->
        @if(!$selectedAPV)
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-2">Search Approved APV Invoice</h3>
            <div class="relative">
                <input
                    type="text"
                    id="apvSearchInput"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Search by APV No, Vendor Name, or Reference No..."
                />
                <div id="apvSearchResults" class="hidden absolute z-10 w-full mt-2 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-96 overflow-y-auto"></div>
            </div>
        </div>
        @endif

        <form action="{{ route('check_vouchers.store') }}" method="POST" id="cvForm">
            @csrf

            <input type="hidden" name="accounts_payable_invoice_id" value="{{ old('accounts_payable_invoice_id', $selectedAPV->id ?? '') }}">
            <input type="hidden" id="maxPayableAmount" value="{{ $selectedAPV->grand_total ?? '' }}">

            @if($selectedAPV)
            <div class="mb-6 p-4 bg-green-900/20 border border-green-700 rounded">
                <div class="flex items-center text-green-300 mb-2">
                    <i class="fas fa-link mr-2"></i>
                    <span class="font-semibold">Linked to APV: {{ $selectedAPV->apv_no }}</span>
                </div>
                <div class="text-sm text-gray-300">
                    Vendor: {{ $selectedAPV->vendor_name }} | Grand Total: {{ $selectedAPV->currency }} {{ number_format($selectedAPV->grand_total, 2) }}
                </div>
            </div>
            @endif

            <!-- CV Date and Check Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CV DATE: <span class="text-red-400">*</span></label>
                    <input type="date" name="cv_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('cv_date', date('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CHECK DATE: <span class="text-red-400">*</span></label>
                    <input type="date" name="check_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('check_date', date('Y-m-d')) }}" required>
                </div>
            </div>

            <!-- Supplier Information -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">SUPPLIER INFORMATION</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">SUPPLIER CODE:</label>
                        <input type="text" name="supplier_code" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_code', $selectedAPV->vendor_code ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">SUPPLIER NAME: <span class="text-red-400">*</span></label>
                        <input type="text" name="supplier_name" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_name', $selectedAPV->vendor_name ?? '') }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-300 mb-2">SUPPLIER ADDRESS:</label>
                        <textarea name="supplier_address" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('supplier_address', $selectedAPV->vendor_address ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">SUPPLIER TIN:</label>
                        <input type="text" name="supplier_tin" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_tin', $selectedAPV->vendor_tin ?? '') }}">
                    </div>
                </div>
            </div>

            <!-- Check Details -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">CHECK DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">CHECK NO:</label>
                        <input type="text" name="check_no" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('check_no', '0') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">BANK:</label>
                        <input type="text" name="bank" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank') }}" placeholder="e.g., CIB - Peso - BPI - 008103-1475-31">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">BRANCH:</label>
                        <input type="text" name="branch" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('branch') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">CHECK AMOUNT: <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-400">₱</span>
                            <input type="number" step="0.01" name="check_amount" id="checkAmountInput" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('check_amount', $selectedAPV->grand_total ?? '') }}" {{ $selectedAPV ? 'max=' . $selectedAPV->grand_total : '' }} required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">PAYMENT DETAILS</h3>
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-700">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 border border-gray-700 text-gray-300">Date</th>
                                <th class="px-4 py-2 border border-gray-700 text-gray-300">Type</th>
                                <th class="px-4 py-2 border border-gray-700 text-gray-300">Reference No.</th>
                                <th class="px-4 py-2 border border-gray-700 text-gray-300">APV No.</th>
                                <th class="px-4 py-2 border border-gray-700 text-gray-300">Paid Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 border border-gray-700">
                                    <input type="date" name="payment_date" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm" value="{{ old('payment_date', date('Y-m-d')) }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-700">
                                    <input type="text" name="payment_type" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm" value="{{ old('payment_type', 'AP') }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-700">
                                    <input type="text" name="reference_no" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm" value="{{ old('reference_no', $selectedAPV->reference_no ?? '') }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-700">
                                    <input type="text" name="apv_no" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm" value="{{ old('apv_no', $selectedAPV->apv_no ?? '') }}">
                                </td>
                                <td class="px-4 py-2 border border-gray-700">
                                    <input type="number" step="0.01" name="paid_amount" id="paidAmountInput" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm" value="{{ old('paid_amount', $selectedAPV->grand_total ?? '') }}" {{ $selectedAPV ? 'max=' . $selectedAPV->grand_total : '' }} required>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Particulars -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PARTICULARS: <span class="text-red-400">*</span></label>
                <textarea name="particulars" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>{{ old('particulars', $selectedAPV->particulars ?? '') }}</textarea>
            </div>

            <!-- Journal Entry -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">JOURNAL ENTRY</h3>
                <div id="journalEntriesContainer">
                    <div class="overflow-x-auto">
                        <table class="w-full border border-gray-700 mb-4">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 border border-gray-700 text-gray-300">Account Code</th>
                                    <th class="px-4 py-2 border border-gray-700 text-gray-300">Account Name</th>
                                    <th class="px-4 py-2 border border-gray-700 text-gray-300">Debit</th>
                                    <th class="px-4 py-2 border border-gray-700 text-gray-300">Credit</th>
                                    <th class="px-4 py-2 border border-gray-700 text-gray-300">
                                        <button type="button" id="addJournalEntry" class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700">
                                            <i class="fas fa-plus"></i> Add Row
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="journalEntriesBody">
                                <tr class="journal-entry-row">
                                    <td class="px-2 py-2 border border-gray-700">
                                        <input type="text" name="journal_entries[0][account_code]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
                                    </td>
                                    <td class="px-2 py-2 border border-gray-700">
                                        <input type="text" name="journal_entries[0][account_name]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
                                    </td>
                                    <td class="px-2 py-2 border border-gray-700">
                                        <input type="number" step="0.01" name="journal_entries[0][debit]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
                                    </td>
                                    <td class="px-2 py-2 border border-gray-700">
                                        <input type="number" step="0.01" name="journal_entries[0][credit]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
                                    </td>
                                    <td class="px-2 py-2 border border-gray-700 text-center">
                                        <button type="button" class="remove-journal-entry bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PREPARED BY:</label>
                    <input type="text" name="prepared_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('prepared_by', Auth::user()->name) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REVIEWED BY:</label>
                    <input type="text" name="reviewed_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reviewed_by') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">APPROVED BY:</label>
                    <input type="text" name="approved_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('approved_by', 'ODM / FDM') }}">
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('check_vouchers.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Check Voucher
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // APV Search
    const apvSearchInput = document.getElementById('apvSearchInput');
    const apvSearchResults = document.getElementById('apvSearchResults');

    if (apvSearchInput) {
        let debounceTimer;

        apvSearchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                apvSearchResults.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('check_vouchers.search_apvs') }}?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(invoices => {
                        if (invoices.length === 0) {
                            apvSearchResults.innerHTML = '<div class="p-4 text-gray-400">No approved APV invoices found</div>';
                            apvSearchResults.classList.remove('hidden');
                            return;
                        }

                        let html = '<div class="divide-y divide-gray-700">';
                        invoices.forEach(invoice => {
                            html += `
                                <div class="apv-result-item block p-3 hover:bg-gray-700 transition cursor-pointer"
                                     data-id="${invoice.id}"
                                     data-apv-no="${(invoice.apv_no || '').replace(/"/g, '&quot;')}"
                                     data-vendor-name="${(invoice.vendor_name || '').replace(/"/g, '&quot;')}"
                                     data-vendor-code="${(invoice.vendor_code || '').replace(/"/g, '&quot;')}"
                                     data-vendor-address="${(invoice.vendor_address || '').replace(/"/g, '&quot;')}"
                                     data-vendor-tin="${(invoice.vendor_tin || '').replace(/"/g, '&quot;')}"
                                     data-currency="${(invoice.currency || 'PHP')}"
                                     data-grand-total="${invoice.grand_total || 0}"
                                     data-reference-no="${(invoice.reference_no || '').replace(/"/g, '&quot;')}"
                                     data-particulars="${(invoice.particulars || '').replace(/"/g, '&quot;')}">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold text-purple-400">${invoice.apv_no}</div>
                                            <div class="text-sm text-gray-300">${invoice.vendor_name}</div>
                                            <div class="text-xs text-gray-400">${invoice.apv_date}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-green-400">${invoice.currency} ${parseFloat(invoice.grand_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        apvSearchResults.innerHTML = html;
                        apvSearchResults.classList.remove('hidden');

                        // Attach click handlers for inline auto-fill
                        apvSearchResults.querySelectorAll('.apv-result-item').forEach(item => {
                            item.addEventListener('click', function() {
                                // Fill hidden APV ID
                                document.querySelector('input[name="accounts_payable_invoice_id"]').value = this.dataset.id;

                                // Fill supplier info
                                const supplierCode = document.querySelector('input[name="supplier_code"]');
                                const supplierName = document.querySelector('input[name="supplier_name"]');
                                const supplierAddress = document.querySelector('textarea[name="supplier_address"]');
                                const supplierTin = document.querySelector('input[name="supplier_tin"]');
                                if (supplierCode) supplierCode.value = this.dataset.vendorCode;
                                if (supplierName) supplierName.value = this.dataset.vendorName;
                                if (supplierAddress) supplierAddress.value = this.dataset.vendorAddress;
                                if (supplierTin) supplierTin.value = this.dataset.vendorTin;

                                // Fill check amount and paid amount (with max limit)
                                const checkAmount = document.querySelector('input[name="check_amount"]');
                                const paidAmount = document.querySelector('input[name="paid_amount"]');
                                const grandTotal = parseFloat(this.dataset.grandTotal).toFixed(2);
                                if (checkAmount) { checkAmount.value = grandTotal; checkAmount.max = grandTotal; }
                                if (paidAmount) { paidAmount.value = grandTotal; paidAmount.max = grandTotal; }
                                document.getElementById('maxPayableAmount').value = grandTotal;

                                // Fill reference and APV no
                                const refNo = document.querySelector('input[name="reference_no"]');
                                const apvNo = document.querySelector('input[name="apv_no"]');
                                if (refNo) refNo.value = this.dataset.referenceNo;
                                if (apvNo) apvNo.value = this.dataset.apvNo;

                                // Fill particulars
                                const particulars = document.querySelector('textarea[name="particulars"]');
                                if (particulars && !particulars.value.trim()) {
                                    particulars.value = this.dataset.particulars;
                                }

                                // Show linked APV badge
                                const searchSection = apvSearchInput.closest('.mb-6');
                                if (searchSection) {
                                    const badge = document.createElement('div');
                                    badge.className = 'mt-2 p-3 bg-green-900/20 border border-green-700 rounded';
                                    badge.innerHTML = `
                                        <div class="flex items-center justify-between text-green-300">
                                            <span><i class="fas fa-link mr-2"></i>Linked to APV: ${this.dataset.apvNo}</span>
                                            <span class="text-sm text-gray-300">${this.dataset.vendorName} | ${this.dataset.currency} ${parseFloat(this.dataset.grandTotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                                        </div>
                                    `;
                                    const existingBadge = searchSection.querySelector('.bg-green-900\\/20');
                                    if (existingBadge) existingBadge.remove();
                                    searchSection.appendChild(badge);
                                }

                                // Hide search
                                apvSearchResults.classList.add('hidden');
                                apvSearchInput.value = this.dataset.apvNo;
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        apvSearchResults.innerHTML = '<div class="p-4 text-red-400">Error searching APV invoices</div>';
                        apvSearchResults.classList.remove('hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (apvSearchInput && !apvSearchInput.contains(e.target) && !apvSearchResults.contains(e.target)) {
                apvSearchResults.classList.add('hidden');
            }
        });
    }

    // Journal Entry Management
    let journalEntryIndex = 1;

    document.getElementById('addJournalEntry').addEventListener('click', function() {
        const tbody = document.getElementById('journalEntriesBody');
        const newRow = document.createElement('tr');
        newRow.className = 'journal-entry-row';
        newRow.innerHTML = `
            <td class="px-2 py-2 border border-gray-700">
                <input type="text" name="journal_entries[${journalEntryIndex}][account_code]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-700">
                <input type="text" name="journal_entries[${journalEntryIndex}][account_name]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-700">
                <input type="number" step="0.01" name="journal_entries[${journalEntryIndex}][debit]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-700">
                <input type="number" step="0.01" name="journal_entries[${journalEntryIndex}][credit]" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white text-sm">
            </td>
            <td class="px-2 py-2 border border-gray-700 text-center">
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

    // Amount limit validation
    function validateAmountLimit(input, label) {
        const maxEl = document.getElementById('maxPayableAmount');
        const maxVal = parseFloat(maxEl ? maxEl.value : '');
        if (isNaN(maxVal) || maxVal <= 0) return true;

        const val = parseFloat(input.value) || 0;
        const warning = input.parentElement.querySelector('.amount-warning');
        if (val > maxVal) {
            if (!warning) {
                const w = document.createElement('div');
                w.className = 'amount-warning text-red-400 text-xs mt-1';
                w.textContent = label + ' cannot exceed ₱' + maxVal.toLocaleString('en-US', {minimumFractionDigits: 2});
                input.parentElement.appendChild(w);
            }
            input.classList.add('border-red-500');
            return false;
        } else {
            if (warning) warning.remove();
            input.classList.remove('border-red-500');
            return true;
        }
    }

    const checkAmountInput = document.getElementById('checkAmountInput');
    const paidAmountInput = document.getElementById('paidAmountInput');

    if (checkAmountInput) {
        checkAmountInput.addEventListener('input', () => validateAmountLimit(checkAmountInput, 'Check amount'));
    }
    if (paidAmountInput) {
        paidAmountInput.addEventListener('input', () => validateAmountLimit(paidAmountInput, 'Paid amount'));
    }

    // Prevent form submission if amounts exceed limit
    document.getElementById('cvForm').addEventListener('submit', function(e) {
        let valid = true;
        if (checkAmountInput && !validateAmountLimit(checkAmountInput, 'Check amount')) valid = false;
        if (paidAmountInput && !validateAmountLimit(paidAmountInput, 'Paid amount')) valid = false;
        if (!valid) {
            e.preventDefault();
            alert('Amount cannot exceed the APV grand total. Please correct the amounts.');
        }
    });
});
</script>
@endsection
