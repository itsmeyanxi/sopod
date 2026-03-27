@extends('layouts.app')

@section('title', 'Create Request for Payment')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">REQUEST FOR PAYMENT</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-500">RFP NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-50 border border-gray-200 text-gray-800 rounded">{{ $rfpNo }}</span>
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

        <!-- Search PO Section -->
        @if(!$selectedPO)
        <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
            <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-search mr-2"></i>Search Approved Purchase Order</h3>
            <div class="relative">
                <input
                    type="text"
                    id="poSearchInput"
                    class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Search by PO No, Supplier, or Company..."
                />
                <div id="poSearchResults" class="hidden absolute z-10 w-full mt-2 bg-white border border-gray-200 rounded shadow-lg max-h-96 overflow-y-auto"></div>
            </div>
        </div>
        @endif

        <form action="{{ route('request_for_payments.store') }}" method="POST" id="rfpForm">
            @csrf
            <input type="hidden" id="maxRfpAmount" value="{{ $poAmount ? number_format($poAmount, 2, '.', '') : '' }}">

            <!-- Company (Hidden - MeatPlus Only) -->
            <input type="hidden" name="company" value="MeatPlus">

            <!-- Payment Methods & Dates Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column - Payment Methods -->
                <div class="bg-gray-50 border border-gray-200 rounded p-4">
                    <label class="block font-semibold text-gray-500 mb-3">PAYMENT METHODS:</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="managers_check" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">Manager's Check</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="regular_check" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">Regular Check</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="wire_transfer" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">Wire Transfer</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="fund_transfer" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">Fund Transfer</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="pdc" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">PDC (Post-Dated Check)</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="cash" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">Cash</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="auto_debit" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">Auto Debit</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="others" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-500">Others</span>
                        </label>
                    </div>
                </div>

                <!-- Right Column - Dates and Reference Numbers -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">DUE DATE:</label>
                        <input type="date" name="due_date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('due_date') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">RFP#:</label>
                        <input type="text" readonly class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-500" value="{{ $rfpNo }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">LINKED PO:</label>
                        <input type="hidden" name="purchase_order_id" id="purchase_order_id" value="{{ old('purchase_order_id', $selectedPO->id ?? '') }}">
                        <div id="linkedPODisplay">
                            @if($selectedPO)
                                <div class="p-3 bg-green-50 border border-green-200 rounded text-green-700 flex justify-between items-center">
                                    <span><i class="fas fa-link mr-2"></i>{{ $selectedPO->po_no }}</span>
                                    <button type="button" id="unlinkPO" class="text-red-700 hover:text-red-700 text-sm"><i class="fas fa-times"></i></button>
                                </div>
                            @else
                                <div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-500">
                                    No PO linked — use search above
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">PAYEE (Vendor/Supplier): <span class="text-red-700">*</span></label>
                    <input type="text" name="payee" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payee', $selectedPO ? (
    $selectedPO->items->map(fn($item) => $item->supplierModel->supplier_name ?? $item->supplier_name ?? null)->filter()->unique()->implode(' / ')
    ?: ($selectedPO->supplierModel->supplier_name ?? $selectedPO->supplier ?? '')
) : '') }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">AMOUNT: <span class="text-red-700">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-500">₱</span>
                        <input type="number" step="0.01" name="amount" class="w-full bg-gray-50 border border-gray-200 rounded pl-8 pr-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount', $poAmount ? number_format($poAmount, 2, '.', '') : '') }}" required>
                    </div>
                </div>
            </div>

            <!-- Particulars -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">PARTICULARS:</label>
                <textarea name="particulars" rows="5" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter payment particulars...">{{ old('particulars') }}</textarea>
            </div>

            <!-- Bank -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">BANK/S:</label>
                <input type="text" name="bank" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank') }}" placeholder="Bank name and account details">
            </div>

            <!-- APV and CV Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">APV NO. (Account Payable Voucher):</label>
                    <input type="text" name="apv_no" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('apv_no') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">CV NO. (Check Voucher):</label>
                    <input type="text" name="cv_no" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('cv_no') }}">
                </div>
            </div>

            <!-- Requestor and Checker -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">REQUESTED BY (Requestor):</label>
                    <input type="text" name="requested_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requested_by') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">CHECKED BY (Department Head):</label>
                    <input type="text" name="checked_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('checked_by') }}">
                </div>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-200 rounded">
                    <div class="bg-gray-50 border-b border-gray-200 px-4 py-2 text-center font-semibold text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>FOR FINANCE USE ONLY
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm">Approved By:</th>
                                <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm">Approved By (Php 50,000 above):</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-200 px-4 py-16 text-center"></td>
                                <td class="border border-gray-200 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-100 text-gray-500 text-xs italic">
                                <td class="border border-gray-200 px-4 py-2 text-center">Finance Manager</td>
                                <td class="border border-gray-200 px-4 py-2 text-center">CFO / President</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('request_for_payments.index') }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-100 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Request for Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // PO Search & Auto-fill
    const poSearchInput = document.getElementById('poSearchInput');
    const poSearchResults = document.getElementById('poSearchResults');

    if (poSearchInput) {
        let debounceTimer;

        poSearchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                poSearchResults.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('request_for_payments.search_pos') }}?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(pos => {
                        if (pos.length === 0) {
                            poSearchResults.innerHTML = '<div class="p-4 text-gray-500">No approved POs found</div>';
                            poSearchResults.classList.remove('hidden');
                            return;
                        }

                        let html = '<div class="divide-y divide-gray-700">';
                        pos.forEach(po => {
                            html += `
                                <div class="po-result-item block p-3 hover:bg-gray-100 transition cursor-pointer"
                                     data-id="${po.id}"
                                     data-po-no="${(po.po_no || '').replace(/"/g, '&quot;')}"
                                     data-supplier="${(po.supplier || '').replace(/"/g, '&quot;')}"
                                     data-company="${(po.company || '').replace(/"/g, '&quot;')}"
                                     data-amount="${po.amount || 0}"
                                     data-currency="${po.currency || 'PHP'}">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold text-purple-700">${po.po_no}</div>
                                            <div class="text-sm text-gray-500">${po.supplier}</div>
                                            <div class="text-xs text-gray-500">${po.company}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500">${po.order_date || ''}</div>
                                            <div class="text-sm text-green-700">${po.currency || 'PHP'} ${parseFloat(po.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        poSearchResults.innerHTML = html;
                        poSearchResults.classList.remove('hidden');

                        // Attach click handlers for inline auto-fill
                        poSearchResults.querySelectorAll('.po-result-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const poId = this.dataset.id;
                                const poNo = this.dataset.poNo;
                                const supplier = this.dataset.supplier;
                                const company = this.dataset.company;
                                const amount = this.dataset.amount;

                                // Fill hidden PO ID
                                document.getElementById('purchase_order_id').value = poId;

                                // Fill linked PO display
                                document.getElementById('linkedPODisplay').innerHTML = `
                                    <div class="p-3 bg-green-50 border border-green-200 rounded text-green-700 flex justify-between items-center">
                                        <span><i class="fas fa-link mr-2"></i>${poNo}</span>
                                        <button type="button" id="unlinkPO" class="text-red-700 hover:text-red-700 text-sm"><i class="fas fa-times"></i></button>
                                    </div>
                                `;
                                attachUnlinkHandler();

                                // Fill payee from supplier
                                const payeeInput = document.querySelector('input[name="payee"]');
                                if (payeeInput) payeeInput.value = supplier;

                                // Fill amount and set max limit
                                const amountInput = document.querySelector('input[name="amount"]');
                                const maxAmt = parseFloat(amount).toFixed(2);
                                if (amountInput) { amountInput.value = maxAmt; amountInput.max = maxAmt; }
                                document.getElementById('maxRfpAmount').value = maxAmt;

                                // Fill company radio
                                if (company) {
                                    const radios = document.querySelectorAll('input[name="company"]');
                                    radios.forEach(radio => {
                                        if (radio.value === company) {
                                            radio.checked = true;
                                        }
                                    });
                                }

                                // Fill particulars with PO reference
                                const particularsInput = document.querySelector('textarea[name="particulars"]');
                                if (particularsInput && !particularsInput.value.trim()) {
                                    particularsInput.value = 'Payment for ' + poNo + ' - ' + supplier;
                                }

                                // Hide search
                                poSearchResults.classList.add('hidden');
                                poSearchInput.value = poNo;
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        poSearchResults.innerHTML = '<div class="p-4 text-red-700">Error searching POs</div>';
                        poSearchResults.classList.remove('hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (poSearchInput && !poSearchInput.contains(e.target) && !poSearchResults.contains(e.target)) {
                poSearchResults.classList.add('hidden');
            }
        });
    }

    // Unlink PO handler
    function attachUnlinkHandler() {
        const unlinkBtn = document.getElementById('unlinkPO');
        if (unlinkBtn) {
            unlinkBtn.addEventListener('click', function() {
                document.getElementById('purchase_order_id').value = '';
                document.getElementById('maxRfpAmount').value = '';
                document.getElementById('linkedPODisplay').innerHTML = `
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-500">
                        No PO linked — use search above
                    </div>
                `;
                const searchInput = document.getElementById('poSearchInput');
                if (searchInput) searchInput.value = '';
                // Remove max and warning from amount input
                const amountInput = document.querySelector('input[name="amount"]');
                if (amountInput) {
                    amountInput.removeAttribute('max');
                    amountInput.classList.remove('border-red-500');
                    const w = amountInput.parentElement.querySelector('.amount-warning');
                    if (w) w.remove();
                }
            });
        }
    }
    attachUnlinkHandler();

    // Amount limit validation
    function validateRfpAmount() {
        const maxEl = document.getElementById('maxRfpAmount');
        const maxVal = parseFloat(maxEl ? maxEl.value : '');
        const amountInput = document.querySelector('input[name="amount"]');
        if (!amountInput || isNaN(maxVal) || maxVal <= 0) return true;

        const val = parseFloat(amountInput.value) || 0;
        const warning = amountInput.parentElement.querySelector('.amount-warning');
        if (val > maxVal) {
            if (!warning) {
                const w = document.createElement('div');
                w.className = 'amount-warning text-red-700 text-xs mt-1';
                w.textContent = 'Amount cannot exceed PO total: ₱' + maxVal.toLocaleString('en-US', {minimumFractionDigits: 2});
                amountInput.parentElement.appendChild(w);
            }
            amountInput.classList.add('border-red-500');
            return false;
        } else {
            if (warning) warning.remove();
            amountInput.classList.remove('border-red-500');
            return true;
        }
    }

    const rfpAmountInput = document.querySelector('input[name="amount"]');
    if (rfpAmountInput) {
        rfpAmountInput.addEventListener('input', validateRfpAmount);
    }

    // Prevent form submission if amount exceeds limit
    document.getElementById('rfpForm').addEventListener('submit', function(e) {
        if (!validateRfpAmount()) {
            e.preventDefault();
            alert('Amount cannot exceed the linked PO total. Please correct the amount.');
        }
    });
});
</script>
@endsection
