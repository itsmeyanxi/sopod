@extends('layouts.app')

@section('title', $notesOnly ? 'Edit Notes - Purchase Order' : 'Edit Purchase Order')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">{{ $notesOnly ? 'EDIT NOTES' : 'EDIT PURCHASE ORDER' }}</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-500">PO NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $purchaseOrder->po_no }}</span>
            </div>
        </div>

        @if($notesOnly)
            <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                This Purchase Order is approved. Only notes can be edited.
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

        @if($notesOnly)
        {{-- NOTES-ONLY FORM for approved POs --}}
        <form action="{{ route('purchase_orders.update_notes', $purchaseOrder->id) }}" method="POST" id="notesForm">
            @csrf
            @method('PUT')

            <!-- Read-only PO Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-2">
                    <p class="text-gray-500"><span class="font-semibold text-gray-500">Company:</span> {{ $purchaseOrder->company }}</p>
                    <p class="text-gray-500"><span class="font-semibold text-gray-500">Order Date:</span> {{ $purchaseOrder->order_date->format('M d, Y') }}</p>
                    <p class="text-gray-500"><span class="font-semibold text-gray-500">PR#:</span> {{ $purchaseOrder->pr_no ?? 'N/A' }}</p>
                </div>
                <div class="space-y-2">
                    <p class="text-gray-500"><span class="font-semibold text-gray-500">Payment Terms:</span> {{ $purchaseOrder->payment_terms ?? 'N/A' }}</p>
                    <p class="text-gray-500"><span class="font-semibold text-gray-500">Status:</span> <span class="text-green-700">Approved</span></p>
                </div>
            </div>

            <!-- Items Table with editable Notes only -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-white mb-2">Items</h3>
                <div class="overflow-x-auto">
                    <table class="border-collapse border border-gray-700" id="itemsTable" style="min-width:1100px; width:100%;">
                        <thead class="bg-red-700 text-white">
                            <tr>
                                <th class="border border-gray-700 px-2 py-2" style="width:40px">NO.</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:130px">ITEM CODE</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:70px">QTY</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:80px">UOM</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:280px">DESCRIPTION</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:200px">SUPPLIER</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:110px">UNIT PRICE</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:100px">TOTAL</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:220px">NOTE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->items as $index => $item)
                            <tr>
                                <td class="border border-gray-700 px-2 py-2 text-center text-gray-500">{{ $index + 1 }}</td>
                                <td class="border border-gray-700 px-2 py-2 text-gray-500">{{ $item->item_code }}</td>
                                <td class="border border-gray-700 px-2 py-2 text-gray-500">{{ $item->qty }}</td>
                                <td class="border border-gray-700 px-2 py-2 text-gray-500">{{ $item->uom }}</td>
                                <td class="border border-gray-700 px-2 py-2 text-gray-500">{{ $item->description }}</td>
                                <td class="border border-gray-700 px-2 py-2 text-gray-500">{{ $item->supplier_name ?? $purchaseOrder->supplier ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-2 py-2 text-gray-500">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="border border-gray-700 px-2 py-2 text-gray-500">{{ number_format($item->total, 2) }}</td>
                                <td class="border border-gray-700 px-2 py-2">
                                    <input type="text" name="notes[{{ $item->id }}]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->note }}" placeholder="Add note...">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('purchase_orders.show', $purchaseOrder->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-yellow-700">
                    <i class="fas fa-sticky-note mr-1"></i> Update Notes
                </button>
            </div>
        </form>

        @else
        {{-- FULL EDIT FORM for non-approved POs --}}
        <form action="{{ route('purchase_orders.update', $purchaseOrder->id) }}" method="POST" id="poForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Select PR Section -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-500 mb-2">LINKED PURCHASE REQUEST:</label>
                <select name="purchase_request_id" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">-- No PR Linked --</option>
                    @foreach($purchaseRequests as $pr)
                        <option value="{{ $pr->id }}" {{ old('purchase_request_id', $purchaseOrder->purchase_request_id) == $pr->id ? 'selected' : '' }}>
                            {{ $pr->pr_no }} - {{ $pr->requisitioner }} ({{ $pr->date_of_request->format('M d, Y') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Company Selection -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">COMPANY: <span class="text-red-700">*</span></label>
                <select name="company" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <option value="">-- Select Company --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company }}" {{ old('company', $purchaseOrder->company) == $company ? 'selected' : '' }}>
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
                        <label class="block font-semibold text-gray-500 mb-1">CONSIGNEE:</label>
                        <input type="text" name="consignee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('consignee', $purchaseOrder->consignee) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">CONSIGNEE ADDRESS:</label>
                        <textarea name="consignee_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('consignee_address', $purchaseOrder->consignee_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">DELIVERY ADDRESS:</label>
                        <textarea name="delivery_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('delivery_address', $purchaseOrder->delivery_address) }}</textarea>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">ORDER DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="order_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('order_date', $purchaseOrder->order_date->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">EXPECTED DELIVERY DATE:</label>
                        <input type="date" name="expected_delivery_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('Y-m-d') : '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms', $purchaseOrder->payment_terms) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">LOCATION:</label>
                        <input type="text" name="location" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('location', $purchaseOrder->location) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">HOUSE:</label>
                        <input type="text" name="house" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('house', $purchaseOrder->house) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">PR#:</label>
                        <input type="text" name="pr_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('pr_no', $purchaseOrder->pr_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">LC PRICE:</label>
                        <input type="number" step="0.01" name="lc_price" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('lc_price', $purchaseOrder->lc_price) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">CURRENCY:</label>
                        <select name="currency" id="currency_select" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="onCurrencyChange()">
                            @foreach($currencies as $cur)
                                <option value="{{ $cur->code }}"
                                        data-rate="{{ $cur->rate_to_php }}"
                                        data-symbol="{{ $cur->symbol }}"
                                        {{ old('currency', $purchaseOrder->currency ?? 'PHP') === $cur->code ? 'selected' : '' }}>
                                    {{ $cur->code }} — {{ $cur->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @php $editCurrency = old('currency', $purchaseOrder->currency ?? 'PHP'); @endphp
                    <div id="exchange_rate_row" class="{{ $editCurrency === 'PHP' ? 'hidden' : '' }}">
                        <label class="block font-semibold text-gray-500 mb-1">EXCHANGE RATE <span class="text-gray-500 text-xs" id="rate_label">(1 {{ $editCurrency }} = ? PHP)</span>:</label>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500">₱</span>
                            <input type="number" step="0.0001" name="exchange_rate" id="exchange_rate" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('exchange_rate', $purchaseOrder->exchange_rate ?? 1) }}">
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Rate used when PO was created. You may update.</p>
                    </div>
                </div>
            </div>

            <!-- Hidden fields for backward compatibility -->
            <input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}">
            <input type="hidden" name="supplier" value="{{ $purchaseOrder->supplier }}">
            <input type="hidden" name="supplier_address" value="{{ $purchaseOrder->supplier_address }}">

            <!-- Items Table -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-white">Items</h3>
                    <button type="button" onclick="addRow()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="border-collapse border border-gray-700" id="itemsTable" style="min-width:1400px; width:100%;">
                        <thead class="bg-red-700 text-white">
                            <tr>
                                <th class="border border-gray-700 px-2 py-2" style="width:70px">ACTION</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:40px">NO.</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:130px">ITEM CODE</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:70px">QTY</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:80px">UOM</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:280px">DESCRIPTION</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:200px">SUPPLIER</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:110px">UNIT PRICE</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:80px">TAX</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:100px">TOTAL</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:180px">NOTE</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @foreach($purchaseOrder->items as $index => $item)
                            <tr>
                                <td class="border border-gray-700 px-2 py-2 text-center">
                                    <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition" title="Delete row">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </td>
                                <td class="border border-gray-700 px-2 py-2 text-center text-gray-500">{{ $index + 1 }}</td>
                                <td class="border border-gray-700 px-2 py-2">
                                    <input type="text" name="items[{{ $index }}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" value="{{ $item->item_code }}" autocomplete="off">
                                    <input type="hidden" name="items[{{ $index }}][purchase_request_item_id]" value="{{ $item->purchase_request_item_id }}">
                                </td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" value="{{ $item->qty }}" required></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->uom }}" required></td>
                                <td class="border border-gray-700 px-2 py-2">
                                    <div class="relative">
                                        <input type="text" name="items[{{ $index }}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" value="{{ $item->description }}" required autocomplete="off">
                                        <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                    </div>
                                </td>
                                <td class="border border-gray-700 px-2 py-2">
                                    <div class="relative">
                                        <input type="text" name="items[{{ $index }}][supplier_name]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white supplier-input" value="{{ $item->supplier_name ?? $purchaseOrder->supplier }}" autocomplete="off" placeholder="Search supplier...">
                                        <input type="hidden" name="items[{{ $index }}][supplier_id]" class="supplier-id-input" value="{{ $item->supplier_id ?? $purchaseOrder->supplier_id }}">
                                        <div class="supplier-dropdown hidden bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto"></div>
                                    </div>
                                </td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price" value="{{ $item->unit_price }}"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][tax]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-tax" value="{{ $item->tax }}"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" value="{{ $item->total }}" readonly></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->note }}" placeholder="Note..."></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Currency Totals Summary -->
            <div id="currency_summary" class="mb-4 {{ ($purchaseOrder->currency ?? 'PHP') === 'PHP' ? 'hidden' : '' }}">
                <div class="bg-gray-900 border border-purple-700 rounded p-4">
                    <h3 class="font-semibold text-purple-700 mb-2">PHP Equivalent Summary</h3>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <span class="text-gray-500">Total (<span id="summary_currency">{{ $purchaseOrder->currency ?? 'USD' }}</span>):</span>
                            <span class="text-white font-bold ml-2" id="summary_foreign_total">0.00</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Exchange Rate:</span>
                            <span class="text-white ml-2">1 <span id="summary_code">{{ $purchaseOrder->currency ?? 'USD' }}</span> = ₱<span id="summary_rate">{{ number_format($purchaseOrder->exchange_rate ?? 1, 4) }}</span></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Total (PHP):</span>
                            <span class="text-green-700 font-bold ml-2">₱<span id="summary_php_total">0.00</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">REMARKS:</label>
                <textarea name="remarks" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter remarks...">{{ old('remarks', $purchaseOrder->remarks) }}</textarea>
            </div>

            <!-- Quotation File Upload -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">QUOTATION:</label>
                @if($purchaseOrder->quotation)
                    <div class="mb-3 p-3 bg-green-50 border border-green-200 rounded">
                        <p class="text-green-700 text-sm">
                            <i class="fas fa-file-check mr-2"></i>
                            <a href="{{ asset('storage/' . $purchaseOrder->quotation) }}" target="_blank" class="hover:underline">
                                Current file: {{ basename($purchaseOrder->quotation) }}
                            </a>
                        </p>
                    </div>
                @endif
                <div class="flex items-center gap-4">
                    <input type="file" name="quotation" id="quotation" class="flex-1 bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    <span class="text-gray-500 text-sm">(PDF, Word, Excel, Image)</span>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-700 rounded">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Prepared By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Checked By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Approved By:</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-700 text-gray-500 text-xs italic">
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
                <a href="{{ route('purchase_orders.show', $purchaseOrder->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-save mr-1"></i> Update Purchase Order
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

@if(!$notesOnly)
<script>
let rowCount = {{ $purchaseOrder->items->count() }};

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const newRow = tbody.insertRow();
    newRow.innerHTML = `
        <td class="border border-gray-700 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition" title="Delete row">
                <i class="fas fa-trash mr-1"></i>Delete
            </button>
        </td>
        <td class="border border-gray-700 px-2 py-2 text-center text-gray-500">${rowCount + 1}</td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" autocomplete="off">
            <input type="hidden" name="items[${rowCount}][purchase_request_item_id]" value="">
        </td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" required></td>
        <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[${rowCount}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" required></td>
        <td class="border border-gray-700 px-2 py-2">
            <div class="relative">
                <input type="text" name="items[${rowCount}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off">
                <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
            </div>
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <div class="relative">
                <input type="text" name="items[${rowCount}][supplier_name]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white supplier-input" autocomplete="off" placeholder="Search supplier...">
                <input type="hidden" name="items[${rowCount}][supplier_id]" class="supplier-id-input" value="">
                <div class="supplier-dropdown hidden bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto"></div>
            </div>
        </td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price"></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][tax]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-tax" value="0"></td>
        <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[${rowCount}][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" readonly></td>
        <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[${rowCount}][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" placeholder="Note..."></td>
    `;
    rowCount++;
    attachCalculationListeners();
    attachItemCodeListeners();
    const newDesc = newRow.querySelector('.desc-input');
    if (newDesc) attachDescAutocomplete(newDesc);
    const newSupplier = newRow.querySelector('.supplier-input');
    if (newSupplier) attachSupplierAutocomplete(newSupplier);
}

function removeRow(btn) {
    const row = btn.closest('tr');
    row.remove();
    reorderRows();
}

function reorderRows() {
    const rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach((row, index) => {
        row.cells[1].textContent = index + 1;
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

function calculateRowTotal(row) {
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const tax = parseFloat(row.querySelector('.item-tax').value) || 0;
    const total = tax > 0 ? (qty * price) + tax : qty * price;
    row.querySelector('.item-total').value = total.toFixed(2);
}

function calculateTotal(e) {
    calculateRowTotal(e.target.closest('tr'));
    updateCurrencySummary();
}

function recalculateAllTotals() {
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        calculateRowTotal(row);
    });
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

// ==================== FIXED-POSITION DROPDOWN HELPER ====================
const SUPPLIER_SEARCH_URL = '{{ route("purchase_orders.search_suppliers") }}';
const SEARCH_URL = '{{ route("non_trade_items.search") }}';
const ITEM_CODE_SEARCH_URL = '{{ route("purchase_orders.search_by_item_code") }}';
const GENERATE_ITEM_CODE_URL = '{{ route("purchase_orders.generate_item_code") }}';

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

// Reposition visible dropdowns on scroll instead of hiding
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

// ==================== SUPPLIER AUTOCOMPLETE ====================
let supplierTimeout;

function attachSupplierAutocomplete(input) {
    const wrapper = input.closest('.relative');
    const dropdown = wrapper.querySelector('.supplier-dropdown');
    const hiddenId = wrapper.querySelector('.supplier-id-input');

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
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 supplier-option"
                          data-id="${s.id}" data-name="${s.supplier_name}">
                        <strong>${s.supplier_name}</strong>
                        <span class="text-gray-500 text-xs ml-1">${s.supplier_code || ''}</span>
                    </div>`
                ).join('');
                positionFixedDropdown(input, dropdown);
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.supplier-option').forEach(opt => {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        input.value = this.dataset.name;
                        hiddenId.value = this.dataset.id;
                        dropdown.classList.add('hidden');
                    });
                });
            } catch (e) { dropdown.classList.add('hidden'); }
        }, 250);
    }

    input.addEventListener('input', function() { hiddenId.value = ''; fetchSuppliers(); });
    input.addEventListener('focus', fetchSuppliers);
    input.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('hidden'), 200));
}

// ==================== DESCRIPTION AUTOCOMPLETE ====================
let descTimeout;

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
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 desc-option"
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
                            const ss = row.querySelector('.supplier-input');
                            const si = row.querySelector('.supplier-id-input');
                            if (ss) ss.value = this.dataset.supplierName || '';
                            if (si) si.value = this.dataset.supplierId;
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
            const ss = row.querySelector('.supplier-input');
            const si = row.querySelector('.supplier-id-input');
            if (ic) ic.value = '';
            if (ss) ss.value = '';
            if (si) si.value = '';
        }
        fetchSuggestions();
    });
    input.addEventListener('focus', fetchSuggestions);
    input.addEventListener('blur', function() {
        setTimeout(() => dropdown.classList.add('hidden'), 200);
        const row = input.closest('tr');
        if (row) {
            const itemCodeInput = row.querySelector('.item-code-input');
            if (itemCodeInput && !itemCodeInput.value.trim()) autoGenerateItemCode(row);
        }
    });
}

// ==================== ITEM CODE AUTO-FILL ====================
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
            const ss = row.querySelector('.supplier-input');
            const si = row.querySelector('.supplier-id-input');
            if (ss && !ss.value.trim()) { ss.value = data.supplier_name || ''; if (si) si.value = data.supplier_id; }
        }
        if (data.uom) { const u = row.querySelector('input[name*="[uom]"]'); if (u && !u.value.trim()) u.value = data.uom; }
        if (data.unit_price) { const p = row.querySelector('.item-price'); if (p && !p.value) p.value = data.unit_price; }
    } catch (e) { console.error('Error fetching item by code:', e); }
}

async function autoGenerateItemCode(row) {
    const descInput = row.querySelector('.desc-input');
    const itemCodeInput = row.querySelector('.item-code-input');
    const supplierId = row.querySelector('.supplier-id-input')?.value || '';
    if (!descInput || !itemCodeInput || !descInput.value.trim()) return;
    try {
        const params = new URLSearchParams({ description: descInput.value.trim() });
        if (supplierId) params.append('supplier_id', supplierId);
        const res = await fetch(`${GENERATE_ITEM_CODE_URL}?${params}`);
        const data = await res.json();
        if (data.item_code) itemCodeInput.value = data.item_code;
    } catch (e) { console.error('Error generating item code:', e); }
}

document.addEventListener('DOMContentLoaded', function() {
    attachCalculationListeners();
    attachItemCodeListeners();
    recalculateAllTotals();
    document.querySelectorAll('.desc-input').forEach(attachDescAutocomplete);
    document.querySelectorAll('.supplier-input').forEach(attachSupplierAutocomplete);
});
</script>
@endif
@endsection
