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
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('purchase_requests.store') }}" method="POST" id="prForm">
            @csrf

            <!-- Company Selection -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">COMPANY: <span class="text-red-400">*</span></label>
                <select name="company" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <option value="">-- Select Company --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company }}" {{ old('company') == $company ? 'selected' : '' }}>
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
                        <label class="block font-semibold text-gray-300 mb-1">REQUISITIONER: <span class="text-red-400">*</span></label>
                        <input type="text" name="requisitioner" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requisitioner') }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DEPARTMENT:</label>
                        <input type="text" name="department" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('department') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER:</label>
                        <select name="supplier_id" id="supplier_id" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="populateSupplierDetails()">
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                        data-address="{{ $supplier->address }}"
                                        data-contact="{{ $supplier->contact_number }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->supplier_name }} ({{ $supplier->supplier_code }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="supplier" id="supplier_text" value="{{ old('supplier') }}">
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
                        <label class="block font-semibold text-gray-300 mb-1">DATE OF REQUEST: <span class="text-red-400">*</span></label>
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
                                <input type="radio" name="type_of_request" value="urgent" class="form-radio text-purple-500">
                                <span class="ml-2">Urgent</span>
                            </label>
                            <label class="inline-flex items-center text-gray-300">
                                <input type="radio" name="type_of_request" value="regular" class="form-radio text-purple-500">
                                <span class="ml-2">Regular</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">WITH BUDGET:</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center text-gray-300">
                                <input type="radio" name="with_budget" value="yes" class="form-radio text-purple-500">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center text-gray-300">
                                <input type="radio" name="with_budget" value="no" class="form-radio text-purple-500">
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
                    <table class="w-full border-collapse border border-gray-700" id="itemsTable">
                        <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                            <tr>
                                <th class="border px-2 py-2 w-12">NO.</th>
                                <th class="border px-2 py-2 w-20">QTY</th>
                                <th class="border px-2 py-2 w-24">UOM</th>
                                <th class="border px-2 py-2">DESCRIPTION</th>
                                <th class="border px-2 py-2 w-32">UNIT PRICE</th>
                                <th class="border px-2 py-2 w-32">AMOUNT</th>
                                <th class="border px-2 py-2">REMARKS/SPECIFICATIONS</th>
                                <th class="border px-2 py-2 w-16">ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody" class="bg-gray-800 text-gray-300 divide-y divide-gray-700">
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-2 py-2 text-center">1</td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" required></td>
                                <td class="border border-gray-700 px-2 py-2">
                                    <div class="relative">
                                        <input type="text" name="items[0][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off">
                                        <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                    </div>
                                </td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][amount]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-amount" readonly></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][remarks]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
                                <td class="border border-gray-700 px-2 py-2 text-center">
                                    <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reason for Requisition -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">REASON FOR REQUISITION:</label>
                <textarea name="reason_for_requisition" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter reason for this requisition...">{{ old('reason_for_requisition') }}</textarea>
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
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
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
                                <td class="border border-gray-700 px-4 py-2 text-center" colspan="2">President</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('purchase_requests.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
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

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const newRow = tbody.insertRow();
    newRow.className = 'hover:bg-gray-700/40';
    newRow.innerHTML = `
        <td class="border border-gray-700 px-2 py-2 text-center">${rowCount + 1}</td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required></td>
        <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[${rowCount}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" required></td>
        <td class="border border-gray-700 px-2 py-2"><div class="relative"><input type="text" name="items[${rowCount}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off"><div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div></div></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price"></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][amount]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-amount" readonly></td>
        <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[${rowCount}][remarks]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
        <td class="border border-gray-700 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    rowCount++;
    attachCalculationListeners();
    const newDesc = newRow.querySelector('.desc-input');
    if (newDesc) attachDescAutocomplete(newDesc);
}

function removeRow(btn) {
    const row = btn.closest('tr');
    row.remove();
    reorderRows();
}

function reorderRows() {
    const rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach((row, index) => {
        row.cells[0].textContent = index + 1;
        row.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
            }
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
    const amount = qty * price;
    row.querySelector('.item-amount').value = amount.toFixed(2);
}

// Populate supplier details when supplier is selected
function populateSupplierDetails() {
    const select = document.getElementById('supplier_id');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption.value) {
        const address = selectedOption.getAttribute('data-address');
        if (address) {
            const addressField = document.querySelector('textarea[name="address"]');
            if (addressField) {
                addressField.value = address;
            }
        }

        const contact = selectedOption.getAttribute('data-contact');
        if (contact) {
            const contactField = document.querySelector('input[name="contact_number"]');
            if (contactField) {
                contactField.value = contact;
            }
        }

        // Store supplier name in hidden field for backwards compatibility
        const supplierName = selectedOption.text.split(' (')[0];
        document.getElementById('supplier_text').value = supplierName;
    }
}

// Initialize calculation listeners
document.addEventListener('DOMContentLoaded', function() {
    attachCalculationListeners();
    initDescAutocomplete();
});

// Description autocomplete
const SEARCH_URL = '{{ route("non_trade_items.search") }}';
let descTimeout;

function initDescAutocomplete() {
    document.querySelectorAll('.desc-input').forEach(attachDescAutocomplete);
}

function attachDescAutocomplete(input) {
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
        clearTimeout(descTimeout);
        if (!supplierId && q.length < 2) { dropdown.classList.add('hidden'); return; }
        descTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q });
                if (supplierId) params.append('supplier_id', supplierId);
                const res = await fetch(`${SEARCH_URL}?${params}`);
                const items = await res.json();
                if (!items.length) { dropdown.classList.add('hidden'); return; }
                dropdown.innerHTML = items.map(name =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 desc-option">${name}</div>`
                ).join('');
                positionDropdown();
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.desc-option').forEach(opt => {
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
</script>
@endsection
