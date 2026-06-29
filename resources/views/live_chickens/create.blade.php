@extends('layouts.app')

@section('title', 'New Live Chicken Record')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">🐔 New Live Chicken Record</h1>
            <a href="{{ route('live_chickens.index') }}" class="text-gray-400 hover:text-white text-sm">← Back to List</a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('live_chickens.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Date --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Date <span class="text-red-400">*</span></label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- PO Number --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">PO Number</label>
                    <div class="relative">
                        <input type="text" id="po_search" placeholder="Search PO..."
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                               value="{{ old('po_no') }}" autocomplete="off">
                        <input type="hidden" name="po_no" id="po_no_val" value="{{ old('po_no') }}">
                        <div id="po_dropdown" class="absolute z-50 bg-gray-900 border border-gray-700 rounded w-full hidden max-h-48 overflow-y-auto shadow-lg"></div>
                    </div>
                    <div id="po_info" class="mt-1 text-xs text-yellow-300 hidden">
                        PO Qty: <span id="po_qty_display">0</span>
                    </div>
                </div>

                {{-- RR Number --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">RR Number</label>
                    <div class="relative">
                        <input type="text" id="rr_search" placeholder="Search RR / SRR..."
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                               autocomplete="off">
                        <input type="hidden" name="srr_code" id="srr_code_val" value="{{ old('srr_code') }}">
                        <div id="rr_dropdown" class="absolute z-50 bg-gray-900 border border-gray-700 rounded w-full hidden max-h-48 overflow-y-auto shadow-lg"></div>
                    </div>
                    <div id="rr_info" class="mt-1 text-xs text-blue-300 hidden">
                        RR: <span id="rr_code_display"></span>
                    </div>
                </div>

                {{-- RFP Number --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">RFP Number</label>
                    <div class="relative">
                        <input type="text" id="rfp_search" placeholder="Search RFP..."
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-green-500 focus:outline-none"
                               autocomplete="off">
                        <input type="hidden" name="rfp_no" id="rfp_no_val" value="{{ old('rfp_no') }}">
                        <div id="rfp_dropdown" class="absolute z-50 bg-gray-900 border border-gray-700 rounded w-full hidden max-h-48 overflow-y-auto shadow-lg"></div>
                    </div>
                    <div id="rfp_info" class="mt-1 text-xs text-green-300 hidden">
                        RFP: <span id="rfp_no_display"></span>
                    </div>
                </div>

                {{-- Reference Number --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Reference Number</label>
                    <div class="relative">
                        <input type="text" id="ref_search" placeholder="Search by reference number..."
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                               value="{{ old('reference_number') }}" autocomplete="off">
                        <input type="hidden" name="reference_number" id="ref_val" value="{{ old('reference_number') }}">
                        <div id="ref_dropdown" class="absolute z-50 bg-gray-900 border border-gray-700 rounded w-full hidden max-h-48 overflow-y-auto shadow-lg"></div>
                    </div>
                    <div id="ref_info" class="mt-1 text-xs text-yellow-300 hidden">
                        Ref: <span id="ref_display"></span>
                    </div>
                </div>

                {{-- Supplier --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Supplier <span class="text-red-400">*</span></label>
                    <input type="text" name="supplier" value="{{ old('supplier') }}" id="supplier_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Brand --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand') }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Items Table (full width) --}}
                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-gray-300 text-sm font-semibold">Items <span class="text-red-400">*</span></label>
                        <button type="button" onclick="addItemRow()" class="bg-purple-600 hover:bg-purple-700 text-white text-xs px-2 py-1 rounded">+ Add Row</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs bg-gray-900 rounded border border-gray-700" id="items_table">
                            <thead class="bg-gray-700 text-gray-300">
                                <tr>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-28">Brand</th>
                                    <th class="px-2 py-2 text-left w-20">Qty</th>
                                    <th class="px-2 py-2 text-left w-20">UOM</th>
                                    <th class="px-2 py-2 text-left w-24">Unit Price</th>
                                    <th class="px-2 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="items_tbody"></tbody>
                        </table>
                    </div>
                    <input type="hidden" name="items" id="items_hidden">
                    <input type="hidden" name="items_data" id="items_data_hidden">
                </div>

                {{-- Price --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Price <span class="text-red-400">*</span></label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" step="0.01" min="0" id="price_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <input type="hidden" name="actual_qty" id="actual_qty_field" value="0">

                {{-- Amount (auto-calc) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Amount <span class="text-red-400">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount', 0) }}" step="0.01" min="0" id="amount_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Delivery Date --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Date</label>
                    <input type="date" name="delivery_date" value="{{ old('delivery_date') }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Delivery Week No --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Week No.</label>
                    <input type="text" name="delivery_week_no" value="{{ old('delivery_week_no') }}" placeholder="e.g. WK01"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Status <span class="text-red-400">*</span></label>
                    <select name="status"
                            class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        @foreach(\App\Models\LiveChicken::STATUSES as $s)
                            <option value="{{ $s }}" @selected(old('status', 'Ongoing') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            {{-- Docs Section --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Docs Required --}}
                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                    <label class="block text-gray-200 font-semibold mb-3">Docs Required</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="file" id="req_type_file"
                                   @checked(old('docs_required_type') === 'file') class="text-purple-500">
                            <span class="text-sm text-gray-300">File Attachment</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="date" id="req_type_date"
                                   @checked(old('docs_required_type') === 'date') class="text-purple-500">
                            <span class="text-sm text-gray-300">Date</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="" id="req_type_none"
                                   @checked(!old('docs_required_type')) class="text-purple-500">
                            <span class="text-sm text-gray-300">None</span>
                        </label>
                    </div>
                    <div id="req_file_input" class="hidden">
                        <input type="file" name="docs_required_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-gray-300 text-sm">
                    </div>
                    <div id="req_date_input" class="hidden">
                        <input type="date" name="docs_required_date" value="{{ old('docs_required_date') }}"
                               class="w-full bg-gray-800 border border-gray-600 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

                {{-- Docs Transmitted --}}
                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                    <label class="block text-gray-200 font-semibold mb-3">Docs Transmitted</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="file" id="trans_type_file"
                                   @checked(old('docs_transmitted_type') === 'file') class="text-purple-500">
                            <span class="text-sm text-gray-300">File Attachment</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="date" id="trans_type_date"
                                   @checked(old('docs_transmitted_type') === 'date') class="text-purple-500">
                            <span class="text-sm text-gray-300">Date</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="" id="trans_type_none"
                                   @checked(!old('docs_transmitted_type')) class="text-purple-500">
                            <span class="text-sm text-gray-300">None</span>
                        </label>
                    </div>
                    <div id="trans_file_input" class="hidden">
                        <input type="file" name="docs_transmitted_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-gray-300 text-sm">
                    </div>
                    <div id="trans_date_input" class="hidden">
                        <input type="date" name="docs_transmitted_date" value="{{ old('docs_transmitted_date') }}"
                               class="w-full bg-gray-800 border border-gray-600 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded font-semibold">
                    Save Record
                </button>
                <a href="{{ route('live_chickens.index') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<script>
// Docs toggle
function setupDocToggle(radioName, fileDiv, dateDiv) {
    const radios = document.querySelectorAll(`input[name="${radioName}"]`);
    function toggle() {
        const val = document.querySelector(`input[name="${radioName}"]:checked`)?.value;
        document.getElementById(fileDiv).classList.toggle('hidden', val !== 'file');
        document.getElementById(dateDiv).classList.toggle('hidden', val !== 'date');
    }
    radios.forEach(r => r.addEventListener('change', toggle));
    toggle();
}
setupDocToggle('docs_required_type', 'req_file_input', 'req_date_input');
setupDocToggle('docs_transmitted_type', 'trans_file_input', 'trans_date_input');

// Auto-calculate amount from sum of item quantities
let poQty = 0;
function getItemQtySum() {
    let total = 0;
    document.querySelectorAll('#items_tbody .item-qty').forEach(i => { total += parseFloat(i.value) || 0; });
    return total;
}
function recalcAmount() {
    const price = parseFloat(document.getElementById('price_field').value) || 0;
    const qty   = getItemQtySum();
    document.getElementById('actual_qty_field').value = qty.toFixed(2);
    document.getElementById('amount_field').value = (price * qty).toFixed(2);
}
document.getElementById('price_field').addEventListener('input', recalcAmount);
document.getElementById('items_tbody').addEventListener('input', function(e) {
    if (e.target.classList.contains('item-qty')) recalcAmount();
});

// PO search
const poSearch    = document.getElementById('po_search');
const poValInput  = document.getElementById('po_no_val');
const poDropdown  = document.getElementById('po_dropdown');
const poInfo      = document.getElementById('po_info');
const poQtyDisp   = document.getElementById('po_qty_display');
const supplierFld = document.getElementById('supplier_field');
const brandFld    = document.querySelector('input[name="brand"]');
const itemsFld    = document.getElementById('items_search');

let debounce;
poSearch.addEventListener('input', function () {
    clearTimeout(debounce);
    const q = this.value.trim();
    if (!q) { poDropdown.classList.add('hidden'); return; }
    debounce = setTimeout(() => {
        fetch(`{{ route('live_chickens.searchPOs') }}?search=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                poDropdown.innerHTML = '';
                if (!data.length) { poDropdown.classList.add('hidden'); return; }
                data.forEach(po => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm border-b border-gray-800';
                    div.innerHTML = `<span class="text-purple-300 font-mono">${po.po_no}</span>`
                        + `<span class="text-gray-400 ml-2">${po.supplier || ''}</span>`
                        + (po.brand ? `<span class="text-yellow-400 ml-2 text-xs">${po.brand}</span>` : '')
                        + `<span class="text-gray-500 ml-2 text-xs">Qty: ${po.po_qty}</span>`;
                    div.addEventListener('click', () => {
                        poSearch.value    = po.po_no;
                        poValInput.value  = po.po_no;
                        if (po.reference_number) {
                            document.getElementById('ref_search').value = po.reference_number;
                            document.getElementById('ref_val').value    = po.reference_number;
                            document.getElementById('ref_display').textContent = po.reference_number;
                            document.getElementById('ref_info').classList.remove('hidden');
                        }
                        supplierFld.value = po.supplier || '';
                        if (brandFld && po.brand) brandFld.value = po.brand;
                        if (po.price) document.getElementById('price_field').value = parseFloat(po.price).toFixed(2);
                        if (po.po_items && po.po_items.length) {
                            document.getElementById('items_tbody').innerHTML = '';
                            po.po_items.forEach(item => addItemRow(item));
                        }
                        poQty = po.po_qty;
                        poQtyDisp.textContent = po.po_qty;
                        poInfo.classList.remove('hidden');
                        recalcAmount();
                        poDropdown.classList.add('hidden');
                    });
                    poDropdown.appendChild(div);
                });
                poDropdown.classList.remove('hidden');
            });
    }, 300);
});

// RFP search
const rfpSearch   = document.getElementById('rfp_search');
const rfpValInput = document.getElementById('rfp_no_val');
const rfpDropdown = document.getElementById('rfp_dropdown');
const rfpInfo     = document.getElementById('rfp_info');
const rfpNoDisp   = document.getElementById('rfp_no_display');
let rfpDebounce;

rfpSearch.addEventListener('input', function () {
    clearTimeout(rfpDebounce);
    const q = this.value.trim();
    if (!q) { rfpDropdown.classList.add('hidden'); return; }
    rfpDebounce = setTimeout(() => {
        fetch(`{{ route('live_chickens.searchRFPs') }}?search=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                rfpDropdown.innerHTML = '';
                if (!data.length) { rfpDropdown.classList.add('hidden'); return; }
                data.forEach(rfp => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm border-b border-gray-800';
                    div.innerHTML = `<span class="text-green-300 font-mono">${rfp.rfp_no}</span>`
                        + `<span class="text-gray-400 ml-2">${rfp.payee || ''}</span>`
                        + (rfp.po_no ? `<span class="text-yellow-400 ml-2 text-xs">PO: ${rfp.po_no}</span>` : '');
                    div.addEventListener('click', () => {
                        rfpSearch.value   = rfp.rfp_no;
                        rfpValInput.value = rfp.rfp_no;
                        if (rfp.po_no) { poSearch.value = rfp.po_no; poValInput.value = rfp.po_no; }
                        supplierFld.value = rfp.payee || '';
                        if (brandFld && rfp.brand) brandFld.value = rfp.brand;
                        if (rfp.reference_number) {
                            document.getElementById('ref_search').value = rfp.reference_number;
                            document.getElementById('ref_val').value    = rfp.reference_number;
                            document.getElementById('ref_display').textContent = rfp.reference_number;
                            document.getElementById('ref_info').classList.remove('hidden');
                        }
                        if (rfp.price) document.getElementById('price_field').value = parseFloat(rfp.price).toFixed(2);
                        if (rfp.rfp_items && rfp.rfp_items.length) {
                            document.getElementById('items_tbody').innerHTML = '';
                            rfp.rfp_items.forEach(item => addItemRow(item));
                        }
                        rfpNoDisp.textContent = rfp.rfp_no;
                        rfpInfo.classList.remove('hidden');
                        recalcAmount();
                        rfpDropdown.classList.add('hidden');
                    });
                    rfpDropdown.appendChild(div);
                });
                rfpDropdown.classList.remove('hidden');
            });
    }, 300);
});

// RR search
const rrSearch   = document.getElementById('rr_search');
const rrValInput = document.getElementById('srr_code_val');
const rrDropdown = document.getElementById('rr_dropdown');
const rrInfo     = document.getElementById('rr_info');
const rrCodeDisp = document.getElementById('rr_code_display');
let rrDebounce;

rrSearch.addEventListener('input', function () {
    clearTimeout(rrDebounce);
    const q = this.value.trim();
    if (!q) { rrDropdown.classList.add('hidden'); return; }
    rrDebounce = setTimeout(() => {
        fetch(`{{ route('live_chickens.searchSRRs') }}?search=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                rrDropdown.innerHTML = '';
                if (!data.length) { rrDropdown.classList.add('hidden'); return; }
                data.forEach(srr => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm border-b border-gray-800';
                    div.innerHTML = `<span class="text-blue-300 font-mono">${srr.srr_code}</span>`
                        + `<span class="text-gray-400 ml-2">${srr.supplier_name || ''}</span>`
                        + (srr.po_no ? `<span class="text-yellow-400 ml-2 text-xs">PO: ${srr.po_no}</span>` : '');
                    div.addEventListener('click', () => {
                        rrSearch.value   = srr.srr_code;
                        rrValInput.value = srr.srr_code;
                        if (srr.po_no) { poSearch.value = srr.po_no; poValInput.value = srr.po_no; }
                        if (srr.reference_number) {
                            document.getElementById('ref_search').value = srr.reference_number;
                            document.getElementById('ref_val').value    = srr.reference_number;
                            document.getElementById('ref_display').textContent = srr.reference_number;
                            document.getElementById('ref_info').classList.remove('hidden');
                        }
                        if (srr.srr_items && srr.srr_items.length) {
                            document.getElementById('items_tbody').innerHTML = '';
                            srr.srr_items.forEach(item => addItemRow(item));
                        }
                        rrCodeDisp.textContent = srr.srr_code;
                        rrInfo.classList.remove('hidden');
                        recalcAmount();
                        rrDropdown.classList.add('hidden');
                    });
                    rrDropdown.appendChild(div);
                });
                rrDropdown.classList.remove('hidden');
            });
    }, 300);
});

// Reference number search
const refSearch   = document.getElementById('ref_search');
const refValInput = document.getElementById('ref_val');
const refDropdown = document.getElementById('ref_dropdown');
const refInfo     = document.getElementById('ref_info');
const refDisplay  = document.getElementById('ref_display');
let refDebounce;

refSearch.addEventListener('input', function () {
    refValInput.value = this.value;
    clearTimeout(refDebounce);
    const q = this.value.trim();
    if (!q) { refDropdown.classList.add('hidden'); return; }
    refDebounce = setTimeout(() => {
        fetch(`{{ route('live_chickens.searchPOs') }}?search=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                refDropdown.innerHTML = '';
                const withRef = data.filter(po => po.reference_number);
                if (!withRef.length) { refDropdown.classList.add('hidden'); return; }
                withRef.forEach(po => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm border-b border-gray-800';
                    div.innerHTML = `<span class="text-yellow-300 font-mono">${po.reference_number}</span>`
                        + `<span class="text-gray-400 ml-2">${po.supplier || ''}</span>`
                        + `<span class="text-purple-300 ml-2 text-xs">PO: ${po.po_no}</span>`;
                    div.addEventListener('click', () => {
                        refSearch.value   = po.reference_number;
                        refValInput.value = po.reference_number;
                        poSearch.value    = po.po_no;
                        poValInput.value  = po.po_no;
                        supplierFld.value = po.supplier || '';
                        if (brandFld && po.brand) brandFld.value = po.brand;
                        if (po.price) document.getElementById('price_field').value = parseFloat(po.price).toFixed(2);
                        if (po.po_items && po.po_items.length) {
                            document.getElementById('items_tbody').innerHTML = '';
                            po.po_items.forEach(item => addItemRow(item));
                        }
                        refDisplay.textContent = po.reference_number;
                        refInfo.classList.remove('hidden');
                        recalcAmount();
                        refDropdown.classList.add('hidden');
                    });
                    refDropdown.appendChild(div);
                });
                refDropdown.classList.remove('hidden');
            });
    }, 300);
});

document.addEventListener('click', e => {
    if (!poDropdown.contains(e.target) && e.target !== poSearch) poDropdown.classList.add('hidden');
    if (!rrDropdown.contains(e.target) && e.target !== rrSearch) rrDropdown.classList.add('hidden');
    if (!rfpDropdown.contains(e.target) && e.target !== rfpSearch) rfpDropdown.classList.add('hidden');
    if (!refDropdown.contains(e.target) && e.target !== refSearch) refDropdown.classList.add('hidden');
});

// Items table
function addItemRow(data = {}) {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-700';
    tr.innerHTML = `
        <td class="px-1 py-1"><input type="text" placeholder="Description" value="${data.description||''}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-desc"></td>
        <td class="px-1 py-1"><input type="text" placeholder="Brand" value="${data.brand||''}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-brand"></td>
        <td class="px-1 py-1"><input type="number" placeholder="0" value="${data.qty||''}" step="0.01" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-qty"></td>
        <td class="px-1 py-1"><input type="text" placeholder="kg" value="${data.uom||''}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-uom"></td>
        <td class="px-1 py-1"><input type="number" placeholder="0" value="${data.unit_price||''}" step="0.01" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-price"></td>
        <td class="px-1 py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-300 text-xs">✕</button></td>`;
    document.getElementById('items_tbody').appendChild(tr);
}
// Serialize items before submit
document.querySelector('form').addEventListener('submit', function() {
    const rows = document.querySelectorAll('#items_tbody tr');
    const data = [];
    rows.forEach(tr => {
        const desc = tr.querySelector('.item-desc')?.value?.trim();
        if (desc) data.push({
            description: desc,
            brand: tr.querySelector('.item-brand')?.value?.trim()||'',
            qty: parseFloat(tr.querySelector('.item-qty')?.value)||0,
            uom: tr.querySelector('.item-uom')?.value?.trim()||'',
            unit_price: parseFloat(tr.querySelector('.item-price')?.value)||0,
        });
    });
    document.getElementById('items_hidden').value = data.map(d=>d.description).join('\n');
    document.getElementById('items_data_hidden').value = JSON.stringify(data);
});
// Add one empty row by default
if (!document.querySelector('#items_tbody tr')) addItemRow();
</script>
@endsection
