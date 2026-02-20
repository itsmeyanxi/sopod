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

        <form action="{{ route('purchase_orders.store') }}" method="POST" id="poForm" enctype="multipart/form-data">
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

                <input type="hidden" name="purchase_request_id" id="selectedPRId" value="{{ old('purchase_request_id', '') }}">
            </div>

            <!-- Company Selection -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">COMPANY: <span class="text-red-400">*</span></label>
                <select name="company" id="company" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
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
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER:</label>
                        <select name="supplier_id" id="supplier_id" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="populateSupplierText()">
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                        data-name="{{ $supplier->supplier_name }}"
                                        data-address="{{ $supplier->address }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="supplier" id="supplier_text" value="{{ old('supplier') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER ADDRESS:</label>
                        <textarea name="supplier_address" id="supplier_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('supplier_address') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE:</label>
                        <input type="text" name="consignee" id="consignee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('consignee') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE ADDRESS:</label>
                        <textarea name="consignee_address" id="consignee_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('consignee_address') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                        <textarea name="delivery_address" id="delivery_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('delivery_address') }}</textarea>
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
                        <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('expected_delivery_date') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" id="payment_terms" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms') }}">
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
                        <input type="text" name="pr_no" id="pr_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('pr_no') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LC PRICE:</label>
                        <input type="number" step="0.01" name="lc_price" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('lc_price') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CURRENCY:</label>
                        <select name="currency" id="currency_select" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="onCurrencyChange()">
                            @foreach($currencies as $cur)
                                <option value="{{ $cur->code }}"
                                        data-rate="{{ $cur->rate_to_php }}"
                                        data-symbol="{{ $cur->symbol }}"
                                        {{ old('currency', 'PHP') === $cur->code ? 'selected' : '' }}>
                                    {{ $cur->code }} — {{ $cur->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="exchange_rate_row" class="{{ old('currency', 'PHP') === 'PHP' ? 'hidden' : '' }}">
                        <label class="block font-semibold text-gray-300 mb-1">EXCHANGE RATE <span class="text-gray-400 text-xs" id="rate_label">(1 USD = ? PHP)</span>:</label>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-400">₱</span>
                            <input type="number" step="0.0001" name="exchange_rate" id="exchange_rate" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('exchange_rate', 1) }}">
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Auto-filled from current rate. You may override.</p>
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
                                    <td class="border border-gray-700 px-2 py-2"><div class="relative"><input type="text" name="items[{{ $index }}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" value="{{ $item->description }}" required autocomplete="off"><div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div></div></td>
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
                                    <td class="border border-gray-700 px-2 py-2"><div class="relative"><input type="text" name="items[0][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off"><div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div></div></td>
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

            <!-- Currency Totals Summary -->
            <div id="currency_summary" class="mb-4 hidden">
                <div class="bg-gray-900 border border-purple-700 rounded p-4">
                    <h3 class="font-semibold text-purple-300 mb-2">PHP Equivalent Summary</h3>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <span class="text-gray-400">Total (<span id="summary_currency">USD</span>):</span>
                            <span class="text-white font-bold ml-2" id="summary_foreign_total">0.00</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Exchange Rate:</span>
                            <span class="text-white ml-2">1 <span id="summary_code">USD</span> = ₱<span id="summary_rate">0.00</span></span>
                        </div>
                        <div>
                            <span class="text-gray-400">Total (PHP):</span>
                            <span class="text-green-400 font-bold ml-2">₱<span id="summary_php_total">0.00</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">REMARKS:</label>
                <textarea name="remarks" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter remarks...">{{ old('remarks') }}</textarea>
            </div>

            <!-- Quotation File Upload -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">QUOTATION:</label>
                <div class="flex items-center gap-4">
                    <input type="file" name="quotation" id="quotation" class="flex-1 bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    <span class="text-gray-400 text-sm">(PDF, Word, Excel, Image)</span>
                </div>
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
        <td class="border border-gray-700 px-2 py-2"><div class="relative"><input type="text" name="items[${rowCount}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off"><div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div></div></td>
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
    updateCurrencySummary();
}

// Currency handling
function onCurrencyChange() {
    const select = document.getElementById('currency_select');
    const code = select.value;
    const rate = parseFloat(select.selectedOptions[0].getAttribute('data-rate')) || 1;
    const rateRow = document.getElementById('exchange_rate_row');
    const rateInput = document.getElementById('exchange_rate');
    const rateLabel = document.getElementById('rate_label');
    if (code === 'PHP') {
        rateRow.classList.add('hidden');
        rateInput.value = 1;
    } else {
        rateRow.classList.remove('hidden');
        rateInput.value = rate.toFixed(4);
        rateLabel.textContent = `(1 ${code} = ? PHP)`;
    }
    updateCurrencySummary();
}

function updateCurrencySummary() {
    const code = document.getElementById('currency_select').value;
    const rate = parseFloat(document.getElementById('exchange_rate').value) || 1;
    const summary = document.getElementById('currency_summary');
    if (code === 'PHP') {
        summary.classList.add('hidden');
        return;
    }
    let foreignTotal = 0;
    document.querySelectorAll('.item-total').forEach(inp => {
        foreignTotal += parseFloat(inp.value) || 0;
    });
    summary.classList.remove('hidden');
    document.getElementById('summary_currency').textContent = code;
    document.getElementById('summary_code').textContent = code;
    document.getElementById('summary_rate').textContent = rate.toFixed(4);
    document.getElementById('summary_foreign_total').textContent = code + ' ' + foreignTotal.toFixed(2);
    document.getElementById('summary_php_total').textContent = (foreignTotal * rate).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

document.getElementById('exchange_rate').addEventListener('input', updateCurrencySummary);

function populateSupplierText() {
    const select = document.getElementById('supplier_id');
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption.value) {
        document.getElementById('supplier_text').value = selectedOption.getAttribute('data-name') || '';
        const address = selectedOption.getAttribute('data-address');
        if (address) {
            const addressField = document.querySelector('textarea[name="supplier_address"]');
            if (addressField) addressField.value = address;
        }
    }
}

// Description autocomplete
const SEARCH_URL = '{{ route("non_trade_items.search") }}';
let descTimeout;

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
                        // Auto-generate item code after selecting from dropdown
                        const row = input.closest('tr');
                        if (row) autoGenerateItemCode(row);
                    });
                });
            } catch (e) { dropdown.classList.add('hidden'); }
        }, 250);
    }

    input.addEventListener('input', fetchSuggestions);
    input.addEventListener('focus', fetchSuggestions);
    input.addEventListener('blur', function() {
        setTimeout(() => dropdown.classList.add('hidden'), 150);
        // Auto-generate item code on blur if it's empty
        const row = input.closest('tr');
        if (row) {
            const itemCodeInput = row.querySelector('input[name*="[item_code]"]');
            if (itemCodeInput && !itemCodeInput.value.trim()) {
                autoGenerateItemCode(row);
            }
        }
    });
    window.addEventListener('scroll', () => dropdown.classList.add('hidden'), true);
}

// Auto-generate item code
async function autoGenerateItemCode(row) {
    const descInput = row.querySelector('input[name*="[description]"]');
    const itemCodeInput = row.querySelector('input[name*="[item_code]"]');
    const supplierId = document.getElementById('supplier_id').value;

    if (!descInput || !itemCodeInput || !descInput.value.trim()) {
        return;
    }

    try {
        const response = await fetch('{{ route("purchase_orders.generate_item_code") }}', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
        });

        // Rebuild URL with proper params
        const url = new URL('{{ route("purchase_orders.generate_item_code") }}', window.location.origin);
        url.searchParams.append('description', descInput.value.trim());
        if (supplierId) url.searchParams.append('supplier_id', supplierId);

        const res = await fetch(url.toString());
        const data = await res.json();

        if (data.item_code && !itemCodeInput.value.trim()) {
            itemCodeInput.value = data.item_code;
        }
    } catch (error) {
        console.error('Error generating item code:', error);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    attachCalculationListeners();
    document.querySelectorAll('.desc-input').forEach(attachDescAutocomplete);
});
</script>
@endsection
