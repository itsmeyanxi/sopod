@extends('layouts.app')

@section('title', 'Create Accounts Payable Invoice')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">ACCOUNTS PAYABLE VOUCHER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">APV NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $apvNo }}</span>
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

        <!-- Search RFP Section -->
        @if(!$selectedRFP)
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-2">Search Approved Request for Payment</h3>
            <div class="relative">
                <input
                    type="text"
                    id="rfpSearchInput"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Search by RFP No, Payee, or Company..."
                />
                <div id="rfpSearchResults" class="hidden absolute z-10 w-full mt-2 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-96 overflow-y-auto"></div>
            </div>
        </div>
        @endif

        <form action="{{ route('accounts_payable_invoices.store') }}" method="POST" id="apvForm">
            @csrf

            <input type="hidden" name="request_for_payment_id" value="{{ old('request_for_payment_id', $selectedRFP->id ?? '') }}">
            <input type="hidden" id="maxInvoiceAmount" value="{{ $selectedRFP->amount ?? '' }}">

            <!-- APV Date and Payment Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">APV DATE: <span class="text-red-400">*</span></label>
                    <input type="date" name="apv_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('apv_date', date('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYMENT TYPE: <span class="text-red-400">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center p-3 bg-gray-900 border border-gray-700 rounded hover:bg-gray-700 cursor-pointer transition flex-1">
                            <input type="radio" name="payment_type" value="full_payment" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500" {{ old('payment_type', 'full_payment') == 'full_payment' ? 'checked' : '' }} required>
                            <span class="ml-3 text-white">Full Payment</span>
                        </label>
                        <label class="flex items-center p-3 bg-gray-900 border border-gray-700 rounded hover:bg-gray-700 cursor-pointer transition flex-1">
                            <input type="radio" name="payment_type" value="downpayment" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500" {{ old('payment_type') == 'downpayment' ? 'checked' : '' }}>
                            <span class="ml-3 text-white">Downpayment</span>
                        </label>
                    </div>
                </div>
            </div>

            @if($selectedRFP)
            <div class="mb-6 p-4 bg-green-900/20 border border-green-700 rounded">
                <div class="flex items-center text-green-300 mb-2">
                    <i class="fas fa-link mr-2"></i>
                    <span class="font-semibold">Linked to RFP: {{ $selectedRFP->rfp_no }}</span>
                </div>
                <div class="text-sm text-gray-300">
                    Payee: {{ $selectedRFP->payee }} | Company: {{ $selectedRFP->company }}
                </div>
            </div>
            @endif

            <!-- Vendor Information -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">VENDOR INFORMATION</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR CODE:</label>
                        <input type="text" name="vendor_code" id="vendor_code" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_code', $supplierInfo['code'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR NAME: <span class="text-red-400">*</span></label>
                        <input type="text" name="vendor_name" id="vendor_name" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_name', $selectedRFP->payee ?? '') }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR ADDRESS:</label>
                        <textarea name="vendor_address" id="vendor_address" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('vendor_address', $supplierInfo['address'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR TIN:</label>
                        <input type="text" name="vendor_tin" id="vendor_tin" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_tin', $supplierInfo['tin'] ?? '') }}">
                    </div>
                </div>
            </div>

            <!-- Document Details -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">DOCUMENT DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DOCUMENT DATE: <span class="text-red-400">*</span></label>
                        <input type="date" name="document_date" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('document_date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms') }}" placeholder="e.g., Net 30">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DUE DATE:</label>
                        <input type="date" name="due_date" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('due_date') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">REFERENCE NO:</label>
                        <input type="text" name="reference_no" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_no') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">PURCHASE ORDER NO:</label>
                        <input type="text" name="purchase_order_no" id="purchase_order_no" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('purchase_order_no', $selectedRFP->purchaseOrder->po_no ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">CURRENCY: <span class="text-red-400">*</span></label>
                        <select name="currency" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            <option value="PHP" {{ old('currency', 'PHP') == 'PHP' ? 'selected' : '' }}>PHP</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="JPY" {{ old('currency') == 'JPY' ? 'selected' : '' }}>JPY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">FOREX RATE:</label>
                        <input type="number" step="0.0001" name="forex_rate" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('forex_rate') }}" placeholder="1.0000">
                    </div>
                </div>
            </div>

            <!-- Particulars and Accounting -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">PARTICULARS & ACCOUNTING</h3>
                <div class="mb-4">
                    <label class="block font-semibold text-gray-300 mb-2">PARTICULARS: <span class="text-red-400">*</span></label>
                    <textarea name="particulars" rows="4" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>{{ old('particulars', $selectedRFP->particulars ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ITEM CODE:</label>
                        <input type="text" name="item_code" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('item_code') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">COST CENTER:</label>
                        <input type="text" name="cost_center" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('cost_center') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT CODE:</label>
                        <input type="text" name="account_code" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_code') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NAME:</label>
                        <input type="text" name="account_name" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_name') }}">
                    </div>
                </div>
            </div>

            <!-- Amount Calculations -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">AMOUNT DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">TOTAL AMOUNT: <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-400">₱</span>
                            <input type="number" step="0.01" name="total" id="totalAmount" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('total', $selectedRFP->amount ?? '') }}" required>
                        </div>
                    </div>
                    <div id="downpaymentField" style="display: none;">
                        <label class="block font-semibold text-gray-300 mb-2">DOWNPAYMENT AMOUNT: <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-400">₱</span>
                            <input type="number" step="0.01" name="downpayment_amount" id="downpaymentAmount" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('downpayment_amount') }}">
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VAT AMOUNT:</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-400">₱</span>
                            <input type="number" step="0.01" name="vat_amount" id="vatAmount" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vat_amount', '0.00') }}">
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">W-TAX AMOUNT:</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-400">₱</span>
                            <input type="number" step="0.01" name="w_tax_amount" id="wTaxAmount" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('w_tax_amount', '0.00') }}">
                        </div>
                    </div>
                </div>

                <!-- Grand Total Display -->
                <div class="mt-4 p-4 bg-gray-800 border-2 border-purple-600 rounded">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-300">GRAND TOTAL:</span>
                        <span id="grandTotalDisplay" class="text-2xl font-bold text-purple-400">₱ 0.00</span>
                    </div>
                </div>
            </div>

            <!-- Prepared and Reviewed By -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PREPARED BY:</label>
                    <input type="text" name="prepared_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('prepared_by') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REVIEWED BY:</label>
                    <input type="text" name="reviewed_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reviewed_by') }}">
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">REMARKS:</label>
                <textarea name="remarks" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('remarks') }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('accounts_payable_invoices.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // RFP Search
    const rfpSearchInput = document.getElementById('rfpSearchInput');
    const rfpSearchResults = document.getElementById('rfpSearchResults');

    if (rfpSearchInput) {
        let debounceTimer;

        rfpSearchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                rfpSearchResults.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('accounts_payable_invoices.search_rfps') }}?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(rfps => {
                        if (rfps.length === 0) {
                            rfpSearchResults.innerHTML = '<div class="p-4 text-gray-400">No approved RFPs found</div>';
                            rfpSearchResults.classList.remove('hidden');
                            return;
                        }

                        let html = '<div class="divide-y divide-gray-700">';
                        rfps.forEach(rfp => {
                            html += `
                                <div class="rfp-result-item block p-3 hover:bg-gray-700 transition cursor-pointer"
                                     data-id="${rfp.id}"
                                     data-rfp-no="${(rfp.rfp_no || '').replace(/"/g, '&quot;')}"
                                     data-payee="${(rfp.payee || '').replace(/"/g, '&quot;')}"
                                     data-company="${(rfp.company || '').replace(/"/g, '&quot;')}"
                                     data-amount="${rfp.amount || 0}"
                                     data-particulars="${(rfp.particulars || '').replace(/"/g, '&quot;')}"
                                     data-purchase-order-no="${(rfp.purchase_order_no || '').replace(/"/g, '&quot;')}"
                                     data-vendor-address="${(rfp.vendor_address || '').replace(/"/g, '&quot;')}"
                                     data-vendor-tin="${(rfp.vendor_tin || '').replace(/"/g, '&quot;')}"
                                     data-vendor-code="${(rfp.vendor_code || '').replace(/"/g, '&quot;')}">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold text-purple-400">${rfp.rfp_no}</div>
                                            <div class="text-sm text-gray-300">${rfp.payee}</div>
                                            <div class="text-xs text-gray-400">${rfp.company}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-300">${rfp.date}</div>
                                            <div class="text-sm text-green-400">₱${parseFloat(rfp.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        rfpSearchResults.innerHTML = html;
                        rfpSearchResults.classList.remove('hidden');

                        // Attach click handlers for inline auto-fill
                        rfpSearchResults.querySelectorAll('.rfp-result-item').forEach(item => {
                            item.addEventListener('click', function() {
                                // Fill hidden RFP ID
                                document.querySelector('input[name="request_for_payment_id"]').value = this.dataset.id;

                                // Fill vendor info
                                const vendorName = document.getElementById('vendor_name');
                                const vendorCode = document.getElementById('vendor_code');
                                const vendorAddress = document.getElementById('vendor_address');
                                const vendorTin = document.getElementById('vendor_tin');
                                if (vendorName) vendorName.value = this.dataset.payee;
                                if (vendorCode) vendorCode.value = this.dataset.vendorCode;
                                if (vendorAddress) vendorAddress.value = this.dataset.vendorAddress;
                                if (vendorTin) vendorTin.value = this.dataset.vendorTin;

                                // Fill PO number
                                const poNo = document.getElementById('purchase_order_no');
                                if (poNo) poNo.value = this.dataset.purchaseOrderNo;

                                // Fill particulars
                                const particulars = document.querySelector('textarea[name="particulars"]');
                                if (particulars && !particulars.value.trim()) {
                                    particulars.value = this.dataset.particulars;
                                }

                                // Fill total amount and set max limit
                                const totalInput = document.getElementById('totalAmount');
                                const maxAmount = parseFloat(this.dataset.amount).toFixed(2);
                                if (totalInput) {
                                    totalInput.value = maxAmount;
                                    totalInput.max = maxAmount;
                                    totalInput.dispatchEvent(new Event('input'));
                                }
                                document.getElementById('maxInvoiceAmount').value = maxAmount;

                                // Show linked RFP badge
                                const searchSection = rfpSearchInput.closest('.mb-6');
                                if (searchSection) {
                                    const badge = document.createElement('div');
                                    badge.className = 'mt-2 p-3 bg-green-900/20 border border-green-700 rounded';
                                    badge.innerHTML = `
                                        <div class="flex items-center justify-between text-green-300">
                                            <span><i class="fas fa-link mr-2"></i>Linked to RFP: ${this.dataset.rfpNo}</span>
                                            <span class="text-sm text-gray-300">${this.dataset.payee} | ₱${parseFloat(this.dataset.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                                        </div>
                                    `;
                                    const existingBadge = searchSection.querySelector('.bg-green-900\\/20');
                                    if (existingBadge) existingBadge.remove();
                                    searchSection.appendChild(badge);
                                }

                                // Hide search
                                rfpSearchResults.classList.add('hidden');
                                rfpSearchInput.value = this.dataset.rfpNo;
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        rfpSearchResults.innerHTML = '<div class="p-4 text-red-400">Error searching RFPs</div>';
                        rfpSearchResults.classList.remove('hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (rfpSearchInput && !rfpSearchInput.contains(e.target) && !rfpSearchResults.contains(e.target)) {
                rfpSearchResults.classList.add('hidden');
            }
        });
    }

    // Grand Total Calculation — declare inputs first so calculateGrandTotal() can reference them
    const totalInput = document.getElementById('totalAmount');
    const vatInput = document.getElementById('vatAmount');
    const wTaxInput = document.getElementById('wTaxAmount');
    const grandTotalDisplay = document.getElementById('grandTotalDisplay');

    // Payment Type Toggle
    const paymentTypeRadios = document.querySelectorAll('input[name="payment_type"]');
    const downpaymentField = document.getElementById('downpaymentField');
    const downpaymentInput = document.getElementById('downpaymentAmount');

    function toggleDownpaymentField() {
        const selectedType = document.querySelector('input[name="payment_type"]:checked').value;
        if (selectedType === 'downpayment') {
            downpaymentField.style.display = 'block';
            downpaymentInput.required = true;
        } else {
            downpaymentField.style.display = 'none';
            downpaymentInput.required = false;
            downpaymentInput.value = '';
        }
        calculateGrandTotal();
    }

    paymentTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleDownpaymentField);
    });

    toggleDownpaymentField();

    function calculateGrandTotal() {
        const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
        const total = parseFloat(totalInput.value) || 0;
        const downpayment = parseFloat(downpaymentInput.value) || 0;
        const vat = parseFloat(vatInput.value) || 0;
        const wTax = parseFloat(wTaxInput.value) || 0;

        let totalBeforeVat;
        if (paymentType === 'downpayment') {
            totalBeforeVat = downpayment;
        } else {
            totalBeforeVat = total;
        }

        const totalAfterVat = totalBeforeVat + vat;
        const grandTotal = totalAfterVat - wTax;

        grandTotalDisplay.textContent = '₱ ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    totalInput.addEventListener('input', calculateGrandTotal);
    downpaymentInput.addEventListener('input', calculateGrandTotal);
    vatInput.addEventListener('input', calculateGrandTotal);
    wTaxInput.addEventListener('input', calculateGrandTotal);

    calculateGrandTotal();

    // Amount limit validation
    function validateTotalLimit() {
        const maxEl = document.getElementById('maxInvoiceAmount');
        const maxVal = parseFloat(maxEl ? maxEl.value : '');
        if (isNaN(maxVal) || maxVal <= 0) return true;

        const val = parseFloat(totalInput.value) || 0;
        const warning = totalInput.parentElement.querySelector('.amount-warning');
        if (val > maxVal) {
            if (!warning) {
                const w = document.createElement('div');
                w.className = 'amount-warning text-red-400 text-xs mt-1';
                w.textContent = 'Total amount cannot exceed RFP amount: ₱' + maxVal.toLocaleString('en-US', {minimumFractionDigits: 2});
                totalInput.parentElement.appendChild(w);
            }
            totalInput.classList.add('border-red-500');
            return false;
        } else {
            if (warning) warning.remove();
            totalInput.classList.remove('border-red-500');
            return true;
        }
    }

    totalInput.addEventListener('input', validateTotalLimit);

    // Prevent form submission if amount exceeds limit
    document.getElementById('apvForm').addEventListener('submit', function(e) {
        if (!validateTotalLimit()) {
            e.preventDefault();
            alert('Total amount cannot exceed the linked RFP amount. Please correct the amount.');
        }
    });
});
</script>
@endsection
