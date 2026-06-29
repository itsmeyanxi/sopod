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
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ icon: 'error', title: 'Validation Error', html: '{!! implode("<br>", $errors->all()) !!}' });
        });
        </script>
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

        <!-- CAR Reference Selector -->
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-3">LINK TO CASH ADVANCE REQUEST <span class="text-gray-400 text-xs font-normal">(optional)</span></h3>
            @if($invoice->cashAdvanceRequest)
            <div class="p-2 bg-green-900 border border-green-700 rounded flex items-center gap-2 text-green-300 text-sm mb-2">
                <i class="fas fa-link"></i>
                <span class="font-semibold">Linked to CAR: {{ $invoice->cashAdvanceRequest->car_no }}</span>
                <span class="text-gray-300">{{ $invoice->cashAdvanceRequest->payee }} | ₱{{ number_format($invoice->cashAdvanceRequest->amount_advanced, 2) }}</span>
            </div>
            @endif
            <div id="carSearchSection">
                <label class="block text-gray-300 text-sm mb-1">Search Approved Cash Advance Request</label>
                <div class="relative">
                    <input type="text" id="carSearchInput"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Search by CAR No, Payee, or Department..."
                        value="{{ $invoice->cashAdvanceRequest->car_no ?? '' }}" />
                    <div id="carSearchResults" class="hidden absolute z-10 w-full mt-2 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-96 overflow-y-auto"></div>
                </div>
            </div>
            <div id="linkedCarBadge"></div>
        </div>

        <form action="{{ route('accounts_payable_invoices.update', $invoice->id) }}" method="POST" id="apvForm">
            @csrf
            @method('PUT')

            <input type="hidden" name="cash_advance_request_id" id="carId" value="{{ old('cash_advance_request_id', $invoice->cash_advance_request_id) }}">

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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR CODE:</label>
                        <input type="text" name="vendor_code" id="vendor_code" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_code', $invoice->vendor_code) }}">
                    </div>
                    <div class="relative">
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR NAME: <span class="text-red-700">*</span></label>
                        <input type="text" name="vendor_name" id="vendor_name" autocomplete="off" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_name', $invoice->vendor_name) }}" required placeholder="Type to search vendor...">
                        <div id="vendor_name_dropdown" class="hidden absolute z-50 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-52 overflow-y-auto" style="top:100%;margin-top:2px;"></div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR ADDRESS:</label>
                        <textarea name="vendor_address" id="vendor_address" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('vendor_address', $invoice->vendor_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">VENDOR TIN:</label>
                        <input type="text" name="vendor_tin" id="vendor_tin" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_tin', $invoice->vendor_tin) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">PO NUMBER:</label>
                        <input type="text" name="purchase_order_no" id="purchase_order_no" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('purchase_order_no', $invoice->purchase_order_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">REFERENCE NO.:</label>
                        <input type="text" name="reference_no" id="reference_no_vendor" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_no', $invoice->reference_no) }}" placeholder="Manual entry">
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
                <div style="overflow-x:auto;overflow-y:visible;">
                    <table class="w-full text-sm border-collapse" style="overflow:visible;">
                        <thead class="bg-red-700 text-white text-xs uppercase">
                            <tr>
                                <th class="border border-gray-600 px-2 py-2" style="width:28px">#</th>
                                <th class="border border-gray-600 px-2 py-2" style="min-width:180px">PARTICULARS</th>
                                <th class="border border-gray-600 px-2 py-2 apv-item-code-th" style="min-width:130px">ITEM CODE</th>
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

            <!-- Computed Summary (compact) -->
            <div class="mb-4 flex justify-end">
                <div class="bg-gray-900 border border-gray-700 rounded px-3 py-2 text-xs" style="min-width:220px;">
                    <div class="flex justify-between gap-4"><span class="text-gray-400">Gross:</span><span class="font-bold text-white" id="apvSumGross">₱0.00</span></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-400">VAT (12%):</span><span class="text-yellow-400" id="apvSumVat">₱0.00</span></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-400">Net of VAT:</span><span class="text-blue-400" id="apvSumNetVat">₱0.00</span></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-400">Wtax:</span><span class="text-red-400" id="apvSumEwt">(₱0.00)</span></div>
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
const DEPT_CODES  = @json($deptCodes);
const DIV_CODES   = @json($divCodes);
function buildCcOptions(codes, selected) {
    let opts = '<option value="">—</option>';
    let found = false;
    codes.forEach(c => {
        const sel = selected && (c.name === selected || c.code === selected) ? 'selected' : '';
        if (sel) found = true;
        opts += `<option value="${c.name}" ${sel}>${c.name}</option>`;
    });
    if (selected && !found) opts += `<option value="${selected}" selected>${selected}</option>`;
    return opts;
}
let apvRowCount = 0;

function addApvRow(data) {
    data = data || {};
    const idx = apvRowCount++;
    const tr = document.createElement('tr');
    tr.className = 'bg-gray-800 hover:bg-gray-750 border-b border-gray-600';
    tr.innerHTML = `
        <td class="border border-gray-600 px-2 py-2 text-center text-gray-300 text-xs font-semibold row-num">${document.querySelectorAll('#apvItemsBody tr').length + 1}</td>
        <td class="border border-gray-600 px-1 py-1" style="position:relative">
            <input type="text" name="items[${idx}][particulars]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-particulars placeholder-gray-400" placeholder="Search description..." autocomplete="off" value="${(data.particulars||'').replace(/"/g,'&quot;')}">
            <div class="apv-part-drop hidden bg-gray-800 border-2 border-gray-500 rounded-lg shadow-2xl overflow-y-auto" style="min-width:320px;"></div>
        </td>
        <td class="border border-gray-600 px-1 py-1 apv-item-code-td" style="display:${window.apvIsService?'none':''}">
            <input type="text" name="items[${idx}][item_code]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-item-code-val placeholder-gray-400" placeholder="Item code" value="${data.item_code||''}">
        </td>
        <td class="border border-gray-600 px-1 py-1"><select name="items[${idx}][department]" class="w-full px-1 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs">${buildCcOptions(DEPT_CODES, data.department||'')}</select></td>
        <td class="border border-gray-600 px-1 py-1"><select name="items[${idx}][division]" class="w-full px-1 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs">${buildCcOptions(DIV_CODES, data.division||'')}</select></td>
        <td class="border border-gray-600 px-1 py-1 text-center"><input type="checkbox" name="items[${idx}][vat]" value="1" class="apv-vat w-4 h-4 accent-yellow-400" ${data.vat?'checked':''} onchange="recalcApvSummary()"></td>
        <td class="border border-gray-600 px-1 py-1">
            <select name="items[${idx}][tax_code]" class="w-full px-1 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-tax-code" onchange="recalcApvSummary()">
                <option value="">—</option>
                <option value="C158" ${data.tax_code==='C158'||data.tax_code==='158'?'selected':''}>C158 - Goods (1%)</option>
                <option value="C160" ${data.tax_code==='C160'||data.tax_code==='160'?'selected':''}>C160 - Services (2%)</option>
                <option value="C100" ${data.tax_code==='C100'?'selected':''}>C100 - Rental (5%)</option>
                <option value="I010" ${data.tax_code==='I010'?'selected':''}>I010 - Professional (5%)</option>
                <option value="I011" ${data.tax_code==='I011'?'selected':''}>I011 - Professional (10%)</option>
            </select>
        </td>
        <td class="border border-gray-600 px-1 py-1">
            <input type="text" name="items[${idx}][account_code]" class="w-full px-2 py-1.5 bg-gray-600 border border-gray-500 rounded text-gray-300 text-xs apv-gl-code" value="${data.account_code||''}" readonly placeholder="Auto-filled">
        </td>
        <td class="border border-gray-600 px-1 py-1" style="position:relative">
            <input type="text" name="items[${idx}][account_name]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-gl-search placeholder-gray-400" placeholder="Search account name..." autocomplete="off" value="${data.account_name||''}">
            <div class="apv-gl-drop hidden bg-gray-800 border border-gray-600 rounded shadow-lg text-xs overflow-y-auto" style="min-width:280px;"></div>
        </td>
        <td class="border border-gray-600 px-1 py-1">
            <input type="number" step="0.01" name="items[${idx}][gross_amount]" class="w-full px-2 py-1.5 bg-gray-700 border border-gray-500 rounded text-white text-xs apv-gross placeholder-gray-400" value="${data.gross_amount||''}" required oninput="updateDebitCredit(this);recalcApvSummary()" placeholder="+Debit / -Credit">
            <div class="text-center text-xs mt-0.5 apv-dc-label font-semibold ${data.gross_amount < 0 ? 'text-red-400' : (data.gross_amount > 0 ? 'text-green-400' : '')}">${data.gross_amount < 0 ? 'CREDIT' : (data.gross_amount > 0 ? 'DEBIT' : '')}</div>
        </td>
        <td class="border border-gray-600 px-1 py-2 text-center"><button type="button" onclick="removeApvRow(this)" class="text-red-400 hover:text-red-300"><i class="fas fa-trash text-xs"></i></button></td>
    `;
    document.getElementById('apvItemsBody').appendChild(tr);

    // GL Account search — account name field; paste autofills code
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
            } catch(err) { partDrop.classList.add('hidden'); }
        }, 250);
    });
    partInput.addEventListener('blur', () => setTimeout(() => partDrop.classList.add('hidden'), 200));

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

function updateDebitCredit(input) {
    const lbl = input.closest('td').querySelector('.apv-dc-label');
    if (!lbl) return;
    const v = parseFloat(input.value);
    if (isNaN(v)||v===0){lbl.textContent='';lbl.className='text-center text-xs mt-0.5 apv-dc-label font-semibold';}
    else if(v>0){lbl.textContent='DEBIT';lbl.className='text-center text-xs mt-0.5 apv-dc-label font-semibold text-green-400';}
    else{lbl.textContent='CREDIT';lbl.className='text-center text-xs mt-0.5 apv-dc-label font-semibold text-red-400';}
}

function recalcApvSummary() {
    let gross = 0, vat = 0, ewt = 0;
    document.querySelectorAll('#apvItemsBody tr').forEach(tr => {
        const raw = parseFloat(tr.querySelector('.apv-gross')?.value)||0;
        const g = Math.abs(raw);
        const v = tr.querySelector('.apv-vat')?.checked;
        const tc = tr.querySelector('.apv-tax-code')?.value||'';
        const iv = v ? g*12/112 : 0; const net = v ? g*100/112 : g;
        const ewtRates={'C158':.01,'158':.01,'C160':.02,'160':.02,'C100':.05,'I010':.05,'I011':.10};
        gross += raw; vat += iv; ewt += net*(ewtRates[tc]||0);
    });
    const fmt = v => '₱'+Math.abs(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
    document.getElementById('apvSumGross').textContent    = fmt(gross);
    document.getElementById('apvSumVat').textContent      = fmt(vat);
    document.getElementById('apvSumNetVat').textContent   = fmt(gross-vat);
    document.getElementById('apvSumEwt').textContent      = '('+fmt(ewt)+')';
    document.getElementById('apvSumAmountDue').textContent = fmt(gross-ewt);
}

// ── Service PO Toggle ─────────────────────────────────────────────────────────
window.apvIsService = {{ optional($invoice->requestForPayment?->purchaseOrder)->po_type === 'service' ? 'true' : 'false' }};
function toggleServiceMode(isService) {
    window.apvIsService = isService;
    document.querySelectorAll('.apv-item-code-th, .apv-item-code-td').forEach(el => {
        el.style.display = isService ? 'none' : '';
    });
}
if (window.apvIsService) toggleServiceMode(true);

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

// CAR Search
(function() {
    const carSearchInput = document.getElementById('carSearchInput');
    const carSearchResults = document.getElementById('carSearchResults');
    let carSearchTimer;
    if (!carSearchInput) return;

    carSearchInput.addEventListener('input', function() {
        clearTimeout(carSearchTimer);
        const searchTerm = this.value.trim();
        if (searchTerm.length < 2) { carSearchResults.classList.add('hidden'); return; }
        carSearchTimer = setTimeout(() => {
            fetch(`{{ route('accounts_payable_invoices.search_cars') }}?search=${encodeURIComponent(searchTerm)}`)
                .then(r => r.json())
                .then(cars => {
                    if (cars.length === 0) {
                        carSearchResults.innerHTML = '<div class="p-4 text-gray-300">No approved CARs found</div>';
                        carSearchResults.classList.remove('hidden');
                        return;
                    }
                    let html = '';
                    cars.forEach(car => {
                        html += `
                            <div class="car-result-item block p-3 hover:bg-gray-700 transition cursor-pointer"
                                 data-id="${car.id}"
                                 data-car-no="${(car.car_no || '').replace(/"/g, '&quot;')}"
                                 data-payee="${(car.payee || '').replace(/"/g, '&quot;')}"
                                 data-department="${(car.department || '').replace(/"/g, '&quot;')}"
                                 data-amount="${car.amount || 0}"
                                 data-purpose="${(car.purpose || '').replace(/"/g, '&quot;')}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-semibold text-purple-400">${car.car_no}</div>
                                        <div class="text-sm text-gray-300">${car.payee || car.department}</div>
                                        <div class="text-xs text-gray-400">${car.purpose || ''}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-green-400">₱${parseFloat(car.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                    </div>
                                </div>
                            </div>`;
                    });
                    carSearchResults.innerHTML = html;
                    carSearchResults.classList.remove('hidden');

                    carSearchResults.querySelectorAll('.car-result-item').forEach(item => {
                        item.addEventListener('click', function() {
                            document.getElementById('carId').value = this.dataset.id;
                            const badge = document.getElementById('linkedCarBadge');
                            badge.innerHTML = `<div class="mt-3 p-2 bg-green-900 border border-green-700 rounded flex items-center gap-2 text-green-300 text-sm">
                                <i class="fas fa-link"></i>
                                <span class="font-semibold">Linked to CAR: ${this.dataset.carNo}</span>
                                <span class="text-gray-300">${this.dataset.payee || this.dataset.department} | ₱${parseFloat(this.dataset.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                            </div>`;
                            carSearchResults.classList.add('hidden');
                            carSearchInput.value = this.dataset.carNo;
                        });
                    });
                })
                .catch(() => {
                    carSearchResults.innerHTML = '<div class="p-4 text-red-400">Error searching CARs</div>';
                    carSearchResults.classList.remove('hidden');
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!carSearchInput.contains(e.target) && !carSearchResults.contains(e.target)) {
            carSearchResults.classList.add('hidden');
        }
    });
})();
</script>

@endsection
