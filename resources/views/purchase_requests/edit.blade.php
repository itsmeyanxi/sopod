@extends('layouts.app')

@section('title', isset($notesOnly) && $notesOnly ? 'Edit Notes - Purchase Request' : 'Edit Purchase Request')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                @if(isset($notesOnly) && $notesOnly)
                    EDIT NOTES - PURCHASE REQUISITION
                @else
                    EDIT PURCHASE REQUISITION FORM
                @endif
            </h1>
            <div class="text-right">
                <label class="font-semibold text-gray-500">PR NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-50 border border-gray-200 text-gray-800 rounded">{{ $purchaseRequest->pr_no }}</span>
            </div>
        </div>

        @if(isset($notesOnly) && $notesOnly)
            <div class="bg-blue-600/20 border border-blue-500 text-blue-300 px-4 py-3 rounded mb-4">
                <i class="fas fa-info-circle mr-2"></i> This PR is approved. Only notes can be edited.
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($notesOnly) && $notesOnly ? route('purchase_requests.update_notes', $purchaseRequest->id) : route('purchase_requests.update', $purchaseRequest->id) }}" method="POST" id="prForm">
            @csrf
            @method('PUT')

            @if(!isset($notesOnly) || !$notesOnly)
            <!-- Company Selection -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">COMPANY: <span class="text-red-400">*</span></label>
                <select name="company" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <option value="">-- Select Company --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company }}" {{ old('company', $purchaseRequest->company) == $company ? 'selected' : '' }}>
                            {{ $company }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">REQUISITIONER: <span class="text-red-400">*</span></label>
                        <input type="text" name="requisitioner" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requisitioner', $purchaseRequest->requisitioner) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">DEPARTMENT:</label>
                        <select name="department" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Department --</option>
                            @foreach(['Accounting', 'Admin', 'Commissary', 'Engineering', 'Finance', 'HR', 'IT', 'Logistics', 'Marketing', 'Operations', 'Procurement', 'Production', 'QA/QC', 'Sales', 'Warehouse'] as $dept)
                                <option value="{{ $dept }}" {{ old('department', $purchaseRequest->department) == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">TERMS:</label>
                        <input type="text" name="terms" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('terms', $purchaseRequest->terms) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">ADDRESS:</label>
                        <textarea name="address" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('address', $purchaseRequest->address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">DELIVERY ADDRESS:</label>
                        <textarea name="delivery_address" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('delivery_address', $purchaseRequest->delivery_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">CONTACT PERSON:</label>
                        <input type="text" name="contact_person" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_person', $purchaseRequest->contact_person) }}">
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">DATE OF REQUEST: <span class="text-red-400">*</span></label>
                        <input type="date" name="date_of_request" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_of_request', $purchaseRequest->date_of_request->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">DATE NEEDED:</label>
                        <input type="date" name="date_needed" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_needed', $purchaseRequest->date_needed ? $purchaseRequest->date_needed->format('Y-m-d') : '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">TYPE OF REQUEST:</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center text-gray-500">
                                <input type="radio" name="type_of_request" value="urgent" class="form-radio text-purple-500" {{ old('type_of_request', $purchaseRequest->type_of_request) == 'urgent' ? 'checked' : '' }}>
                                <span class="ml-2">Urgent</span>
                            </label>
                            <label class="inline-flex items-center text-gray-500">
                                <input type="radio" name="type_of_request" value="regular" class="form-radio text-purple-500" {{ old('type_of_request', $purchaseRequest->type_of_request) == 'regular' ? 'checked' : '' }}>
                                <span class="ml-2">Regular</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">WITH BUDGET:</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center text-gray-500">
                                <input type="radio" name="with_budget" value="yes" class="form-radio text-purple-500" {{ old('with_budget', $purchaseRequest->with_budget) == 'yes' ? 'checked' : '' }}>
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center text-gray-500">
                                <input type="radio" name="with_budget" value="no" class="form-radio text-purple-500" {{ old('with_budget', $purchaseRequest->with_budget) == 'no' ? 'checked' : '' }}>
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">CHARGE TO:</label>
                        <input type="text" name="charge_to" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('charge_to', $purchaseRequest->charge_to) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">CONTACT NUMBER:</label>
                        <input type="text" name="contact_number" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_number', $purchaseRequest->contact_number) }}">
                    </div>
                </div>
            </div>

            @endif

            <!-- Items Table -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-gray-800">Items</h3>
                    @if(!isset($notesOnly) || !$notesOnly)
                    <button type="button" onclick="addRow()" class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-gray-800 px-4 py-2 rounded transition">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="border-collapse border border-gray-200" id="itemsTable" style="min-width:1400px; width:100%;">
                        <thead class="bg-red-700 text-white uppercase text-xs">
                            <tr>
                                @if(!isset($notesOnly) || !$notesOnly)
                                <th class="border border-gray-200 px-2 py-2" style="width:70px">ACTION</th>
                                @endif
                                <th class="border border-gray-200 px-2 py-2" style="width:40px">NO.</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:130px">ITEM CODE</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:130px">DATE NEEDED</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:120px">QTY</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:80px">UOM</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:300px">DESCRIPTION</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:110px">UNIT PRICE</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:100px">AMOUNT</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:160px">REMARKS/SPECIFICATIONS</th>
                                <th class="border border-gray-200 px-2 py-2" style="width:140px">NOTE</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody" class="bg-white text-gray-500 divide-y divide-gray-700">
                            @foreach($purchaseRequest->items as $index => $item)
                            <tr class="hover:bg-gray-100/40">
                                @if(!isset($notesOnly) || !$notesOnly)
                                <td class="border border-gray-200 px-2 py-2 text-center">
                                    <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition" title="Delete row">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </td>
                                @endif
                                <td class="border border-gray-200 px-2 py-2 text-center">{{ $index + 1 }}</td>
                                @if(isset($notesOnly) && $notesOnly)
                                <td class="border border-gray-200 px-2 py-2">{{ $item->item_code ?? 'N/A' }}</td>
                                <td class="border border-gray-200 px-2 py-2">{{ $item->date_needed ? \Carbon\Carbon::parse($item->date_needed)->format('M d, Y') : 'N/A' }}</td>
                                <td class="border border-gray-200 px-2 py-2">{{ number_format($item->qty, 2) }}</td>
                                <td class="border border-gray-200 px-2 py-2">{{ $item->uom }}</td>
                                <td class="border border-gray-200 px-2 py-2">{{ $item->description }}</td>
                                <td class="border border-gray-200 px-2 py-2 text-right">{{ $item->unit_price ? '₱' . number_format($item->unit_price, 2) : 'N/A' }}</td>
                                <td class="border border-gray-200 px-2 py-2 text-right">{{ $item->amount ? '₱' . number_format($item->amount, 2) : 'N/A' }}</td>
                                <td class="border border-gray-200 px-2 py-2">{{ $item->remarks ?? '' }}</td>
                                <td class="border border-gray-200 px-2 py-2">
                                    <input type="text" name="item_notes[{{ $item->id }}]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800" value="{{ $item->note }}">
                                </td>
                                @else
                                <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[{{ $index }}][item_code]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-code-input" autocomplete="off" value="{{ $item->item_code }}"></td>
                                <td class="border border-gray-200 px-2 py-2"><input type="date" name="items[{{ $index }}][date_needed]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800" value="{{ $item->date_needed }}"></td>
                                <td class="border border-gray-200 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][qty]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-qty" value="{{ $item->qty }}" required></td>
                                <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[{{ $index }}][uom]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800" value="{{ $item->uom }}" required></td>
                                <td class="border border-gray-200 px-2 py-2">
                                    <div class="relative">
                                        <input type="text" name="items[{{ $index }}][description]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 desc-input" value="{{ $item->description }}" required autocomplete="off">
                                        <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-white border border-gray-300 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                    </div>
                                </td>
                                <input type="hidden" name="items[{{ $index }}][supplier_id]" class="supplier-id-input" value="{{ $item->supplier_id }}">
                                <input type="hidden" name="items[{{ $index }}][supplier_name]" class="supplier-name-input" value="{{ $item->supplier_name }}">
                                <td class="border border-gray-200 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-price" value="{{ $item->unit_price }}"></td>
                                <td class="border border-gray-200 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][amount]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-amount" value="{{ $item->amount }}" readonly></td>
                                <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[{{ $index }}][remarks]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800" value="{{ $item->remarks }}"></td>
                                <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[{{ $index }}][note]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800" value="{{ $item->note }}"></td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!isset($notesOnly) || !$notesOnly)
            <!-- Reason for Requisition -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-800 mb-2">REASON FOR REQUISITION:</label>
                <textarea name="reason_for_requisition" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter reason for this requisition...">{{ old('reason_for_requisition', $purchaseRequest->reason_for_requisition) }}</textarea>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-200 rounded">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm">Prepared By:</th>
                                <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm" colspan="2">Noted By:</th>
                                <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm" colspan="3">Approved By:</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-200 px-4 py-4 text-center text-gray-500 text-sm">{{ $purchaseRequest->creator->name ?? auth()->user()->name }}</td>
                                <td class="border border-gray-200 px-4 py-16 text-center"></td>
                                <td class="border border-gray-200 px-4 py-16 text-center"></td>
                                <td class="border border-gray-200 px-4 py-16 text-center"></td>
                                <td class="border border-gray-200 px-4 py-16 text-center"></td>
                                <td class="border border-gray-200 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-100 text-gray-500 text-xs italic">
                                <td class="border border-gray-200 px-4 py-2 text-center">Requisitioner</td>
                                <td class="border border-gray-200 px-4 py-2 text-center">Department Head</td>
                                <td class="border border-gray-200 px-4 py-2 text-center">General Manager</td>
                                <td class="border border-gray-200 px-4 py-2 text-center">CFO</td>
                                <td class="border border-gray-200 px-4 py-2 text-center" colspan="2">Vice-President/President</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('purchase_requests.show', $purchaseRequest->id) }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-100 transition">
                    Cancel
                </a>
                @if(isset($notesOnly) && $notesOnly)
                <button type="submit" class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-yellow-700 transition">
                    <i class="fas fa-save mr-1"></i> Update Notes
                </button>
                @else
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-save mr-1"></i> Update Purchase Request
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
let rowCount = {{ $purchaseRequest->items->count() }};
const SUPPLIER_SEARCH_URL = '{{ route("purchase_requests.search_suppliers") }}';
const SEARCH_URL = '{{ route("non_trade_items.search") }}';
const ITEM_CODE_SEARCH_URL = '{{ route("purchase_orders.search_by_item_code") }}';
const GENERATE_ITEM_CODE_URL = '{{ route("purchase_orders.generate_item_code") }}';

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const newRow = tbody.insertRow();
    newRow.className = 'hover:bg-gray-100/40';
    newRow.innerHTML = `
        <td class="border border-gray-200 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition" title="Delete row">
                <i class="fas fa-trash mr-1"></i>Delete
            </button>
        </td>
        <td class="border border-gray-200 px-2 py-2 text-center">${rowCount + 1}</td>
        <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[${rowCount}][item_code]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-code-input" autocomplete="off"></td>
        <td class="border border-gray-200 px-2 py-2"><input type="date" name="items[${rowCount}][date_needed]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800"></td>
        <td class="border border-gray-200 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][qty]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-qty" required></td>
        <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[${rowCount}][uom]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800" required></td>
        <td class="border border-gray-200 px-2 py-2"><div class="relative"><input type="text" name="items[${rowCount}][description]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 desc-input" required autocomplete="off"><div class="desc-dropdown hidden bg-white border border-gray-300 rounded shadow-lg max-h-40 overflow-y-auto"></div></div></td>
        <input type="hidden" name="items[${rowCount}][supplier_id]" class="supplier-id-input">
        <input type="hidden" name="items[${rowCount}][supplier_name]" class="supplier-name-input">
        <td class="border border-gray-200 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][unit_price]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-price"></td>
        <td class="border border-gray-200 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][amount]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 item-amount" readonly></td>
        <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[${rowCount}][remarks]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800"></td>
        <td class="border border-gray-200 px-2 py-2"><input type="text" name="items[${rowCount}][note]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800"></td>
    `;
    rowCount++;
    attachCalculationListeners();
    attachItemCodeListeners();
    const newDesc = newRow.querySelector('.desc-input');
    if (newDesc) attachDescAutocomplete(newDesc);
}

function removeRow(btn) { btn.closest('tr').remove(); reorderRows(); }

function reorderRows() {
    const rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells[1]) cells[1].textContent = index + 1;
        row.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name');
            if (name) input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
        });
    });
    rowCount = rows.length;
}

function attachCalculationListeners() {
    document.querySelectorAll('.item-qty, .item-price').forEach(input => {
        input.removeEventListener('input', calculateAmount);
        input.addEventListener('input', calculateAmount);
    });
}

function calculateAmount(e) {
    const row = e.target.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-amount').value = (qty * price).toFixed(2);
}

// ======================== FIXED-POSITION DROPDOWN HELPER ========================
function positionFixedDropdown(inputEl, dropdownEl) {
    const rect = inputEl.getBoundingClientRect();
    dropdownEl.style.position = 'fixed';
    dropdownEl.style.top = rect.bottom + 'px';
    dropdownEl.style.left = rect.left + 'px';
    dropdownEl.style.width = rect.width + 'px';
    dropdownEl.style.zIndex = '9999';
    dropdownEl.style.maxHeight = '200px';
    dropdownEl.style.overflowY = 'auto';
}

let scrollTicking = false;
window.addEventListener('scroll', function() {
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
let supplierTimeout;
function attachSupplierAutocomplete(input) {
    const container = input.closest('.relative');
    const dropdown = container.querySelector('.supplier-dropdown');
    const idInput = container.querySelector('.supplier-id-input');
    const nameInput = container.querySelector('.supplier-name-input');

    function fetchSuppliers() {
        const q = input.value.trim();
        clearTimeout(supplierTimeout);
        if (q.length < 1) { dropdown.classList.add('hidden'); return; }
        supplierTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`${SUPPLIER_SEARCH_URL}?q=${encodeURIComponent(q)}`);
                const suppliers = await res.json();
                if (!suppliers.length) { dropdown.classList.add('hidden'); return; }
                dropdown.innerHTML = suppliers.map(s =>
                    `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-200 supplier-option" data-id="${s.id}" data-name="${s.supplier_name}" data-code="${s.supplier_code}" data-address="${s.address || ''}">${s.supplier_name} (${s.supplier_code})</div>`
                ).join('');
                positionFixedDropdown(input, dropdown);
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.supplier-option').forEach(opt => {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        input.value = this.dataset.name;
                        idInput.value = this.dataset.id;
                        nameInput.value = this.dataset.name;
                        dropdown.classList.add('hidden');
                    });
                });
            } catch (e) { dropdown.classList.add('hidden'); }
        }, 250);
    }
    input.addEventListener('input', function() { idInput.value = ''; nameInput.value = ''; fetchSuppliers(); });
    input.addEventListener('focus', fetchSuppliers);
    input.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('hidden'), 200));
}

// ======================== DESCRIPTION AUTOCOMPLETE ========================
let descTimeout;
function initDescAutocomplete() { document.querySelectorAll('.desc-input').forEach(attachDescAutocomplete); }

function attachDescAutocomplete(input) {
    const dropdown = input.nextElementSibling;
    function fetchSuggestions() {
        const q = input.value.trim();
        const row = input.closest('tr');
        const supplierId = row ? (row.querySelector('.supplier-id-input')?.value || '') : '';
        clearTimeout(descTimeout);
        if (!supplierId && q.length < 2) { dropdown.classList.add('hidden'); return; }
        descTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q });
                if (supplierId) params.append('supplier_id', supplierId);
                const res = await fetch(`${SEARCH_URL}?${params}`);
                const items = await res.json();
                if (!items.length) { dropdown.classList.add('hidden'); return; }
                dropdown.innerHTML = items.map(item =>
                    `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-200 desc-option"
                          data-name="${(item.name || item).toString().replace(/"/g, '&quot;')}"
                          data-item-code="${(item.item_code || '').toString().replace(/"/g, '&quot;')}"
                          data-supplier-id="${item.supplier_id || ''}"
                          data-supplier-name="${(item.supplier_name || '').toString().replace(/"/g, '&quot;')}">${typeof item === 'string' ? item : item.display_name}</div>`
                ).join('');
                positionFixedDropdown(input, dropdown);
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.desc-option').forEach(opt => {
                    opt.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value = this.dataset.name || this.textContent;
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
        const row = input.closest('tr');
        if (row) {
            const itemCodeInput = row.querySelector('.item-code-input');
            if (itemCodeInput && !itemCodeInput.value.trim()) autoGenerateItemCode(row);
        }
    });
}

// ======================== ITEM CODE AUTO-FILL ========================
function attachItemCodeListeners() {
    document.querySelectorAll('.item-code-input').forEach(input => {
        if (input.dataset.itemCodeBound) return;
        input.dataset.itemCodeBound = '1';
        input.addEventListener('blur', function() {
            const code = this.value.trim();
            if (!code) return;
            const row = this.closest('tr');
            if (row) fetchItemByCode(code, row);
        });
    });
}

async function fetchItemByCode(itemCode, row) {
    try {
        const res = await fetch(`${ITEM_CODE_SEARCH_URL}?item_code=${encodeURIComponent(itemCode)}`);
        const data = await res.json();
        if (!data.description) return;
        const descInput = row.querySelector('.desc-input');
        if (descInput && !descInput.value.trim()) descInput.value = data.description;
        if (data.supplier_id) {
            const ss = row.querySelector('.supplier-search');
            const si = row.querySelector('.supplier-id-input');
            const sn = row.querySelector('.supplier-name-input');
            if (ss && !ss.value.trim()) { ss.value = data.supplier_name || ''; if (si) si.value = data.supplier_id; if (sn) sn.value = data.supplier_name || ''; }
        }
        if (data.uom) { const u = row.querySelector('input[name*="[uom]"]'); if (u && !u.value.trim()) u.value = data.uom; }
        if (data.unit_price) { const p = row.querySelector('.item-price'); if (p && !p.value) p.value = data.unit_price; }
    } catch (e) { console.error('Error fetching item by code:', e); }
}

async function autoGenerateItemCode(row) {
    const descInput = row.querySelector('.desc-input');
    const itemCodeInput = row.querySelector('.item-code-input');
    if (!descInput || !itemCodeInput) return;
    const description = descInput.value.trim();
    const supplierId = row.querySelector('.supplier-id-input')?.value || '';
    if (!description) return;
    try {
        const params = new URLSearchParams({ description });
        if (supplierId) params.append('supplier_id', supplierId);
        const res = await fetch(`${GENERATE_ITEM_CODE_URL}?${params}`);
        const data = await res.json();
        if (data.item_code) itemCodeInput.value = data.item_code;
    } catch (e) { console.error('Error generating item code:', e); }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    attachCalculationListeners();
    attachItemCodeListeners();
    initDescAutocomplete();
});
</script>
@endsection
