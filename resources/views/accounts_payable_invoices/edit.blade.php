@extends('layouts.app')

@section('title', 'Edit Accounts Payable Invoice')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT ACCOUNTS PAYABLE VOUCHER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">APV NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $invoice->apv_no }}</span>
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

        @if($invoice->requestForPayment)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded">
            <div class="flex items-center text-green-700 mb-2">
                <i class="fas fa-link mr-2"></i>
                <span class="font-semibold">Linked to RFP: {{ $invoice->requestForPayment->rfp_no }}</span>
            </div>
            <div class="text-sm text-gray-300">
                Payee: {{ $invoice->requestForPayment->payee }} | Company: {{ $invoice->requestForPayment->company }}
            </div>
        </div>
        @endif

        <form action="{{ route('accounts_payable_invoices.update', $invoice->id) }}" method="POST" id="apvForm">
            @csrf
            @method('PUT')

            <!-- APV Date and Payment Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">APV DATE: <span class="text-red-700">*</span></label>
                    <input type="date" name="apv_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('apv_date', $invoice->apv_date->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYMENT TYPE: <span class="text-red-700">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center p-3 bg-gray-900 border border-gray-700 rounded hover:bg-gray-700 cursor-pointer transition flex-1">
                            <input type="radio" name="payment_type" value="full_payment" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500" {{ old('payment_type', $invoice->payment_type) == 'full_payment' ? 'checked' : '' }} required>
                            <span class="ml-3 text-white">Full Payment</span>
                        </label>
                        <label class="flex items-center p-3 bg-gray-900 border border-gray-700 rounded hover:bg-gray-700 cursor-pointer transition flex-1">
                            <input type="radio" name="payment_type" value="downpayment" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500" {{ old('payment_type', $invoice->payment_type) == 'downpayment' ? 'checked' : '' }}>
                            <span class="ml-3 text-white">Downpayment</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">VENDOR INFORMATION</h3>
                <!-- Supplier Search -->
                <div class="mb-4 relative" id="supplierSearchWrap">
                    <label class="block font-semibold text-gray-300 mb-2">SEARCH SUPPLIER:</label>
                    <input type="text" id="supplier_search_input" autocomplete="off"
                           placeholder="Type supplier name to change..."
                           value="{{ old('vendor_name', $invoice->vendor_name) }}"
                           class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <div id="supplier_search_dropdown" class="hidden absolute z-50 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-52 overflow-y-auto" style="top:100%;margin-top:2px;"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR CODE:</label>
                        <input type="text" name="vendor_code" id="vendor_code" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_code', $invoice->vendor_code) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR NAME: <span class="text-red-700">*</span></label>
                        <input type="text" name="vendor_name" id="vendor_name" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_name', $invoice->vendor_name) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR ADDRESS:</label>
                        <textarea name="vendor_address" id="vendor_address" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('vendor_address', $invoice->vendor_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR TIN:</label>
                        <input type="text" name="vendor_tin" id="vendor_tin" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_tin', $invoice->vendor_tin) }}">
                    </div>
                </div>
            </div>

            <!-- Document Details -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">DOCUMENT DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DOCUMENT DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="document_date" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('document_date', $invoice->document_date->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms', $invoice->payment_terms) }}" placeholder="e.g., Net 30">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DUE DATE:</label>
                        <input type="date" name="due_date" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">REFERENCE NO:</label>
                        <input type="text" name="reference_no" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_no', $invoice->reference_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">PURCHASE ORDER NO:</label>
                        <input type="text" name="purchase_order_no" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('purchase_order_no', $invoice->purchase_order_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">CURRENCY: <span class="text-red-700">*</span></label>
                        <select name="currency" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            <option value="PHP" {{ old('currency', $invoice->currency) == 'PHP' ? 'selected' : '' }}>PHP</option>
                            <option value="USD" {{ old('currency', $invoice->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency', $invoice->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="JPY" {{ old('currency', $invoice->currency) == 'JPY' ? 'selected' : '' }}>JPY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">FOREX RATE:</label>
                        <input type="number" step="0.0001" name="forex_rate" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('forex_rate', $invoice->forex_rate) }}" placeholder="1.0000">
                    </div>
                </div>
            </div>

            <!-- Downpayment (shown for downpayment type) -->
            <div id="downpaymentField" class="mb-6 {{ old('payment_type', $invoice->payment_type) === 'downpayment' ? '' : 'hidden' }}">
                <label class="block font-semibold text-gray-300 mb-2">DOWNPAYMENT AMOUNT:</label>
                <div class="relative w-64">
                    <span class="absolute left-3 top-2.5 text-gray-300">₱</span>
                    <input type="number" step="0.01" name="downpayment_amount" id="downpaymentAmount" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('downpayment_amount', $invoice->downpayment_amount) }}">
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
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead class="bg-red-700 text-white text-xs uppercase">
                            <tr>
                                <th class="border border-gray-600 px-2 py-2" style="width:28px">#</th>
                                <th class="border border-gray-600 px-2 py-2" style="min-width:180px">PARTICULARS</th>
                                <th class="border border-gray-600 px-2 py-2" style="min-width:130px">ITEM CODE</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:90px">DEPT</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:90px">DIVISION</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:40px">VAT</th>
                                <th class="border border-gray-600 px-2 py-2" style="width:90px">TAX CODE</th>
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

            <!-- Computed Summary -->
            <div class="mb-6 flex justify-end">
                <div class="bg-gray-900 border border-gray-700 rounded p-4 w-80">
                    <div class="flex flex-col gap-1 text-sm">
                        <div class="flex justify-between"><span class="text-gray-300">Total Gross:</span><span class="font-bold text-white" id="apvSumGross">₱0.00</span></div>
                        <div class="flex justify-between"><span class="text-gray-300">VAT (12%):</span><span class="font-bold text-yellow-400" id="apvSumVat">₱0.00</span></div>
                        <div class="flex justify-between"><span class="text-gray-300">Net of VAT:</span><span class="font-bold text-blue-400" id="apvSumNetVat">₱0.00</span></div>
                        <div class="flex justify-between"><span class="text-gray-300">EWT:</span><span class="font-bold text-red-400" id="apvSumEwt">(₱0.00)</span></div>
                        <div class="flex justify-between border-t border-gray-600 pt-2 mt-1">
                            <span class="text-white font-semibold">Amount Due:</span>
                            <span class="font-bold text-green-400 text-base" id="apvSumAmountDue">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prepared and Reviewed By -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PREPARED BY:</label>
                    <input type="text" name="prepared_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('prepared_by', $invoice->prepared_by) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REVIEWED BY:</label>
                    <input type="text" name="reviewed_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reviewed_by', $invoice->reviewed_by) }}">
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">REMARKS:</label>
                <textarea name="remarks" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('remarks', $invoice->remarks) }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('accounts_payable_invoices.show', $invoice->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment Type Toggle
    document.querySelectorAll('input[name="payment_type"]').forEach(r => r.addEventListener('change', function() {
        document.getElementById('downpaymentField').classList.toggle('hidden', this.value !== 'downpayment');
    }));

    // Pre-fill existing items
    @foreach($invoice->items as $item)
    addApvRow({
        particulars:  '{{ addslashes($item->particulars) }}',
        item_code:    '{{ addslashes($item->item_code) }}',
        department:   '{{ addslashes($item->department) }}',
        division:     '{{ addslashes($item->division) }}',
        vat:          {{ $item->vat ? 'true' : 'false' }},
        tax_code:     '{{ $item->tax_code }}',
        account_code: '{{ addslashes($item->account_code) }}',
        account_name: '{{ addslashes($item->account_name) }}',
        gross_amount: {{ $item->gross_amount }},
    });
    @endforeach
    @if($invoice->items->isEmpty())
    addApvRow();
    @endif
});

// ── APV Items Table ───────────────────────────────────────────────────────────
const GL_ACCOUNTS = @json($glAccounts);
let apvRowCount = 0;

function addApvRow(data) {
    data = data || {};
    const idx = apvRowCount++;
    const tr = document.createElement('tr');
    tr.className = 'bg-gray-800 hover:bg-gray-750 border-b border-gray-600';
    tr.innerHTML = `
        <td class="border border-gray-600 px-2 py-2 text-center text-gray-300 text-xs font-semibold row-num">${document.querySelectorAll('#apvItemsBody tr').length + 1}</td>
        <td class="border border-gray-600 px-1 py-1"><input type="text" name="items[${idx}][particulars]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-particulars placeholder-gray-400" placeholder="Description..." value="${(data.particulars||'').replace(/"/g,'&quot;')}"></td>
        <td class="border border-gray-600 px-1 py-1">
            <div class="relative">
                <div class="relative">
                    <input type="text" class="apv-item-search w-full bg-gray-700 border border-gray-500 rounded px-2 py-1.5 pr-7 text-white text-xs placeholder-gray-400 focus:border-blue-500 focus:outline-none" placeholder="Search item..." autocomplete="off" value="${data.item_code||''}">
                    <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-3 h-3 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <div class="apv-item-drop hidden absolute z-[9999] bg-gray-800 border-2 border-gray-500 rounded-lg mt-1 shadow-2xl max-h-64 overflow-y-auto" style="min-width:320px;left:0;top:100%">
                    <div class="sticky top-0 bg-gray-700 px-3 py-1.5 text-xs text-gray-300 font-semibold border-b border-gray-600">Select an item</div>
                </div>
                <input type="hidden" name="items[${idx}][item_code]" class="apv-item-code-val" value="${data.item_code||''}">
            </div>
        </td>
        <td class="border border-gray-600 px-1 py-1"><input type="text" name="items[${idx}][department]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs placeholder-gray-400" placeholder="Dept" value="${data.department||''}"></td>
        <td class="border border-gray-600 px-1 py-1"><input type="text" name="items[${idx}][division]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs placeholder-gray-400" placeholder="Div" value="${data.division||''}"></td>
        <td class="border border-gray-600 px-1 py-1 text-center"><input type="checkbox" name="items[${idx}][vat]" value="1" class="apv-vat w-4 h-4 accent-yellow-400" ${data.vat?'checked':''} onchange="recalcApvSummary()"></td>
        <td class="border border-gray-600 px-1 py-1">
            <select name="items[${idx}][tax_code]" class="w-full px-1 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-tax-code" onchange="recalcApvSummary()">
                <option value="">—</option>
                <option value="158" ${data.tax_code==='158'?'selected':''}>158 (1%)</option>
                <option value="160" ${data.tax_code==='160'?'selected':''}>160 (2%)</option>
            </select>
        </td>
        <td class="border border-gray-600 px-1 py-1" style="position:relative">
            <input type="text" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-gl-search placeholder-gray-400" placeholder="Search GL..." autocomplete="off" value="${data.account_code||''}">
            <div class="apv-gl-drop hidden absolute z-50 bg-gray-800 border border-gray-600 rounded shadow-lg text-xs max-h-40 overflow-y-auto" style="top:100%;left:0;min-width:280px;"></div>
            <input type="hidden" name="items[${idx}][account_code]" class="apv-gl-code" value="${data.account_code||''}">
        </td>
        <td class="border border-gray-600 px-1 py-1"><input type="text" name="items[${idx}][account_name]" class="w-full px-2 py-1.5 bg-gray-600 border border-gray-500 rounded text-gray-300 text-xs apv-gl-name" value="${data.account_name||''}" readonly placeholder="Auto-filled"></td>
        <td class="border border-gray-600 px-1 py-1"><input type="number" step="0.01" name="items[${idx}][gross_amount]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-gross placeholder-gray-400" value="${data.gross_amount||''}" min="0" required oninput="recalcApvSummary()" placeholder="0.00"></td>
        <td class="border border-gray-600 px-1 py-2 text-center"><button type="button" onclick="removeApvRow(this)" class="text-red-400 hover:text-red-300"><i class="fas fa-trash text-xs"></i></button></td>
    `;
    document.getElementById('apvItemsBody').appendChild(tr);
    const glSearch = tr.querySelector('.apv-gl-search'), glDrop = tr.querySelector('.apv-gl-drop');
    const glCode = tr.querySelector('.apv-gl-code'), glName = tr.querySelector('.apv-gl-name');
    glSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        if (q.length < 1) { glDrop.classList.add('hidden'); return; }
        const hits = GL_ACCOUNTS.filter(a => (a.search||'').includes(q)).slice(0, 50);
        glDrop.innerHTML = hits.length ? hits.map(a => `<div class="px-2 py-1 hover:bg-blue-600 cursor-pointer border-b border-gray-700 gl-o" data-code="${a.code}" data-name="${a.name}"><span class="font-mono font-semibold">${a.code}</span> <span class="text-gray-400">${a.name}</span></div>`).join('') : '<div class="px-2 py-1 text-gray-400">No matches</div>';
        glDrop.classList.remove('hidden');
        glDrop.querySelectorAll('.gl-o').forEach(o => o.addEventListener('mousedown', function(e) {
            e.preventDefault(); glSearch.value = this.dataset.code+' — '+this.dataset.name; glCode.value = this.dataset.code; glName.value = this.dataset.name; glDrop.classList.add('hidden');
        }));
    });
    glSearch.addEventListener('blur', () => setTimeout(() => glDrop.classList.add('hidden'), 200));

    // Item code typeahead
    const itemSearch  = tr.querySelector('.apv-item-search');
    const itemDrop    = tr.querySelector('.apv-item-drop');
    const itemCodeVal = tr.querySelector('.apv-item-code-val');
    const ITEM_URL    = '{{ route("accounts_payable_invoices.search_items") }}';
    let itemDebounce;
    const ITEM_HEADER = '<div class="sticky top-0 bg-gray-700 px-3 py-1.5 text-xs text-gray-300 font-semibold border-b border-gray-600">Select an item</div>';
    itemSearch.addEventListener('input', function() {
        clearTimeout(itemDebounce);
        const q = this.value.trim();
        if (q.length < 1) { itemDrop.classList.add('hidden'); return; }
        itemDebounce = setTimeout(async () => {
            try {
                const res   = await fetch(`${ITEM_URL}?q=${encodeURIComponent(q)}`);
                const items = await res.json();
                if (!items.length) {
                    itemDrop.innerHTML = ITEM_HEADER + '<div class="px-4 py-3 text-gray-400 text-xs">No items found</div>';
                } else {
                    itemDrop.innerHTML = ITEM_HEADER + items.map(i =>
                        `<div class="item-o px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer text-white border-b border-gray-700 transition-colors"
                              data-code="${(i.item_code||'').replace(/"/g,'&quot;')}"
                              data-desc="${(i.item_description||'').replace(/"/g,'&quot;')}"
                              data-cat="${(i.item_category||'').replace(/"/g,'&quot;')}"
                              data-brand="${(i.brand||'').replace(/"/g,'&quot;')}">
                            <div class="font-semibold text-xs mb-0.5">${i.item_description||''}</div>
                            <div class="flex gap-2 text-xs text-gray-300">
                                <span class="bg-gray-700 px-1.5 py-0.5 rounded">${i.brand||''}</span>
                                <span>${i.item_category||''}</span>
                                <span class="text-yellow-300 font-mono">${i.item_code||''}</span>
                            </div>
                        </div>`
                    ).join('');
                    itemDrop.querySelectorAll('.item-o').forEach(o => o.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        itemSearch.value  = this.dataset.code;
                        itemCodeVal.value = this.dataset.code;
                        const particulars = tr.querySelector('.apv-particulars');
                        if (particulars) particulars.value = this.dataset.desc;
                        itemDrop.classList.add('hidden');
                    }));
                }
                itemDrop.classList.remove('hidden');
            } catch(err) { itemDrop.classList.add('hidden'); }
        }, 250);
    });
    itemSearch.addEventListener('blur', () => setTimeout(() => itemDrop.classList.add('hidden'), 200));

    reorderApvRows(); recalcApvSummary();
}

function removeApvRow(btn) { btn.closest('tr').remove(); reorderApvRows(); recalcApvSummary(); }

function reorderApvRows() {
    document.querySelectorAll('#apvItemsBody tr').forEach((tr, i) => {
        const n = tr.querySelector('.row-num'); if (n) n.textContent = i + 1;
        tr.querySelectorAll('input,select').forEach(el => { const nm = el.getAttribute('name'); if (nm) el.setAttribute('name', nm.replace(/items\[\d+\]/, `items[${i}]`)); });
    });
    apvRowCount = document.querySelectorAll('#apvItemsBody tr').length;
}

function recalcApvSummary() {
    let gross = 0, vat = 0, ewt = 0;
    document.querySelectorAll('#apvItemsBody tr').forEach(tr => {
        const g = parseFloat(tr.querySelector('.apv-gross')?.value)||0;
        const v = tr.querySelector('.apv-vat')?.checked;
        const tc = tr.querySelector('.apv-tax-code')?.value||'';
        const iv = v ? g*12/112 : 0; const net = v ? g*100/112 : g;
        gross += g; vat += iv; ewt += net*(tc==='158'?.01:tc==='160'?.02:0);
    });
    const fmt = v => '₱'+v.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
    document.getElementById('apvSumGross').textContent    = fmt(gross);
    document.getElementById('apvSumVat').textContent      = fmt(vat);
    document.getElementById('apvSumNetVat').textContent   = fmt(gross-vat);
    document.getElementById('apvSumEwt').textContent      = '('+fmt(ewt)+')';
    document.getElementById('apvSumAmountDue').textContent = fmt(gross-ewt);
}

// ── Vendor Search Dropdown ──────────────────────────────────────────────────
(function() {
    const VENDOR_URL   = '{{ route("accounts_payable_invoices.search_vendors") }}';
    const searchInput = document.getElementById('supplier_search_input');
    const dropdown    = document.getElementById('supplier_search_dropdown');
    let debounce;

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 1) { dropdown.classList.add('hidden'); return; }
        debounce = setTimeout(async () => {
            try {
                const res   = await fetch(`${VENDOR_URL}?q=${encodeURIComponent(q)}`);
                const items = await res.json();
                if (!items.length) { dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No vendors found</div>'; dropdown.classList.remove('hidden'); return; }
                dropdown.innerHTML = items.map(s =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 supplier-opt"
                          data-name="${(s.vendor_name||'').replace(/"/g,'&quot;')}"
                          data-code="${(s.vendor_code||'').replace(/"/g,'&quot;')}">
                        <span class="font-semibold">${s.vendor_name}</span>
                        <span class="text-gray-400 ml-2 text-xs">${s.vendor_code||''}</span>
                    </div>`
                ).join('');
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.supplier-opt').forEach(opt => {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        document.getElementById('vendor_name').value = this.dataset.name;
                        document.getElementById('vendor_code').value = this.dataset.code;
                        searchInput.value = this.dataset.name;
                        dropdown.classList.add('hidden');
                    });
                });
            } catch(e) { dropdown.classList.add('hidden'); }
        }, 250);
    });

    searchInput.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('hidden'), 200));
})();
</script>
@endsection
