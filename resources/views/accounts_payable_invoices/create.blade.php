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
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ icon: 'error', title: 'Validation Error', html: '{!! implode("<br>", $errors->all()) !!}' });
        });
        </script>
        @endif

        <!-- RFP Reference Selector -->
        @if(!$selectedRFP)
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-3">LINK TO REQUEST FOR PAYMENT <span class="text-gray-400 text-xs font-normal">(optional if PO or Reference No is provided)</span></h3>
            <div id="rfpSearchSection">
                <label class="block text-gray-300 text-sm mb-1">Search Approved Request for Payment</label>
                <div class="relative">
                    <input type="text" id="rfpSearchInput"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Search by RFP No, Payee, or Company..." />
                    <div id="rfpSearchResults" class="hidden absolute z-10 w-full mt-2 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-96 overflow-y-auto"></div>
                </div>
            </div>
            <div id="linkedRefBadge"></div>
        </div>
        @endif

        <form action="{{ route('accounts_payable_invoices.store') }}" method="POST" id="apvForm">
            @csrf

            <input type="hidden" name="request_for_payment_id" id="rfpId" value="{{ old('request_for_payment_id', $selectedRFP->id ?? '') }}">
            <input type="hidden" id="maxInvoiceAmount" value="{{ $selectedRFP->amount ?? '' }}">

            <!-- APV Date and Payment Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">APV DATE: <span class="text-red-700">*</span></label>
                    <input type="date" name="apv_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('apv_date', date('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYMENT TYPE: <span class="text-red-700">*</span></label>
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
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded">
                <div class="flex items-center text-green-700 mb-2">
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
                    <div class="relative">
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR NAME: <span class="text-red-700">*</span></label>
                        <input type="text" name="vendor_name" id="vendor_name" autocomplete="off" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_name', $selectedRFP->payee ?? '') }}" required placeholder="Type to search vendor...">
                        <div id="vendor_name_dropdown" class="hidden absolute z-50 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-52 overflow-y-auto" style="top:100%;margin-top:2px;"></div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR ADDRESS:</label>
                        <textarea name="vendor_address" id="vendor_address" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('vendor_address', $supplierInfo['address'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR TIN:</label>
                        <input type="text" name="vendor_tin" id="vendor_tin" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_tin', $supplierInfo['tin'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">PO NUMBER:</label>
                        <input type="text" name="purchase_order_no" id="purchase_order_no" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('purchase_order_no', $selectedRFP->purchaseOrder->po_no ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">REFERENCE NO.:</label>
                        <input type="text" name="reference_no" id="reference_no_vendor" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_no') }}" placeholder="Manual entry">
                    </div>
                </div>
            </div>

            <!-- Document Details -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">DOCUMENT DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DOCUMENT DATE: <span class="text-red-700">*</span></label>
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
                        <label class="block font-semibold text-gray-300 mb-2">CURRENCY: <span class="text-red-700">*</span></label>
                        <select name="currency" id="apvCurrencySelect" onchange="apvUpdateCurrencySymbols()" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            <option value="PHP" data-symbol="₱" {{ old('currency', 'PHP') == 'PHP' ? 'selected' : '' }}>PHP</option>
                            <option value="USD" data-symbol="$" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" data-symbol="€" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="JPY" data-symbol="¥" {{ old('currency') == 'JPY' ? 'selected' : '' }}>JPY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">FOREX RATE:</label>
                        <input type="number" step="0.0001" name="forex_rate" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('forex_rate') }}" placeholder="1.0000">
                    </div>
                </div>
            </div>

            <!-- Downpayment field (shown when payment_type = downpayment) -->
            <div id="downpaymentField" class="mb-6 hidden">
                <label class="block font-semibold text-gray-300 mb-2">DOWNPAYMENT AMOUNT:</label>
                <div class="relative w-64">
                    <span class="absolute left-3 top-2.5 text-gray-300 apv-currency-symbol">₱</span>
                    <input type="number" step="0.01" name="downpayment_amount" id="downpaymentAmount" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('downpayment_amount') }}">
                </div>
            </div>

            <!-- Particulars & Accounting Items Table -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-white">PARTICULARS & ACCOUNTING</h3>
                    <button type="button" onclick="addApvRow()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
                <div style="overflow-x:auto;overflow-y:visible;">
                    <table class="w-full text-sm border-collapse" style="overflow:visible;" id="apvItemsTable">
                        <thead class="bg-red-700 text-white text-xs uppercase">
                            <tr>
                                <th class="border border-gray-600 px-2 py-2" style="width:28px">#</th>
                                <th class="border border-gray-600 px-2 py-2" style="min-width:180px">PARTICULARS</th>
                                <th class="border border-gray-600 px-2 py-2 apv-item-code-th" style="min-width:130px">ITEM CODE</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:90px">DEPT</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:90px">DIVISION</th>
                                <th class="border border-gray-600 px-2 py-2 apv-vat-col" style="width:40px">VAT</th>
                                <th class="border border-gray-600 px-2 py-2 apv-tax-col" style="width:90px">TAX CODE</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:160px">ACCOUNT CODE</th>
                                <th class="border border-gray-600 px-2 py-2" style="min-width:180px">ACCOUNT NAME</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:115px">GROSS AMOUNT</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="apvItemsBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Computed Summary (compact) -->
            <div class="mb-4 flex justify-end">
                <div class="bg-gray-900 border border-gray-700 rounded px-3 py-2 text-xs" style="min-width:220px;">
                    <div class="flex justify-between gap-4"><span class="text-gray-400">Gross:</span><span class="font-bold text-white" id="apvSumGross">₱0.00</span></div>
                    <div class="flex justify-between gap-4 apv-vat-summary-row"><span class="text-gray-400">VAT (12%):</span><span class="text-yellow-400" id="apvSumVat">₱0.00</span></div>
                    <div class="flex justify-between gap-4 apv-vat-summary-row"><span class="text-gray-400">Net of VAT:</span><span class="text-blue-400" id="apvSumNetVat">₱0.00</span></div>
                    <div class="flex justify-between gap-4 apv-ewt-summary-row"><span class="text-gray-400">Wtax:</span><span class="text-red-400" id="apvSumEwt">(₱0.00)</span></div>
                    <div class="flex justify-between gap-4 border-t border-gray-600 pt-1 mt-1">
                        <span class="text-white font-semibold">Amount Due:</span>
                        <span class="font-bold text-green-400" id="apvSumAmountDue">₱0.00</span>
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
                <button type="submit" id="apvSubmitBtn"
                    class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
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
                            rfpSearchResults.innerHTML = '<div class="p-4 text-gray-300">No approved RFPs found</div>';
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
                                     data-po-reference-number="${(rfp.po_reference_number || '').replace(/"/g, '&quot;')}"
                                     data-vendor-address="${(rfp.vendor_address || '').replace(/"/g, '&quot;')}"
                                     data-vendor-tin="${(rfp.vendor_tin || '').replace(/"/g, '&quot;')}"
                                     data-vendor-code="${(rfp.vendor_code || '').replace(/"/g, '&quot;')}"
                                     data-payment-terms="${(rfp.payment_terms || '').replace(/"/g, '&quot;')}"
                                     data-currency="${rfp.currency || 'PHP'}"
                                     data-forex-rate="${rfp.forex_rate || ''}"
                                     data-po-type="${rfp.po_type || 'items'}"
                                     data-service-description="${(rfp.service_description || '').replace(/"/g, '&quot;')}"
                                     data-po-items="${JSON.stringify(rfp.po_items || []).replace(/"/g, '&quot;')}">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold text-purple-400">${rfp.rfp_no}${rfp.purchase_order_no ? ' <span class="text-yellow-400 text-xs">PO: '+rfp.purchase_order_no+'</span>' : ''}</div>
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

                        rfpSearchResults.querySelectorAll('.rfp-result-item').forEach(item => {
                            item.addEventListener('click', function() {
                                document.getElementById('rfpId').value = this.dataset.id;
                                document.getElementById('apvSubmitBtn').disabled = false;

                                const vendorName    = document.getElementById('vendor_name');
                                const vendorCode    = document.getElementById('vendor_code');
                                const vendorAddress = document.getElementById('vendor_address');
                                const vendorTin     = document.getElementById('vendor_tin');
                                if (vendorName)    vendorName.value    = this.dataset.payee;
                                if (vendorCode)    vendorCode.value    = this.dataset.vendorCode;
                                if (vendorAddress) vendorAddress.value = this.dataset.vendorAddress;
                                if (vendorTin)     vendorTin.value     = this.dataset.vendorTin;

                                const pmtTerms = document.querySelector('input[name="payment_terms"]');
                                if (pmtTerms && this.dataset.paymentTerms) pmtTerms.value = this.dataset.paymentTerms;

                                const currencyEl = document.querySelector('select[name="currency"]');
                                if (currencyEl && this.dataset.currency) currencyEl.value = this.dataset.currency;

                                const forexEl = document.querySelector('input[name="forex_rate"]');
                                if (forexEl && this.dataset.forexRate) forexEl.value = this.dataset.forexRate;

                                const poNo = document.getElementById('purchase_order_no');
                                if (poNo) poNo.value = this.dataset.purchaseOrderNo;

                                const refNo = document.getElementById('reference_no_vendor');
                                if (refNo && this.dataset.poReferenceNumber) refNo.value = this.dataset.poReferenceNumber;

                                toggleServiceMode(this.dataset.poType === 'service');

                                const poItems = JSON.parse(this.dataset.poItems || '[]');
                                document.getElementById('apvItemsBody').innerHTML = '';
                                apvRowCount = 0;
                                if (poItems.length) {
                                    poItems.forEach(i => addApvRow({ particulars: i.description, item_code: i.item_code }));
                                } else {
                                    addApvRow({ particulars: this.dataset.serviceDescription || this.dataset.particulars || '' });
                                }

                                document.getElementById('maxInvoiceAmount').value = parseFloat(this.dataset.amount).toFixed(2);

                                const searchSection = rfpSearchInput.closest('.mb-6');
                                if (searchSection) {
                                    const badge = document.createElement('div');
                                    badge.className = 'mt-2 p-3 bg-green-900 border border-green-700 rounded';
                                    badge.innerHTML = `
                                        <div class="flex items-center justify-between text-green-400">
                                            <span><i class="fas fa-link mr-2"></i>Linked to RFP: ${this.dataset.rfpNo}</span>
                                            <span class="text-sm text-gray-300">${this.dataset.payee} | ₱${parseFloat(this.dataset.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                                        </div>
                                    `;
                                    const existingBadge = searchSection.querySelector('.bg-green-900');
                                    if (existingBadge) existingBadge.remove();
                                    searchSection.appendChild(badge);
                                }

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

    // Payment Type Toggle
    document.querySelectorAll('input[name="payment_type"]').forEach(r => r.addEventListener('change', function() {
        const dp = document.getElementById('downpaymentField');
        dp.classList.toggle('hidden', this.value !== 'downpayment');
    }));

    // Init one empty row
    addApvRow({ particulars: '{{ addslashes($selectedRFP->particulars ?? '') }}' });

    // Apply currency rules on initial load
    apvUpdateCurrencySymbols();

    document.getElementById('apvForm').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('#apvItemsBody tr');
        if (!rows.length) { e.preventDefault(); alert('Add at least one item.'); return; }

        const rfpId = (document.getElementById('rfpId')?.value || '').trim();
        const poNo  = (document.getElementById('purchase_order_no')?.value || '').trim();
        const refNo = document.querySelector('input[name="reference_no"]')?.value?.trim() || '';
        if (!rfpId && !poNo && !refNo) {
            e.preventDefault();
            alert('Please link to an RFP, enter a PO Number, or provide a Reference No.');
            return;
        }

        const maxVal = parseFloat(document.getElementById('maxInvoiceAmount').value) || 0;
        if (maxVal > 0) {
            const gross = [...document.querySelectorAll('.apv-gross')].reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
            if (gross > maxVal) {
                e.preventDefault();
                alert('Total gross (₱' + gross.toFixed(2) + ') exceeds RFP amount (₱' + maxVal.toFixed(2) + ').');
            }
        }
    });

    // RFP click: update max amount
    document.addEventListener('click', function(e) {
        const item = e.target.closest('.rfp-result-item');
        if (item) document.getElementById('maxInvoiceAmount').value = parseFloat(item.dataset.amount).toFixed(2);
    });
});

// ── Currency / VAT / Tax Code Toggle ─────────────────────────────────────────
function apvUpdateCurrencySymbols() {
    const select  = document.getElementById('apvCurrencySelect');
    const sym     = select.selectedOptions[0]?.getAttribute('data-symbol') || '₱';
    const isPhp   = select.value === 'PHP';

    // Update currency symbols
    document.querySelectorAll('.apv-currency-symbol').forEach(el => el.textContent = sym);

    // Toggle VAT and Tax Code header columns
    document.querySelectorAll('.apv-vat-col, .apv-tax-col').forEach(el => {
        el.style.display = isPhp ? '' : 'none';
    });

    // Toggle VAT and Tax Code summary rows
    document.querySelectorAll('.apv-vat-summary-row').forEach(el => {
        el.style.display = isPhp ? '' : 'none';
    });
    document.querySelectorAll('.apv-ewt-summary-row').forEach(el => {
        el.style.display = isPhp ? '' : 'none';
    });

    // Toggle per-row VAT and Tax Code cells, clear and disable when not PHP
    document.querySelectorAll('#apvItemsBody tr').forEach(tr => {
        const vatCb  = tr.querySelector('.apv-vat');
        const taxSel = tr.querySelector('.apv-tax-code');
        const vatTd  = tr.querySelector('.apv-vat-td');
        const taxTd  = tr.querySelector('.apv-tax-td');

        if (vatTd)  vatTd.style.display  = isPhp ? '' : 'none';
        if (taxTd)  taxTd.style.display  = isPhp ? '' : 'none';

        if (vatCb) {
            if (!isPhp) vatCb.checked = false;
            vatCb.disabled = !isPhp;
        }
        if (taxSel) {
            if (!isPhp) taxSel.value = '';
            taxSel.disabled = !isPhp;
        }
    });

    recalcApvSummary();
}

// ── APV Items Table ───────────────────────────────────────────────────────────
const GL_ACCOUNTS = @json($glAccounts);
let apvRowCount = 0;

function addApvRow(data) {
    data = data || {};
    const idx   = apvRowCount++;
    const isPhp = document.getElementById('apvCurrencySelect').value === 'PHP';
    const tr    = document.createElement('tr');
    tr.className = 'bg-gray-800 hover:bg-gray-750 border-b border-gray-600';
    tr.innerHTML = `
        <td class="border border-gray-600 px-2 py-2 text-center text-gray-300 text-xs font-semibold row-num">${document.querySelectorAll('#apvItemsBody tr').length + 1}</td>
        <td class="border border-gray-600 px-1 py-1" style="position:relative">
            <input type="text" name="items[${idx}][particulars]"
                class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-particulars placeholder-gray-400"
                placeholder="Search description..." autocomplete="off" value="${(data.particulars || '').replace(/"/g, '&quot;')}">
            <div class="apv-part-drop hidden bg-gray-800 border-2 border-gray-500 rounded-lg shadow-2xl overflow-y-auto" style="min-width:320px;"></div>
        </td>
        <td class="border border-gray-600 px-1 py-1 apv-item-code-td" style="display:${window.apvIsService?'none':''}">
            <input type="text" name="items[${idx}][item_code]"
                class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-item-code-val placeholder-gray-400"
                placeholder="Item code" value="${data.item_code || ''}">
        </td>
        <td class="border border-gray-600 px-1 py-1">
            <input type="text" name="items[${idx}][department]"
                class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs placeholder-gray-400"
                placeholder="Dept" value="${data.department || ''}" list="dept-list" autocomplete="off">
        </td>
        <td class="border border-gray-600 px-1 py-1">
            <input type="text" name="items[${idx}][division]"
                class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs placeholder-gray-400"
                placeholder="Div" value="${data.division || ''}" list="div-list" autocomplete="off">
        </td>
        <td class="border border-gray-600 px-1 py-1 text-center apv-vat-td" style="display:${isPhp ? '' : 'none'}">
            <input type="checkbox" name="items[${idx}][vat]" value="1"
                class="apv-vat w-4 h-4 accent-yellow-400"
                ${data.vat ? 'checked' : ''}
                ${!isPhp ? 'disabled' : ''}
                onchange="recalcApvSummary()">
        </td>
        <td class="border border-gray-600 px-1 py-1 apv-tax-td" style="display:${isPhp ? '' : 'none'}">
            <select name="items[${idx}][tax_code]"
                class="w-full px-1 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-tax-code"
                onchange="recalcApvSummary()"
                ${!isPhp ? 'disabled' : ''}>
                <option value="">—</option>
                <option value="C158" ${data.tax_code === 'C158' || data.tax_code === '158' ? 'selected' : ''}>C158 - Goods (1%)</option>
                <option value="C160" ${data.tax_code === 'C160' || data.tax_code === '160' ? 'selected' : ''}>C160 - Services (2%)</option>
                <option value="C100" ${data.tax_code === 'C100' ? 'selected' : ''}>C100 - Rental (5%)</option>
                <option value="I010" ${data.tax_code === 'I010' ? 'selected' : ''}>I010 - Professional (5%)</option>
                <option value="I011" ${data.tax_code === 'I011' ? 'selected' : ''}>I011 - Professional (10%)</option>
            </select>
        </td>
        <td class="border border-gray-600 px-1 py-1">
            <input type="text" name="items[${idx}][account_code]"
                class="w-full px-2 py-1.5 bg-gray-600 border border-gray-500 rounded text-gray-300 text-xs apv-gl-code"
                value="${data.account_code || ''}" readonly placeholder="Auto-filled">
        </td>
        <td class="border border-gray-600 px-1 py-1" style="position:relative">
            <input type="text" name="items[${idx}][account_name]"
                class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-gl-search placeholder-gray-400"
                placeholder="Search account name..." autocomplete="off" value="${data.account_name || ''}">
            <div class="apv-gl-drop hidden bg-gray-800 border border-gray-600 rounded shadow-lg text-xs overflow-y-auto" style="min-width:280px;"></div>
        </td>
        <td class="border border-gray-600 px-1 py-1">
            <input type="number" step="0.01" name="items[${idx}][gross_amount]"
                class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-gross placeholder-gray-400"
                value="${data.gross_amount || ''}" required
                oninput="updateDebitCredit(this);recalcApvSummary()" placeholder="+Debit / -Credit">
            <div class="text-center text-xs mt-0.5 apv-dc-label font-semibold ${data.gross_amount < 0 ? 'text-red-400' : 'text-green-400'}">${data.gross_amount < 0 ? 'CREDIT' : (data.gross_amount > 0 ? 'DEBIT' : '')}</div>
        </td>
        <td class="border border-gray-600 px-1 py-2 text-center">
            <button type="button" onclick="removeApvRow(this)" class="text-red-400 hover:text-red-300">
                <i class="fas fa-trash text-xs"></i>
            </button>
        </td>
    `;

    document.getElementById('apvItemsBody').appendChild(tr);

    // GL Account search — wired to account name field; pastes auto-fill code
    const glSearch = tr.querySelector('.apv-gl-search');
    const glDrop   = tr.querySelector('.apv-gl-drop');
    const glCode   = tr.querySelector('.apv-gl-code');

    function positionDrop(input, drop) {
        const rect = input.getBoundingClientRect();
        drop.style.cssText = `position:absolute;top:${rect.bottom+window.scrollY}px;left:${rect.left+window.scrollX}px;width:320px;z-index:99999;max-height:220px;overflow-y:auto;`;
        document.body.appendChild(drop);
    }

    glSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        if (q.length < 1) { glDrop.classList.add('hidden'); return; }
        const exact = GL_ACCOUNTS.find(a => a.name.toLowerCase() === q || a.display.toLowerCase() === q);
        if (exact) { glCode.value = exact.code; glDrop.classList.add('hidden'); return; }
        const hits = GL_ACCOUNTS.filter(a => (a.search||'').includes(q)).slice(0, 50);
        glDrop.innerHTML = hits.length
            ? hits.map(a => `<div class="px-2 py-1 hover:bg-blue-600 cursor-pointer border-b border-gray-700 gl-o" data-code="${a.code}" data-name="${a.name}"><span class="font-mono font-semibold text-yellow-300 text-xs">${a.code}</span> <span class="text-gray-300 text-xs">${a.name}</span></div>`).join('')
            : '<div class="px-2 py-1 text-gray-400 text-xs">No matches</div>';
        positionDrop(glSearch, glDrop);
        glDrop.classList.remove('hidden');
        glDrop.querySelectorAll('.gl-o').forEach(o => o.addEventListener('mousedown', function(e) {
            e.preventDefault();
            glSearch.value = this.dataset.name;
            glCode.value   = this.dataset.code;
            glDrop.classList.add('hidden');
        }));
    });
    glSearch.addEventListener('blur', () => setTimeout(() => glDrop.classList.add('hidden'), 200));

    // Particulars search — typeahead for item descriptions, fills item code
    const partInput   = tr.querySelector('.apv-particulars');
    const partDrop    = tr.querySelector('.apv-part-drop');
    const itemCodeVal = tr.querySelector('.apv-item-code-val');
    const ITEM_URL    = '{{ route("accounts_payable_invoices.search_items") }}';
    const ITEM_HDR    = '<div class="sticky top-0 bg-gray-700 px-3 py-1.5 text-xs text-gray-300 font-semibold border-b border-gray-600">Select an item</div>';
    let itemDebounce;

    partInput.addEventListener('input', function() {
        clearTimeout(itemDebounce);
        const q = this.value.trim();
        if (q.length < 1) { partDrop.classList.add('hidden'); return; }
        itemDebounce = setTimeout(async () => {
            try {
                const res   = await fetch(`${ITEM_URL}?q=${encodeURIComponent(q)}`);
                const items = await res.json();
                partDrop.innerHTML = !items.length
                    ? ITEM_HDR + '<div class="px-4 py-3 text-gray-400 text-xs">No items found</div>'
                    : ITEM_HDR + items.map(i =>
                        `<div class="item-o px-4 py-2 hover:bg-blue-600 cursor-pointer text-white border-b border-gray-700"
                              data-code="${(i.item_code||'').replace(/"/g,'&quot;')}"
                              data-desc="${(i.item_description||'').replace(/"/g,'&quot;')}">
                            <div class="font-semibold text-xs">${i.item_description||''}</div>
                            <div class="flex gap-2 text-xs text-gray-300 mt-0.5">
                                <span class="bg-gray-700 px-1 rounded">${i.brand||''}</span>
                                <span class="text-yellow-300 font-mono">${i.item_code||''}</span>
                            </div>
                        </div>`
                    ).join('');
                partDrop.querySelectorAll('.item-o').forEach(o => o.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    partInput.value = this.dataset.desc;
                    if (itemCodeVal) itemCodeVal.value = this.dataset.code;
                    partDrop.classList.add('hidden');
                }));
                positionDrop(partInput, partDrop);
                partDrop.classList.remove('hidden');
            } catch (err) { partDrop.classList.add('hidden'); }
        }, 250);
    });
    partInput.addEventListener('blur', () => setTimeout(() => partDrop.classList.add('hidden'), 200));

    reorderApvRows();
}

function removeApvRow(btn) {
    btn.closest('tr').remove();
    reorderApvRows();
    recalcApvSummary();
}

function reorderApvRows() {
    document.querySelectorAll('#apvItemsBody tr').forEach((tr, i) => {
        const n = tr.querySelector('.row-num');
        if (n) n.textContent = i + 1;
        tr.querySelectorAll('input,select').forEach(el => {
            const nm = el.getAttribute('name');
            if (nm) el.setAttribute('name', nm.replace(/items\[\d+\]/, `items[${i}]`));
        });
    });
    apvRowCount = document.querySelectorAll('#apvItemsBody tr').length;
}

function updateDebitCredit(input) {
    const lbl = input.closest('td').querySelector('.apv-dc-label');
    if (!lbl) return;
    const v = parseFloat(input.value);
    if (isNaN(v) || v === 0) { lbl.textContent = ''; lbl.className = 'text-center text-xs mt-0.5 apv-dc-label font-semibold'; }
    else if (v > 0) { lbl.textContent = 'DEBIT'; lbl.className = 'text-center text-xs mt-0.5 apv-dc-label font-semibold text-green-400'; }
    else { lbl.textContent = 'CREDIT'; lbl.className = 'text-center text-xs mt-0.5 apv-dc-label font-semibold text-red-400'; }
}

function recalcApvSummary() {
    const isPhp = document.getElementById('apvCurrencySelect').value === 'PHP';
    const sym   = document.getElementById('apvCurrencySelect').selectedOptions[0]?.getAttribute('data-symbol') || '₱';
    let gross = 0, vat = 0, ewt = 0;

    document.querySelectorAll('#apvItemsBody tr').forEach(tr => {
        const raw = parseFloat(tr.querySelector('.apv-gross')?.value) || 0;
        const g   = Math.abs(raw); // use abs for VAT/EWT calc; sign determines debit/credit
        const v   = isPhp && (tr.querySelector('.apv-vat')?.checked);
        const tc  = isPhp ? (tr.querySelector('.apv-tax-code')?.value || '') : '';
        const iv  = v ? g * 12 / 112 : 0;
        const net = v ? g * 100 / 112 : g;
        gross += raw; // signed total
        vat   += iv;
        const ewtRates = {'C158':0.01,'158':0.01,'C160':0.02,'160':0.02,'C100':0.05,'I010':0.05,'I011':0.10};
        ewt   += net * (ewtRates[tc] || 0);
    });

    const netOfVat = gross - vat;
    const fmt = (v, s) => (s || sym) + Math.abs(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    document.getElementById('apvSumGross').textContent     = fmt(gross);
    document.getElementById('apvSumVat').textContent       = fmt(vat);
    document.getElementById('apvSumNetVat').textContent    = fmt(netOfVat);
    document.getElementById('apvSumEwt').textContent       = '(' + fmt(ewt) + ')';
    document.getElementById('apvSumAmountDue').textContent = fmt(isPhp ? gross - ewt : gross);
}

// ── Service PO Toggle ─────────────────────────────────────────────────────────
window.apvIsService = false;
function toggleServiceMode(isService) {
    window.apvIsService = isService;
    document.querySelectorAll('.apv-item-code-th, .apv-item-code-td').forEach(el => {
        el.style.display = isService ? 'none' : '';
    });
}

// ── Vendor Name Search Typeahead ──────────────────────────────────────────────
(function() {
    const VENDOR_URL = '{{ route("accounts_payable_invoices.search_vendors") }}';
    const input      = document.getElementById('vendor_name');
    const dropdown   = document.getElementById('vendor_name_dropdown');
    let debounce;
    if (!input || !dropdown) return;

    input.addEventListener('input', function() {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 1) { dropdown.classList.add('hidden'); return; }
        debounce = setTimeout(async () => {
            try {
                const res   = await fetch(`${VENDOR_URL}?q=${encodeURIComponent(q)}`);
                const items = await res.json();
                if (!items.length) {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No vendors found</div>';
                    dropdown.classList.remove('hidden');
                    return;
                }
                dropdown.innerHTML = items.map(s =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 vnd-opt"
                          data-name="${(s.vendor_name||'').replace(/"/g,'&quot;')}"
                          data-code="${(s.vendor_code||'').replace(/"/g,'&quot;')}"
                          data-address="${(s.address||'').replace(/"/g,'&quot;')}"
                          data-tin="${(s.tin||'').replace(/"/g,'&quot;')}">
                        <span class="font-semibold">${s.vendor_name}</span>
                        <span class="text-gray-400 ml-2 text-xs">${s.vendor_code||''}</span>
                        ${s.tin ? `<span class="text-gray-500 ml-2 text-xs">TIN: ${s.tin}</span>` : ''}
                    </div>`
                ).join('');
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.vnd-opt').forEach(opt => {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        input.value = this.dataset.name;
                        document.getElementById('vendor_code').value    = this.dataset.code;
                        document.getElementById('vendor_address').value = this.dataset.address || '';
                        document.getElementById('vendor_tin').value     = this.dataset.tin || '';
                        dropdown.classList.add('hidden');
                    });
                });
            } catch(e) { dropdown.classList.add('hidden'); }
        }, 250);
    });
    input.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('hidden'), 200));
})();
</script>

<datalist id="dept-list">
    <option>Accounting</option><option>Administration</option><option>Credit &amp; Collection</option>
    <option>Delivery</option><option>Executive</option><option>Finance</option>
    <option>Finance &amp; Accounting</option><option>HR &amp; Administration</option>
    <option>Information System</option><option>Procurement</option><option>Purchasing</option>
    <option>Sales</option><option>Supply Chain</option><option>Treasury</option><option>Warehouse</option>
</datalist>
<datalist id="div-list">
    <option>Accounting</option><option>Benefits</option><option>Category</option><option>CFO</option>
    <option>Credit &amp; Collection</option><option>Customer Service</option><option>Delivery</option>
    <option>Employee Relations</option><option>Finance Management</option><option>General Administration</option>
    <option>General Manager</option><option>Imports</option><option>IT Operations</option>
    <option>Logistics</option><option>Procurement Approver</option><option>Purchasing</option>
    <option>Quality Assurance</option><option>Recruitment</option><option>Sales</option>
    <option>Trade Local/Imports</option><option>Training and Development</option>
    <option>Treasury Operations</option><option>Vice President</option><option>Warehouse Operations</option>
</datalist>
@endsection