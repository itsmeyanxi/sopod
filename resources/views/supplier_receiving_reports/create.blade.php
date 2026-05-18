@extends('layouts.app')

@section('title', 'Create Supply Receiving Report')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CREATE SUPPLY RECEIVING REPORT</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">SRR CODE:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $srrCode }}</span>
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

        <form action="{{ route('supplier_receiving_reports.store') }}" method="POST" id="srrForm">
            @csrf

            <!-- Top Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="report_date" value="{{ old('report_date', date('Y-m-d')) }}" required
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLY:</label>
                        <div class="relative">
                            <input type="text" id="supplier_search" placeholder="Search supplier..."
                                class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" autocomplete="off">
                            <input type="hidden" name="supplier_id" id="supplier_id" value="{{ old('supplier_id') }}">
                            <div id="supplierDropdown" class="hidden absolute z-10 w-full mt-1 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto shadow-lg"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CV NO:</label>
                        <input type="text" name="cv_no" value="{{ old('cv_no') }}" placeholder="Check Voucher Number"
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">STORAGE:</label>
                        <select name="storage" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Storage --</option>
                            @foreach($storages as $storage)
                                <option value="{{ $storage->storage_name }}" {{ old('storage') == $storage->storage_name ? 'selected' : '' }}>
                                    {{ $storage->storage_name }}
                                    @if($storage->warehouse)
                                        ({{ $storage->warehouse->warehouse_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PO NO:</label>
                        <div class="relative">
                            <input type="text" name="po_no" id="po_no" value="{{ old('po_no') }}" placeholder="Search or type PO number..."
                                class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" autocomplete="off">
                            <div id="poSearchResults" class="hidden absolute z-10 w-full mt-1 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto shadow-lg">
                            </div>
                        </div>
                        <!-- PO qty info strip -->
                        <div id="poQtyInfo" class="hidden mt-2 p-2 bg-gray-900 border border-gray-600 rounded text-sm text-gray-300">
                            PO qty: <span id="poQtyValue" class="font-bold text-white">0</span>
                            <span id="lcQtyContainer" class="hidden">&nbsp;|&nbsp; Live Chicken qty: <span id="lcQtyValue" class="font-bold text-yellow-300">0</span></span>
                            &nbsp;|&nbsp; SRR total qty: <span id="srrQtyValue" class="font-bold text-white">0</span>
                        </div>
                        <!-- Qty exceeded warning -->
                        <div id="qtyExceededWarning" class="hidden mt-2 p-3 bg-red-900 border border-red-600 rounded text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-red-300 font-semibold"><i class="fas fa-exclamation-triangle mr-1"></i>SRR qty exceeds PO ordered qty.</span>
                                <button type="button" onclick="reduceToPoQty()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-xs font-semibold ml-3">
                                    <i class="fas fa-arrow-down mr-1"></i> Reduce to PO Qty
                                </button>
                            </div>
                            <div class="text-red-400 text-xs mt-1">You must reduce the quantities or clear the PO link before submitting.</div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">REFERENCE NUMBER:</label>
                        <input type="text" name="reference_number" id="srr_reference_number" value="{{ old('reference_number') }}" placeholder="Auto-filled from PO or enter manually"
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">TYPE: <span class="text-red-700">*</span></label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="purchased" {{ old('report_type', 'purchased') == 'purchased' ? 'checked' : '' }}
                                    class="text-purple-500 focus:ring-purple-500">
                                <span class="text-gray-300">Purchased</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="stock_transfer" {{ old('report_type') == 'stock_transfer' ? 'checked' : '' }}
                                    class="text-purple-500 focus:ring-purple-500">
                                <span class="text-gray-300">Stock Transfer</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="backload" {{ old('report_type') == 'backload' ? 'checked' : '' }}
                                    class="text-purple-500 focus:ring-purple-500">
                                <span class="text-gray-300">Backload</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="returned" {{ old('report_type') == 'returned' ? 'checked' : '' }}
                                    class="text-purple-500 focus:ring-purple-500">
                                <span class="text-gray-300">Returned</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-lg text-gray-300">ITEMS</h3>
                    <button type="button" onclick="addRow()" class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700 transition">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-700" id="itemsTable">
                        <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                            <tr>
                                <th class="border border-gray-700 px-2 py-2 w-10">#</th>
                                <th class="border border-gray-700 px-2 py-2">ITEM CODE</th>
                                <th class="border border-gray-700 px-2 py-2">ITEM DESCRIPTION <span class="text-red-700">*</span></th>
                                <th class="border border-gray-700 px-2 py-2">BRAND</th>
                                <th class="border border-gray-700 px-2 py-2 w-28">NO. OF BOXES</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">NET WEIGHT</th>
                                <th class="border border-gray-700 px-2 py-2 w-28">PRODUCTION DATE</th>
                                <th class="border border-gray-700 px-2 py-2 w-36">EXPIRATION DATE</th>
                                <th class="border border-gray-700 px-2 py-2">PALLET NO.</th>
                                <th class="border border-gray-700 px-2 py-2">REMARKS</th>
                                <th class="border border-gray-700 px-2 py-2 w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr class="item-row">
                                <td class="border border-gray-700 px-2 py-2 text-center row-number">1</td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[0][item_code]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <div class="relative">
                                        <input type="text" name="items[0][item_description]" required class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded srr-desc-input" autocomplete="off">
                                        <div class="srr-desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                    </div>
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[0][brand]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="number" name="items[0][no_of_boxes]" value="0" min="0" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded boxes-input" onchange="calculateTotals()">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="number" name="items[0][net_weight]" value="0" min="0" step="0.01" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded weight-input" onchange="calculateTotals()">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="date" name="items[0][pd]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="date" name="items[0][expiry_date]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[0][pallet_no]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[0][remarks]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-2 py-2 text-center">
                                    <button type="button" onclick="removeRow(this)" class="text-red-700 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-700">
                            <tr>
                                <td colspan="4" class="border border-gray-700 px-4 py-2 text-right font-semibold text-gray-300">TOTALS:</td>
                                <td class="border border-gray-700 px-4 py-2 text-center font-semibold text-white" id="totalBoxes">0</td>
                                <td class="border border-gray-700 px-4 py-2 text-center font-semibold text-white" id="totalWeight">0.00</td>
                                <td colspan="5" class="border border-gray-700"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Note -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-1">NOTE:</label>
                <textarea name="note" rows="3" placeholder="Additional notes..."
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('note') }}</textarea>
            </div>

            <!-- Signature Fields -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">PREPARED BY:</label>
                    <input type="text" value="{{ auth()->user()->name }}" readonly
                        class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-gray-300 cursor-not-allowed">
                    <input type="hidden" name="prepared_by" value="{{ auth()->user()->name }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CHECKED BY:</label>
                    <input type="text" name="checked_by" value="{{ old('checked_by') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">RECEIVED BY:</label>
                    <input type="text" name="received_by" value="{{ old('received_by') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">VERIFIED BY:</label>
                    <input type="text" name="verified_by" value="{{ old('verified_by') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end gap-3 border-t border-gray-700 pt-4">
                <a href="{{ route('supplier_receiving_reports.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Save as Draft
                </button>
                <button type="submit" name="save_final" value="1" class="bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-2 rounded hover:from-green-700 hover:to-green-800 transition">
                    <i class="fas fa-check mr-1"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function escapeHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
let rowCount = 1;

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const index = tbody.querySelectorAll('.item-row').length;
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `
        <td class="border border-gray-700 px-2 py-2 text-center row-number">${index + 1}</td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="text" name="items[${index}][item_code]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <div class="relative"><input type="text" name="items[${index}][item_description]" required class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded srr-desc-input" autocomplete="off"><div class="srr-desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div></div>
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="text" name="items[${index}][brand]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="number" name="items[${index}][no_of_boxes]" value="0" min="0" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded boxes-input" onchange="calculateTotals()">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="number" name="items[${index}][net_weight]" value="0" min="0" step="0.01" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded weight-input" onchange="calculateTotals()">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="date" name="items[${index}][pd]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="date" name="items[${index}][expiry_date]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="text" name="items[${index}][pallet_no]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="text" name="items[${index}][remarks]" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
        </td>
        <td class="border border-gray-700 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="text-red-700 hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    renumberRows();
    const newDesc = row.querySelector('.srr-desc-input');
    if (newDesc) attachSrrDescAutocomplete(newDesc);
}

function removeRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.querySelectorAll('.item-row').length <= 1) {
        alert('At least one item is required.');
        return;
    }
    btn.closest('tr').remove();
    renumberRows();
    reindexItems();
    calculateTotals();
}

function renumberRows() {
    document.querySelectorAll('#itemsBody .item-row').forEach((row, idx) => {
        row.querySelector('.row-number').textContent = idx + 1;
    });
}

function reindexItems() {
    document.querySelectorAll('#itemsBody .item-row').forEach((row, idx) => {
        row.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${idx}]`));
            }
        });
    });
}

function calculateTotals() {
    let totalBoxes = 0;
    let totalWeight = 0;
    document.querySelectorAll('.boxes-input').forEach(input => {
        totalBoxes += parseInt(input.value) || 0;
    });
    document.querySelectorAll('.weight-input').forEach(input => {
        totalWeight += parseFloat(input.value) || 0;
    });
    document.getElementById('totalBoxes').textContent = totalBoxes;
    document.getElementById('totalWeight').textContent = totalWeight.toFixed(2);
    validateSrrQty();
}

// PO Search
const poInput = document.getElementById('po_no');
const poResults = document.getElementById('poSearchResults');
let poTimeout;

poInput.addEventListener('input', function() {
    const searchTerm = this.value.trim();
    clearTimeout(poTimeout);

    if (searchTerm.length < 2) {
        poResults.classList.add('hidden');
        return;
    }

    const supplierId = document.getElementById('supplier_id').value;

    poTimeout = setTimeout(async () => {
        try {
            const params = new URLSearchParams({ search: searchTerm });
            if (supplierId) params.append('supplier_id', supplierId);

            const response = await fetch(`{{ route('supplier_receiving_reports.searchPurchaseOrders') }}?${params}`);
            const pos = await response.json();

            if (pos.length === 0) {
                poResults.innerHTML = '<div class="p-3 text-gray-300 text-center text-sm">No POs found</div>';
                poResults.classList.remove('hidden');
                return;
            }

            let html = '<div class="divide-y divide-gray-700">';
            pos.forEach(po => {
                html += `<div class="p-3 hover:bg-gray-700 cursor-pointer text-sm" onclick="selectPO('${po.po_no}')">
                    <span class="font-semibold text-white">${po.po_no}</span>
                    <span class="text-gray-300 ml-2">${po.supplier || ''}</span>
                </div>`;
            });
            html += '</div>';
            poResults.innerHTML = html;
            poResults.classList.remove('hidden');
        } catch (e) {
            console.error('PO search error:', e);
        }
    }, 300);
});

const GET_PO_ITEMS_URL = '{{ route("supplier_receiving_reports.getPOItems") }}';
let poTotalQty = 0;

async function selectPO(poNo) {
    document.getElementById('po_no').value = poNo;
    document.getElementById('poSearchResults').classList.add('hidden');

    try {
        const res = await fetch(`${GET_PO_ITEMS_URL}?po_no=${encodeURIComponent(poNo)}`);
        const data = await res.json();

        const refField = document.getElementById('srr_reference_number');
        if (refField && data.reference_number) refField.value = data.reference_number;

        const poQty = data.po_qty || 0;
        const hasLcQty = data.lc_actual_qty !== null && data.lc_actual_qty !== undefined;
        poTotalQty = hasLcQty ? data.lc_actual_qty : poQty;

        if (poQty > 0 || hasLcQty) {
            document.getElementById('poQtyInfo').classList.remove('hidden');
            document.getElementById('poQtyValue').textContent = poQty;
            const lcContainer = document.getElementById('lcQtyContainer');
            if (hasLcQty) {
                document.getElementById('lcQtyValue').textContent = data.lc_actual_qty;
                lcContainer.classList.remove('hidden');
            } else {
                lcContainer.classList.add('hidden');
            }
        } else {
            document.getElementById('poQtyInfo').classList.add('hidden');
        }

        // Auto-fill supplier
        if (data.supplier_id || data.supplier_name) {
            const supHidden = document.getElementById('supplier_id');
            const supSearch = document.getElementById('supplier_search');
            if (supHidden && data.supplier_id) supHidden.value = data.supplier_id;
            if (supSearch && data.supplier_name) supSearch.value = data.supplier_name;
        }

        // Auto-fill first item row
        if (data.items && data.items.length > 0) {
            const firstRow = document.querySelector('#itemsBody .item-row');
            if (firstRow) {
                const fi = data.items[0];
                const codeInput    = firstRow.querySelector('[name$="[item_code]"]');
                const descInput    = firstRow.querySelector('.srr-desc-input');
                const brandInput   = firstRow.querySelector('[name$="[brand]"]');
                const remarksInput = firstRow.querySelector('[name$="[remarks]"]');
                if (codeInput)    codeInput.value    = fi.item_code   || '';
                if (descInput)    descInput.value    = fi.description || '';
                if (brandInput)   brandInput.value   = fi.brand       || '';
                if (remarksInput && fi.note) remarksInput.value = fi.note;
            }
        }

        // Auto-fill note from PO remarks
        if (data.note) {
            const noteTA = document.querySelector('[name="note"]');
            if (noteTA) noteTA.value = data.note;
        }

        validateSrrQty();
    } catch (e) {
        console.error('Failed to load PO items:', e);
    }
}

function getSrrTotalQty() {
    let total = 0;
    document.querySelectorAll('.boxes-input').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    return total;
}

function validateSrrQty() {
    if (poTotalQty <= 0) {
        document.getElementById('qtyExceededWarning').classList.add('hidden');
        return true;
    }
    const srrTotal = getSrrTotalQty();
    document.getElementById('srrQtyValue').textContent = srrTotal;
    const exceeded = srrTotal > poTotalQty;
    document.getElementById('qtyExceededWarning').classList.toggle('hidden', !exceeded);
    return !exceeded;
}

function reduceToPoQty() {
    const inputs = document.querySelectorAll('.boxes-input');
    const srrTotal = getSrrTotalQty();
    if (srrTotal <= 0 || poTotalQty <= 0) return;

    const ratio = poTotalQty / srrTotal;
    let remaining = poTotalQty;
    inputs.forEach((input, idx) => {
        const val = parseFloat(input.value) || 0;
        if (idx < inputs.length - 1) {
            const reduced = Math.floor(val * ratio * 100) / 100;
            input.value = reduced;
            remaining -= reduced;
        } else {
            input.value = Math.max(0, Math.round(remaining * 100) / 100);
        }
    });
    inputs.forEach(input => input.dispatchEvent(new Event('change')));
    validateSrrQty();
}

document.addEventListener('click', function(e) {
    if (!poInput.contains(e.target) && !poResults.contains(e.target)) {
        poResults.classList.add('hidden');
    }
});

// Item Description Autocomplete
const SRR_SEARCH_URL = '{{ route("non_trade_items.search") }}';
let srrDescTimeout;

function attachSrrDescAutocomplete(input) {
    const dropdown = input.nextElementSibling;

    function positionDropdown() {
        const rect = input.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = rect.bottom + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.width = rect.width + 'px';
        dropdown.style.zIndex = '9999';
        dropdown.style.maxHeight = '160px';
        dropdown.style.overflowY = 'auto';
    }

    function fetchSuggestions() {
        const q = input.value.trim();
        const supplierId = document.getElementById('supplier_id').value;
        clearTimeout(srrDescTimeout);
        if (!supplierId && q.length < 2) { dropdown.classList.add('hidden'); return; }
        srrDescTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q });
                if (supplierId) params.append('supplier_id', supplierId);
                const res = await fetch(`${SRR_SEARCH_URL}?${params}`);
                const items = await res.json();
                if (!items.length) { dropdown.classList.add('hidden'); return; }
                dropdown.innerHTML = items.map(name =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 srr-desc-option">${name}</div>`
                ).join('');
                positionDropdown();
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.srr-desc-option').forEach(opt => {
                    opt.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value = this.textContent;
                        dropdown.classList.add('hidden');
                    });
                });
            } catch (e) { dropdown.classList.add('hidden'); }
        }, 250);
    }

    input.addEventListener('input', fetchSuggestions);
    input.addEventListener('focus', fetchSuggestions);
    input.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('hidden'), 150));
    window.addEventListener('scroll', () => dropdown.classList.add('hidden'), true);
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.srr-desc-input').forEach(attachSrrDescAutocomplete);

    // Validate qty on any boxes-input change (also covers dynamically added rows)
    document.getElementById('srrForm').addEventListener('input', function(e) {
        if (e.target.classList.contains('boxes-input')) {
            validateSrrQty();
        }
    });
    document.getElementById('srrForm').addEventListener('change', function(e) {
        if (e.target.classList.contains('boxes-input')) {
            validateSrrQty();
        }
    });

    // Block submit if qty exceeded
    document.getElementById('srrForm').addEventListener('submit', function(e) {
        if (!validateSrrQty()) {
            e.preventDefault();
            alert('SRR total qty exceeds the PO ordered qty. Please reduce the quantities or remove the PO link.');
        }
    });

    // Also clear PO qty info if po_no is manually cleared
    document.getElementById('po_no').addEventListener('input', function() {
        if (!this.value.trim()) {
            poTotalQty = 0;
            document.getElementById('poQtyInfo').classList.add('hidden');
            document.getElementById('qtyExceededWarning').classList.add('hidden');
        }
    });

    // Supplier searchable dropdown
    const suppliersData = @json($suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->supplier_name]));
    const supplierSearch = document.getElementById('supplier_search');
    const supplierHidden = document.getElementById('supplier_id');
    const supplierDrop   = document.getElementById('supplierDropdown');

    if (supplierHidden.value) {
        const found = suppliersData.find(s => s.id == supplierHidden.value);
        if (found) supplierSearch.value = found.name;
    }

    supplierSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        if (!q) { supplierDrop.classList.add('hidden'); return; }
        const filtered = suppliersData.filter(s => s.name.toLowerCase().includes(q));
        if (!filtered.length) { supplierDrop.classList.add('hidden'); return; }
        supplierDrop.innerHTML = filtered.slice(0, 15).map(s =>
            `<div class="p-2 hover:bg-gray-700 cursor-pointer text-sm text-white" data-id="${s.id}" data-name="${escapeHtml(s.name)}">${escapeHtml(s.name)}</div>`
        ).join('');
        supplierDrop.classList.remove('hidden');
        supplierDrop.querySelectorAll('[data-id]').forEach(opt => {
            opt.addEventListener('mousedown', function(e) {
                e.preventDefault();
                supplierSearch.value = this.dataset.name;
                supplierHidden.value = this.dataset.id;
                supplierDrop.classList.add('hidden');
            });
        });
    });
    supplierSearch.addEventListener('blur', () => setTimeout(() => supplierDrop.classList.add('hidden'), 150));
    document.addEventListener('click', function(e) {
        if (!supplierSearch.contains(e.target) && !supplierDrop.contains(e.target)) {
            supplierDrop.classList.add('hidden');
        }
    });
});
</script>
@endsection
