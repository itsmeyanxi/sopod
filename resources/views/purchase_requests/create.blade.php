@extends('layouts.app')

@section('title', 'Create Purchase Request')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">PURCHASE REQUISITION FORM</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">PR NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $prNo }}</span>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <p class="font-bold mb-2">Form Validation Errors:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <p class="font-bold mb-2">Error:</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <form action="{{ route('purchase_requests.store') }}" method="POST" id="prForm">
            @csrf

            <!-- Company (Hidden - MeatPlus Only) -->
            <input type="hidden" name="company" value="MeatPlus">

            @if(!empty($bom))
            <input type="hidden" name="bom_id" value="{{ $bom->id }}">
            <!-- BOM Info Banner -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-700 rounded-full w-10 h-10 flex items-center justify-center">
                            <span class="text-lg">🐔</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-blue-800 text-sm">BOM-Linked Purchase Request</h3>
                            <p class="text-xs text-blue-600 mt-0.5">
                                Cycle: <strong>{{ $bom->cycle_ref }}</strong>
                                @if($bom->grower) · Grower: <strong>{{ $bom->grower }}</strong> @endif
                                · Date: {{ $bom->cycle_date->format('M d, Y') }}
                                · Houses: {{ $bom->num_houses }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-blue-500 font-semibold uppercase">BOM Total Cost</div>
                        <div class="text-lg font-bold text-blue-800">PHP {{ number_format($bom->total_cost, 2) }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">REQUISITIONER: <span class="text-red-700">*</span></label>
                        <input type="text" name="requisitioner" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requisitioner') }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DEPARTMENT:</label>
                        <select name="department" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Department --</option>
                            @foreach(['Accounting', 'Admin', 'Commissary', 'Engineering', 'Finance', 'HR', 'IT', 'Logistics', 'Marketing', 'Operations', 'Procurement', 'Production', 'QA/QC', 'Sales', 'Warehouse'] as $dept)
                                <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">TERMS:</label>
                        <input type="text" name="terms" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('terms') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">ADDRESS:</label>
                        <textarea name="address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                        <textarea name="delivery_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('delivery_address') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONTACT PERSON:</label>
                        <input type="text" name="contact_person" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_person') }}">
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DATE OF REQUEST: <span class="text-red-700">*</span></label>
                        <input type="date" name="date_of_request" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_of_request', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DATE NEEDED:</label>
                        <input type="date" name="date_needed" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_needed') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">TYPE OF REQUEST:</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center text-gray-300">
                                <input type="radio" name="type_of_request" value="urgent" class="form-radio text-purple-500" {{ old('type_of_request') == 'urgent' ? 'checked' : '' }}>
                                <span class="ml-2">Urgent</span>
                            </label>
                            <label class="inline-flex items-center text-gray-300">
                                <input type="radio" name="type_of_request" value="regular" class="form-radio text-purple-500" {{ old('type_of_request') == 'regular' ? 'checked' : '' }}>
                                <span class="ml-2">Regular</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">WITH BUDGET:</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center text-gray-300">
                                <input type="radio" name="with_budget" value="yes" class="form-radio text-purple-500" {{ old('with_budget') == 'yes' ? 'checked' : '' }}>
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center text-gray-300">
                                <input type="radio" name="with_budget" value="no" class="form-radio text-purple-500" {{ old('with_budget') == 'no' ? 'checked' : '' }}>
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CHARGE TO:</label>
                        <input type="text" name="charge_to" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('charge_to') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONTACT NUMBER:</label>
                        <input type="text" name="contact_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_number') }}">
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-white">Items</h3>
                    <button type="button" onclick="addRow()" class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="border-collapse border border-gray-700" id="itemsTable" style="min-width:1400px; width:100%;">
                        <thead class="bg-red-700 text-white uppercase text-xs">
                            <tr>
                                <th class="border px-2 py-2" style="width:70px">ACTION</th>
                                <th class="border px-2 py-2" style="width:40px">NO.</th>
                                <th class="border px-2 py-2" style="width:130px">ITEM CODE</th>
                                <th class="border px-2 py-2" style="width:130px">DATE NEEDED</th>
                                <th class="border px-2 py-2" style="width:120px">QTY</th>
                                <th class="border px-2 py-2" style="width:80px">UOM</th>
                                <th class="border px-2 py-2" style="width:300px">DESCRIPTION</th>
                                <th class="border px-2 py-2" style="width:110px">UNIT PRICE</th>
                                <th class="border px-2 py-2" style="width:100px">AMOUNT</th>
                                <th class="border px-2 py-2" style="width:160px">REMARKS/SPECIFICATIONS</th>
                                <th class="border px-2 py-2" style="width:140px">NOTE</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody" class="bg-gray-800 text-gray-300 divide-y divide-gray-700">
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-2 py-2 text-center">
                                    <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </td>
                                <td class="border border-gray-700 px-2 py-2 text-center">1</td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" autocomplete="off"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="date" name="items[0][date_needed]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" ></td>
                                <td class="border border-gray-700 px-2 py-2">
                                    <div class="relative">
                                        <input type="text" name="items[0][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off">
                                        <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                    </div>
                                </td>
                                <input type="hidden" name="items[0][supplier_id]" class="supplier-id-input">
                                <input type="hidden" name="items[0][supplier_name]" class="supplier-name-input">
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][amount]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-amount" readonly></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][remarks]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reason for Requisition -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">REASON FOR REQUISITION:</label>
                <textarea name="reason_for_requisition" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter reason for this requisition...">{{ old('reason_for_requisition', !empty($bom) ? 'BOM Purchase Request for cycle ' . $bom->cycle_ref . ($bom->grower ? ' — Grower: ' . $bom->grower : '') : '') }}</textarea>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-700 rounded">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Prepared By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm" colspan="2">Noted By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm" colspan="3">Approved By:</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-700 px-4 py-4 text-center text-gray-300 text-sm">{{ auth()->user()->name }}</td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-700 text-gray-300 text-xs italic">
                                <td class="border border-gray-700 px-4 py-2 text-center">Requisitioner</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">Department Head</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">General Manager</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">CFO</td>
                                <td class="border border-gray-700 px-4 py-2 text-center" colspan="2">Vice-President/President</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('purchase_requests.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-save mr-1"></i> Submit Purchase Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let rowCount = 1;

// ── Route URLs ──────────────────────────────────────────────────────────────
const SUPPLIER_SEARCH_URL    = '{{ route("purchase_requests.search_suppliers") }}';
const SEARCH_URL             = '{{ route("items.search") }}';
const ITEM_CODE_SEARCH_URL   = '{{ route("purchase_orders.search_by_item_code") }}';
const GENERATE_ITEM_CODE_URL = '{{ route("purchase_orders.generate_item_code") }}';

// ======================== ROW MANAGEMENT ========================
function addRow() {
    const tbody  = document.getElementById('itemsBody');
    const newRow = tbody.insertRow();
    newRow.className = 'hover:bg-gray-700/40';
    newRow.innerHTML = `
        <td class="border border-gray-700 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition">
                <i class="fas fa-trash mr-1"></i>Delete
            </button>
        </td>
        <td class="border border-gray-700 px-2 py-2 text-center">${rowCount + 1}</td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" autocomplete="off">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="date" name="items[${rowCount}][date_needed]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="number" step="0.01" name="items[${rowCount}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required>
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" >
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <div class="relative">
                <input type="text" name="items[${rowCount}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off">
                <div class="desc-dropdown hidden bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto"></div>
            </div>
        </td>
        <input type="hidden" name="items[${rowCount}][supplier_id]" class="supplier-id-input">
        <input type="hidden" name="items[${rowCount}][supplier_name]" class="supplier-name-input">
        <td class="border border-gray-700 px-2 py-2">
            <input type="number" step="0.01" name="items[${rowCount}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="number" step="0.01" name="items[${rowCount}][amount]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-amount" readonly>
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][remarks]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
        </td>
    `;
    rowCount++;
    attachCalculationListeners();
    attachItemCodeListeners();
    const newDesc = newRow.querySelector('.desc-input');
    if (newDesc) attachDescAutocomplete(newDesc);
}

function removeRow(btn) {
    btn.closest('tr').remove();
    reorderRows();
}

function reorderRows() {
    document.querySelectorAll('#itemsBody tr').forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells[1]) cells[1].textContent = index + 1;
        row.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name');
            if (name) input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
        });
    });
    rowCount = document.querySelectorAll('#itemsBody tr').length;
}

// ======================== AMOUNT CALCULATION ========================
function attachCalculationListeners() {
    document.querySelectorAll('.item-qty, .item-price').forEach(input => {
        input.removeEventListener('input', calculateAmount);
        input.addEventListener('input', calculateAmount);
    });
}

function calculateAmount(e) {
    const row   = e.target.closest('tr');
    const qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-amount').value = (qty * price).toFixed(2);
}

// ======================== DROPDOWN POSITIONING ========================
function positionFixedDropdown(inputEl, dropdownEl) {
    const rect = inputEl.getBoundingClientRect();
    dropdownEl.style.position  = 'fixed';
    dropdownEl.style.top       = rect.bottom + 'px';
    dropdownEl.style.left      = rect.left + 'px';
    dropdownEl.style.width     = rect.width + 'px';
    dropdownEl.style.zIndex    = '9999';
    dropdownEl.style.maxHeight = '200px';
    dropdownEl.style.overflowY = 'auto';
}

let scrollTicking = false;
window.addEventListener('scroll', function () {
    if (!scrollTicking) {
        requestAnimationFrame(() => {
            document.querySelectorAll('.desc-dropdown:not(.hidden), .supplier-dropdown:not(.hidden)').forEach(dd => {
                const input = dd.closest('.relative')?.querySelector('input:first-child') || dd.previousElementSibling;
                if (input) positionFixedDropdown(input, dd);
            });
            scrollTicking = false;
        });
        scrollTicking = true;
    }
}, true);

// ======================== SUPPLIER AUTOCOMPLETE ========================
// KEY CHANGE: reads the description from the same row and passes it to the
// backend so only suppliers who have sold that item are returned.
let supplierTimeout;

function attachSupplierAutocomplete(input) {
    const container = input.closest('.relative');
    const dropdown  = container.querySelector('.supplier-dropdown');
    const idInput   = container.querySelector('.supplier-id-input');
    const nameInput = container.querySelector('.supplier-name-input');

    function getRowDescription() {
        const row = input.closest('tr');
        return row ? (row.querySelector('.desc-input')?.value.trim() || '') : '';
    }

    async function fetchSuppliers() {
        const q    = input.value.trim();
        const desc = getRowDescription();
        clearTimeout(supplierTimeout);
        if (q.length < 1 && !desc) { dropdown.classList.add('hidden'); return; }

        supplierTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q });
                if (desc) params.append('description', desc);
                // Always send current supplier ID so backend can guarantee it appears
                if (idInput.value) params.append('current_supplier_id', idInput.value);

                const res       = await fetch(`${SUPPLIER_SEARCH_URL}?${params}`);
                const suppliers = await res.json();

                if (!suppliers.length) { dropdown.classList.add('hidden'); return; }

                const isFallback = suppliers.some(s => s.is_fallback);

                // Header
                let headerHtml = '';
                if (desc && !isFallback) {
                    headerHtml = `<div class="px-3 py-1.5 text-xs text-green-700 bg-gray-900 border-b border-gray-700 font-semibold">
                        ✓ Showing suppliers confirmed to carry this item
                    </div>`;
                } else if (desc && isFallback) {
                    headerHtml = `<div class="px-3 py-1.5 text-xs text-yellow-700 bg-gray-900 border-b border-gray-700 font-semibold">
                        ⚠ This item has no confirmed suppliers in the library — showing all active suppliers
                    </div>`;
                }

                // Supplier rows
                const rowsHtml = suppliers.map(s => {
                    let badge = '';
                    if (s.is_current && s.carries_item === false) {
                        badge = `<span class="ml-1 px-1.5 py-0.5 text-xs bg-blue-700 text-blue-100 rounded">← Current (unconfirmed)</span>`;
                    } else if (s.carries_item === true) {
                        badge = `<span class="ml-1 px-1.5 py-0.5 text-xs bg-green-700 text-green-100 rounded">✓ Carries item</span>`;
                    } else if (desc) {
                        badge = `<span class="ml-1 px-1.5 py-0.5 text-xs bg-gray-700 text-gray-300 rounded">? Unconfirmed</span>`;
                    }
                    return `
                    <div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 supplier-option"
                         data-id="${s.id}" data-name="${s.supplier_name}"
                         data-code="${s.supplier_code}" data-address="${s.address || ''}">
                        <div class="flex items-center gap-1 font-semibold text-white flex-wrap">
                            ${s.supplier_name} ${badge}
                        </div>
                        <div class="text-xs text-gray-300">${s.supplier_code || ''}</div>
                    </div>`;
                }).join('');

                dropdown.innerHTML = headerHtml + rowsHtml;
                positionFixedDropdown(input, dropdown);
                dropdown.classList.remove('hidden');

                dropdown.querySelectorAll('.supplier-option').forEach(opt => {
                    opt.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value     = this.dataset.name;
                        idInput.value   = this.dataset.id;
                        nameInput.value = this.dataset.name;
                        dropdown.classList.add('hidden');
                    });
                });
            } catch (e) { dropdown.classList.add('hidden'); }
        }, 250);
    }

    input.addEventListener('input', () => { idInput.value = ''; nameInput.value = ''; fetchSuppliers(); });
    // Also re-fetch when user clicks into the supplier field (description may have changed)
    input.addEventListener('focus', fetchSuppliers);
    input.addEventListener('blur',  () => setTimeout(() => dropdown.classList.add('hidden'), 200));
}

// ======================== DESCRIPTION AUTOCOMPLETE ========================
let descTimeout;

function initDescAutocomplete() {
    document.querySelectorAll('.desc-input').forEach(attachDescAutocomplete);
}

function attachDescAutocomplete(input) {
    const dropdown = input.nextElementSibling;

    async function fetchSuggestions() {
        const q          = input.value.trim();
        const row        = input.closest('tr');
        const supplierId = row?.querySelector('.supplier-id-input')?.value || '';
        clearTimeout(descTimeout);
        if (!supplierId && q.length < 2) { dropdown.classList.add('hidden'); return; }
        descTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q });
                if (supplierId) params.append('supplier_id', supplierId);
                const res   = await fetch(`${SEARCH_URL}?${params}`);
                const items = await res.json();
                if (!items.length) { dropdown.classList.add('hidden'); return; }
                dropdown.innerHTML = items.map(item =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 desc-option"
                          data-name="${(item.item_description || '').replace(/"/g, '&quot;')}"
                          data-item-code="${(item.item_code || '').replace(/"/g, '&quot;')}">
                        <div class="font-semibold">${item.item_description || ''}</div>
                        <div class="text-xs text-gray-400">${item.item_code || ''} ${item.brand ? '· '+item.brand : ''} <span class="text-yellow-400">${item.type === 'non_trade' ? 'Non-Trade' : 'Trade'}</span></div>
                    </div>`
                ).join('');
                positionFixedDropdown(input, dropdown);
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.desc-option').forEach(opt => {
                    opt.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value = this.dataset.name || '';
                        dropdown.classList.add('hidden');
                        const row = input.closest('tr');
                        if (!row) return;
                        const itemCodeInput = row.querySelector('.item-code-input');
                        if (this.dataset.itemCode && itemCodeInput) {
                            itemCodeInput.value = this.dataset.itemCode;
                        } else if (itemCodeInput) {
                            itemCodeInput.value = '';
                            autoGenerateItemCode(row);
                        }
                        if (this.dataset.supplierId) {
                            const ss = row.querySelector('.supplier-search');
                            const si = row.querySelector('.supplier-id-input');
                            const sn = row.querySelector('.supplier-name-input');
                            if (ss) ss.value = this.dataset.supplierName || '';
                            if (si) si.value = this.dataset.supplierId;
                            if (sn) sn.value = this.dataset.supplierName || '';
                        }
                    });
                });
            } catch (e) { dropdown.classList.add('hidden'); }
        }, 250);
    }

    input.addEventListener('input', function() {
        const row = input.closest('tr');
        if (row && !input.value.trim()) {
            const ic = row.querySelector('.item-code-input');
            const ss = row.querySelector('.supplier-search');
            const si = row.querySelector('.supplier-id-input');
            const sn = row.querySelector('.supplier-name-input');
            if (ic) ic.value = '';
            if (ss) ss.value = '';
            if (si) si.value = '';
            if (sn) sn.value = '';
        }
        fetchSuggestions();
    });
    input.addEventListener('focus', fetchSuggestions);
    input.addEventListener('blur', () => {
        setTimeout(() => dropdown.classList.add('hidden'), 200);
        const row           = input.closest('tr');
        const itemCodeInput = row?.querySelector('.item-code-input');
        if (row && itemCodeInput && !itemCodeInput.value.trim()) {
            autoGenerateItemCode(row);
        }
    });
}

// ======================== ITEM CODE AUTO-FILL ========================
let itemCodeTimeout;

function attachItemCodeListeners() {
    document.querySelectorAll('.item-code-input').forEach(input => {
        if (input.dataset.itemCodeBound) return;
        input.dataset.itemCodeBound = '1';

        input.addEventListener('input', function () {
            clearTimeout(itemCodeTimeout);
            const code = this.value.trim();
            if (!code) return;
            const row = this.closest('tr');
            itemCodeTimeout = setTimeout(() => fetchAndFillByItemCode(code, row), 400);
        });

        input.addEventListener('blur', function () {
            clearTimeout(itemCodeTimeout);
            const code = this.value.trim();
            if (!code) return;
            fetchAndFillByItemCode(code, this.closest('tr'));
        });
    });
}

async function fetchAndFillByItemCode(itemCode, row) {
    if (!row) return;
    try {
        const res  = await fetch(`${ITEM_CODE_SEARCH_URL}?item_code=${encodeURIComponent(itemCode)}`);
        const data = await res.json();
        if (!data || !data.description) return;

        const descInput = row.querySelector('.desc-input');
        if (descInput) descInput.value = data.description;

        if (data.supplier_id) {
            const supplierSearch = row.querySelector('.supplier-search');
            const supplierId     = row.querySelector('.supplier-id-input');
            const supplierName   = row.querySelector('.supplier-name-input');
            if (supplierSearch) supplierSearch.value = data.supplier_name || '';
            if (supplierId)     supplierId.value     = data.supplier_id;
            if (supplierName)   supplierName.value   = data.supplier_name || '';
        }
        if (data.uom) {
            const uomInput = row.querySelector('input[name*="[uom]"]');
            if (uomInput) uomInput.value = data.uom;
        }
        if (data.unit_price) {
            const priceInput = row.querySelector('.item-price');
            if (priceInput) {
                priceInput.value = data.unit_price;
                priceInput.dispatchEvent(new Event('input'));
            }
        }
    } catch (e) {
        console.error('Error fetching item by code:', e);
    }
}

// ======================== ITEM CODE AUTO-GENERATE ========================
async function autoGenerateItemCode(row) {
    const descInput     = row.querySelector('.desc-input');
    const itemCodeInput = row.querySelector('.item-code-input');
    if (!descInput || !itemCodeInput) return;

    const description = descInput.value.trim();
    const supplierId  = row.querySelector('.supplier-id-input')?.value || '';
    if (!description) return;

    try {
        const params = new URLSearchParams({ description });
        if (supplierId) params.append('supplier_id', supplierId);
        const res  = await fetch(`${GENERATE_ITEM_CODE_URL}?${params}`);
        const data = await res.json();
        if (data.item_code) {
            itemCodeInput.value = data.item_code;
        }
    } catch (e) {
        console.error('Error generating item code:', e);
    }
}

// ======================== BOM PRE-FILL ========================
function prefillBomItems(bomItems) {
    if (!bomItems || !bomItems.length) return;

    const tbody = document.getElementById('itemsBody');
    // Clear the default empty row
    tbody.innerHTML = '';
    rowCount = 0;

    bomItems.forEach((item, idx) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-700/40';
        tr.innerHTML = `
            <td class="border border-gray-700 px-2 py-2 text-center">
                <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </td>
            <td class="border border-gray-700 px-2 py-2 text-center">${idx + 1}</td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="text" name="items[${idx}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" autocomplete="off">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="date" name="items[${idx}][date_needed]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="number" step="0.01" name="items[${idx}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required value="${item.qty || ''}">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="text" name="items[${idx}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="${item.uom || ''}">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <div class="relative">
                    <input type="text" name="items[${idx}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off" value="${(item.description || '').replace(/"/g, '&quot;')}">
                    <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                </div>
            </td>
            <input type="hidden" name="items[${idx}][supplier_id]" class="supplier-id-input">
            <input type="hidden" name="items[${idx}][supplier_name]" class="supplier-name-input">
            <td class="border border-gray-700 px-2 py-2">
                <input type="number" step="0.01" name="items[${idx}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price" value="${item.unit_price || ''}">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="number" step="0.01" name="items[${idx}][amount]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-amount" readonly value="${item.amount || ''}">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="text" name="items[${idx}][remarks]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="${(item.remarks || '').replace(/"/g, '&quot;')}">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="text" name="items[${idx}][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="${(item.note || '').replace(/"/g, '&quot;')}">
            </td>
        `;
        tbody.appendChild(tr);
        rowCount++;
    });

    attachCalculationListeners();
    attachItemCodeListeners();
    initDescAutocomplete();
}

// ======================== INIT ========================
document.addEventListener('DOMContentLoaded', function () {
    @if(!empty($bom) && !empty($bomItems))
    const bomItems = @json($bomItems);
    prefillBomItems(bomItems);
    @else
    attachCalculationListeners();
    attachItemCodeListeners();
    initDescAutocomplete();
    @endif
});
</script>
@endsection