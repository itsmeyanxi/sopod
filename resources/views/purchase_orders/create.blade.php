@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CREATE PURCHASE ORDER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">PO NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $poNo }}</span>
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

        <form action="{{ route('purchase_orders.store') }}" method="POST" id="poForm">
            @csrf

            <!-- Search PR Section -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-300 mb-2">SEARCH PURCHASE REQUEST (Optional):</label>
                <p class="text-gray-400 text-sm mb-3">Search by PR Number, Requisitioner, or Company to auto-fill data</p>

                <div class="relative">
                    <input
                        type="text"
                        id="prSearchInput"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 pr-10"
                        placeholder="Type to search approved PRs..."
                        autocomplete="off">
                    <span class="absolute right-3 top-2.5 text-gray-500">
                        <i class="fas fa-search"></i>
                    </span>
                </div>

                <!-- Search Results Dropdown -->
                <div id="prSearchResults" class="hidden mt-2 bg-gray-800 border border-gray-700 rounded max-h-64 overflow-y-auto">
                    <!-- Results will be populated here by JavaScript -->
                </div>

                <!-- Selected PR Display -->
                <div id="selectedPRDisplay" class="hidden mt-3 p-3 bg-green-900/20 border border-green-700 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-green-400 font-semibold">Selected PR: </span>
                            <span id="selectedPRText" class="text-gray-300"></span>
                        </div>
                        <button type="button" onclick="clearSelectedPR()" class="text-red-400 hover:text-red-300">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>

                <input type="hidden" name="purchase_request_id" id="selectedPRId" value="{{ old('purchase_request_id', $selectedPR->id ?? '') }}">
            </div>

            <!-- Company Selection -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">COMPANY: <span class="text-red-400">*</span></label>
                <select name="company" id="company" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <option value="">-- Select Company --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company }}" {{ old('company', $selectedPR->company ?? '') == $company ? 'selected' : '' }}>
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
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER:</label>
                        <input type="text" name="supplier" id="supplier" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier', $selectedPR->supplier ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER ADDRESS:</label>
                        <textarea name="supplier_address" id="supplier_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('supplier_address', $selectedPR->address ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE:</label>
                        <input type="text" name="consignee" id="consignee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('consignee', $selectedPR->requisitioner ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE ADDRESS:</label>
                        <textarea name="consignee_address" id="consignee_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('consignee_address') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                        <textarea name="delivery_address" id="delivery_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('delivery_address', $selectedPR->delivery_address ?? '') }}</textarea>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">ORDER DATE: <span class="text-red-400">*</span></label>
                        <input type="date" name="order_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('order_date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">EXPECTED DELIVERY DATE:</label>
                        <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('expected_delivery_date', $selectedPR->date_needed ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" id="payment_terms" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms', $selectedPR->terms ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LOCATION:</label>
                        <input type="text" name="location" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('location') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">HOUSE:</label>
                        <input type="text" name="house" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('house') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PR#:</label>
                        <input type="text" name="pr_no" id="pr_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('pr_no', $selectedPR->pr_no ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LC PRICE:</label>
                        <input type="number" step="0.01" name="lc_price" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('lc_price') }}">
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-white">Items</h3>
                    <button type="button" onclick="addRow()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-700" id="itemsTable">
                        <thead class="bg-red-700 text-white">
                            <tr>
                                <th class="border border-gray-700 px-2 py-2 w-12">NO.</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">ITEM CODE</th>
                                <th class="border border-gray-700 px-2 py-2 w-20">QTY</th>
                                <th class="border border-gray-700 px-2 py-2 w-24">UOM</th>
                                <th class="border border-gray-700 px-2 py-2">DESCRIPTION</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">UNIT PRICE</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">TAX</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">TOTAL</th>
                                <th class="border border-gray-700 px-2 py-2 w-16">ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @if($selectedPR && $selectedPR->items->count() > 0)
                                @foreach($selectedPR->items as $index => $item)
                                <tr>
                                    <td class="border border-gray-700 px-2 py-2 text-center text-gray-300">{{ $index + 1 }}</td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" value="{{ $item->qty }}" required></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->uom }}" required></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->description }}" required></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price" value="{{ $item->unit_price }}"></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][tax]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-tax" value="0"></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" readonly></td>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="border border-gray-700 px-2 py-2 text-center text-gray-300">1</td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" required></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[0][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" required></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price"></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][tax]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-tax" value="0"></td>
                                    <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[0][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" readonly></td>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">REMARKS:</label>
                <textarea name="remarks" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter remarks...">{{ old('remarks') }}</textarea>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-700 rounded">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Prepared By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Checked By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By:</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-700 text-gray-300 text-xs italic">
                                <td class="border border-gray-700 px-4 py-2 text-center">Purchasing Officer</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">Accounting Manager</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">Authorized Signatory</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('purchase_orders.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let rowCount = {{ $selectedPR && $selectedPR->items->count() > 0 ? $selectedPR->items->count() : 1 }};

// Available PRs data
const availablePRs = @json($purchaseRequests);

// PR Search functionality
const prSearchInput = document.getElementById('prSearchInput');
const prSearchResults = document.getElementById('prSearchResults');
const selectedPRDisplay = document.getElementById('selectedPRDisplay');
const selectedPRText = document.getElementById('selectedPRText');
const selectedPRId = document.getElementById('selectedPRId');

prSearchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();

    if (searchTerm.length < 2) {
        prSearchResults.classList.add('hidden');
        return;
    }

    const filteredPRs = availablePRs.filter(pr => {
        return pr.pr_no.toLowerCase().includes(searchTerm) ||
               pr.requisitioner.toLowerCase().includes(searchTerm) ||
               pr.company.toLowerCase().includes(searchTerm);
    });

    if (filteredPRs.length === 0) {
        prSearchResults.innerHTML = '<div class="p-3 text-gray-400 text-center">No matching PRs found</div>';
        prSearchResults.classList.remove('hidden');
        return;
    }

    let resultsHTML = '';
    filteredPRs.forEach(pr => {
        const dateFormatted = new Date(pr.date_of_request).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        resultsHTML += `
            <div class="p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700" onclick="selectPR(${pr.id}, '${pr.pr_no}', '${pr.requisitioner}', '${dateFormatted}')">
                <div class="font-semibold text-white">${pr.pr_no}</div>
                <div class="text-sm text-gray-400">${pr.requisitioner} • ${pr.company}</div>
                <div class="text-xs text-gray-500">${dateFormatted}</div>
            </div>
        `;
    });

    prSearchResults.innerHTML = resultsHTML;
    prSearchResults.classList.remove('hidden');
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    if (!prSearchInput.contains(e.target) && !prSearchResults.contains(e.target)) {
        prSearchResults.classList.add('hidden');
    }
});

function selectPR(prId, prNo, requisitioner, dateFormatted) {
    selectedPRId.value = prId;
    selectedPRText.textContent = `${prNo} - ${requisitioner} (${dateFormatted})`;
    selectedPRDisplay.classList.remove('hidden');
    prSearchResults.classList.add('hidden');
    prSearchInput.value = '';

    // Redirect to populate form with PR data
    window.location.href = '{{ route("purchase_orders.create") }}?pr_id=' + prId;
}

function clearSelectedPR() {
    selectedPRId.value = '';
    selectedPRDisplay.classList.add('hidden');
    window.location.href = '{{ route("purchase_orders.create") }}';
}

// Initialize selected PR display if PR was pre-selected
@if($selectedPR)
document.addEventListener('DOMContentLoaded', function() {
    const dateFormatted = new Date('{{ $selectedPR->date_of_request->format('Y-m-d') }}').toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    selectedPRText.textContent = '{{ $selectedPR->pr_no }} - {{ $selectedPR->requisitioner }} (' + dateFormatted + ')';
    selectedPRDisplay.classList.remove('hidden');
});
@endif

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const newRow = tbody.insertRow();
    newRow.innerHTML = `
        <td class="border border-gray-700 px-2 py-2 text-center text-gray-300">${rowCount + 1}</td>
        <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[${rowCount}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white"></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required></td>
        <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[${rowCount}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" required></td>
        <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[${rowCount}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" required></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price"></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][tax]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-tax" value="0"></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" readonly></td>
        <td class="border border-gray-700 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    rowCount++;
    attachCalculationListeners();
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
    document.querySelectorAll('.item-qty, .item-price, .item-tax').forEach(input => {
        input.removeEventListener('input', calculateTotal);
        input.addEventListener('input', calculateTotal);
    });
}

function calculateTotal(e) {
    const row = e.target.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const tax = parseFloat(row.querySelector('.item-tax').value) || 0;
    const total = (qty * price) + tax;
    row.querySelector('.item-total').value = total.toFixed(2);
}

// Initialize calculation listeners
document.addEventListener('DOMContentLoaded', function() {
    attachCalculationListeners();
});
</script>
@endsection
