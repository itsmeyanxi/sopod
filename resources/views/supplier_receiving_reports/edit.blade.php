@extends('layouts.app')

@section('title', 'Edit Supply Receiving Report')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT SUPPLY RECEIVING REPORT</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">SRR CODE:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $report->srr_code }}</span>
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

        <form action="{{ route('supplier_receiving_reports.update', $report->id) }}" method="POST" id="srrForm">
            @csrf
            @method('PUT')

            <!-- Top Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DATE: <span class="text-red-400">*</span></label>
                        <input type="date" name="report_date" value="{{ old('report_date', $report->report_date->format('Y-m-d')) }}" required
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLY:</label>
                        <select name="supplier_id" id="supplier_id"
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Supply --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $report->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CV NO:</label>
                        <input type="text" name="cv_no" value="{{ old('cv_no', $report->cv_no) }}" placeholder="Check Voucher Number"
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">STORAGE:</label>
                        <select name="storage" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Warehouse / Storage --</option>
                            @foreach([
                                'Crystal Cold Chain Corp.',
                                'Glacier South Refrigeration Services Corp.',
                                'Icy Point Storage and Processing Corp.',
                                'One Stop Warehousing Solutions, Inc.',
                                'Benson Industrial Cold Storage, Inc.',
                                'Apex Cold Storage Inc.',
                                'Titan Transnational Corporation',
                            ] as $warehouse)
                                <option value="{{ $warehouse }}" {{ old('storage', $report->storage) == $warehouse ? 'selected' : '' }}>{{ $warehouse }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PO NO:</label>
                        <div class="relative">
                            <input type="text" name="po_no" id="po_no" value="{{ old('po_no', $report->po_no) }}" placeholder="Search or type PO number..."
                                class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" autocomplete="off">
                            <div id="poSearchResults" class="hidden absolute z-10 w-full mt-1 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto shadow-lg">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">TYPE: <span class="text-red-400">*</span></label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="purchased" {{ old('report_type', $report->report_type) == 'purchased' ? 'checked' : '' }}
                                    class="text-purple-500 focus:ring-purple-500">
                                <span class="text-gray-300">Purchased</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="stock_transfer" {{ old('report_type', $report->report_type) == 'stock_transfer' ? 'checked' : '' }}
                                    class="text-purple-500 focus:ring-purple-500">
                                <span class="text-gray-300">Stock Transfer</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="backload" {{ old('report_type', $report->report_type) == 'backload' ? 'checked' : '' }}
                                    class="text-purple-500 focus:ring-purple-500">
                                <span class="text-gray-300">Backload</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="report_type" value="returned" {{ old('report_type', $report->report_type) == 'returned' ? 'checked' : '' }}
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
                                <th class="border border-gray-700 px-2 py-2">ITEM DESCRIPTION <span class="text-red-400">*</span></th>
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
                            @foreach($report->items as $index => $item)
                            <tr class="item-row">
                                <td class="border border-gray-700 px-2 py-2 text-center row-number">{{ $index + 1 }}</td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[{{ $index }}][item_code]" value="{{ old("items.{$index}.item_code", $item->item_code) }}" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <div class="relative">
                                        <input type="text" name="items[{{ $index }}][item_description]" value="{{ old("items.{$index}.item_description", $item->item_description) }}" required class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded srr-desc-input" autocomplete="off">
                                        <div class="srr-desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                    </div>
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[{{ $index }}][brand]" value="{{ old("items.{$index}.brand", $item->brand) }}" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="number" name="items[{{ $index }}][no_of_boxes]" value="{{ old("items.{$index}.no_of_boxes", $item->no_of_boxes) }}" min="0" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded boxes-input" onchange="calculateTotals()">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="number" name="items[{{ $index }}][net_weight]" value="{{ old("items.{$index}.net_weight", $item->net_weight) }}" min="0" step="0.01" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded weight-input" onchange="calculateTotals()">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="date" name="items[{{ $index }}][pd]" value="{{ old("items.{$index}.pd", $item->pd ? $item->pd->format('Y-m-d') : '') }}" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="date" name="items[{{ $index }}][expiry_date]" value="{{ old("items.{$index}.expiry_date", $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '') }}" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[{{ $index }}][pallet_no]" value="{{ old("items.{$index}.pallet_no", $item->pallet_no) }}" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-1 py-1">
                                    <input type="text" name="items[{{ $index }}][remarks]" value="{{ old("items.{$index}.remarks", $item->remarks) }}" class="w-full bg-gray-900 border-0 px-2 py-1 text-white text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 rounded">
                                </td>
                                <td class="border border-gray-700 px-2 py-2 text-center">
                                    <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
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
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('note', $report->note) }}</textarea>
            </div>

            <!-- Signature Fields -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">PREPARED BY:</label>
                    <input type="text" value="{{ $report->prepared_by }}" readonly
                        class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-gray-300 cursor-not-allowed">
                    <input type="hidden" name="prepared_by" value="{{ $report->prepared_by }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CHECKED BY:</label>
                    <input type="text" name="checked_by" value="{{ old('checked_by', $report->checked_by) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">RECEIVED BY:</label>
                    <input type="text" name="received_by" value="{{ old('received_by', $report->received_by) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">VERIFIED BY:</label>
                    <input type="text" name="verified_by" value="{{ old('verified_by', $report->verified_by) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end gap-3 border-t border-gray-700 pt-4">
                <a href="{{ route('supplier_receiving_reports.show', $report->id) }}" class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-500 transition">
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
            <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
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
}

// Calculate on load
calculateTotals();

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
                poResults.innerHTML = '<div class="p-3 text-gray-400 text-center text-sm">No POs found</div>';
                poResults.classList.remove('hidden');
                return;
            }

            let html = '<div class="divide-y divide-gray-700">';
            pos.forEach(po => {
                html += `<div class="p-3 hover:bg-gray-700 cursor-pointer text-sm" onclick="selectPO('${po.po_no}')">
                    <span class="font-semibold text-white">${po.po_no}</span>
                    <span class="text-gray-400 ml-2">${po.supplier || ''}</span>
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

function selectPO(poNo) {
    document.getElementById('po_no').value = poNo;
    document.getElementById('poSearchResults').classList.add('hidden');
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
});
</script>
@endsection
