@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT PURCHASE ORDER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">PO NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $purchaseOrder->po_no }}</span>
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

        <form action="{{ route('purchase_orders.update', $purchaseOrder->id) }}" method="POST" id="poForm">
            @csrf
            @method('PUT')

            <!-- Select PR Section -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-300 mb-2">LINKED PURCHASE REQUEST:</label>
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
                <label class="block font-semibold text-gray-300 mb-2">COMPANY: <span class="text-red-400">*</span></label>
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
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER:</label>
                        <input type="text" name="supplier" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier', $purchaseOrder->supplier) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER ADDRESS:</label>
                        <textarea name="supplier_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('supplier_address', $purchaseOrder->supplier_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE:</label>
                        <input type="text" name="consignee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('consignee', $purchaseOrder->consignee) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE ADDRESS:</label>
                        <textarea name="consignee_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('consignee_address', $purchaseOrder->consignee_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                        <textarea name="delivery_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('delivery_address', $purchaseOrder->delivery_address) }}</textarea>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">ORDER DATE: <span class="text-red-400">*</span></label>
                        <input type="date" name="order_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('order_date', $purchaseOrder->order_date->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">EXPECTED DELIVERY DATE:</label>
                        <input type="date" name="expected_delivery_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('Y-m-d') : '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms', $purchaseOrder->payment_terms) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LOCATION:</label>
                        <input type="text" name="location" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('location', $purchaseOrder->location) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">HOUSE:</label>
                        <input type="text" name="house" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('house', $purchaseOrder->house) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PR#:</label>
                        <input type="text" name="pr_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('pr_no', $purchaseOrder->pr_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LC PRICE:</label>
                        <input type="number" step="0.01" name="lc_price" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('lc_price', $purchaseOrder->lc_price) }}">
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
                            @foreach($purchaseOrder->items as $index => $item)
                            <tr>
                                <td class="border border-gray-700 px-2 py-2 text-center text-gray-300">{{ $index + 1 }}</td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->item_code }}"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" value="{{ $item->qty }}" required></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->uom }}" required></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="text" name="items[{{ $index }}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->description }}" required></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price" value="{{ $item->unit_price }}"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][tax]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-tax" value="{{ $item->tax }}"></td>
                                <td class="border border-gray-700 px-2 py-2"><input type="number" step="0.01" name="items[{{ $index }}][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" value="{{ $item->total }}" readonly></td>
                                <td class="border border-gray-700 px-2 py-2 text-center">
                                    <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">REMARKS:</label>
                <textarea name="remarks" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter remarks...">{{ old('remarks', $purchaseOrder->remarks) }}</textarea>
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
                <a href="{{ route('purchase_orders.show', $purchaseOrder->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-save mr-1"></i> Update Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let rowCount = {{ $purchaseOrder->items->count() }};

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
