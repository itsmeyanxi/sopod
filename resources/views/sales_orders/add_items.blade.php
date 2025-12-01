@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">
            ➕ Add Items to {{ $salesOrder->sales_order_number }}
        </h1>
        <a href="{{ route('sales_orders.show', $salesOrder->id) }}" 
           class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-sm">
            ← Back
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <!-- SO Info Summary -->
    <div class="bg-gray-800 rounded-lg p-4 mb-6 border border-gray-700">
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-400">Customer:</span>
                <span class="font-semibold ml-2">{{ $salesOrder->customer->customer_name }}</span>
            </div>
            <div>
                <span class="text-gray-400">PO Number:</span>
                <span class="font-semibold ml-2">{{ $salesOrder->po_number }}</span>
            </div>
            <div>
                <span class="text-gray-400">Current Total:</span>
                <span class="font-semibold ml-2 text-green-400">₱{{ number_format($salesOrder->total_amount, 2) }}</span>
            </div>
        </div>
    </div>

    <form action="{{ route('sales_orders.storeAdditionalItems', $salesOrder->id) }}" method="POST" id="addItemsForm">
        @csrf

        <!-- Items Container -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">🧾 New Items</h2>
                <button type="button" onclick="addItemRow()" 
                    class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm">
                    ➕ Add Item
                </button>
            </div>

            <div id="items-container" class="space-y-4">
                <!-- Items will be added here via JavaScript -->
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" 
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold">
                💾 Add Items to Sales Order
            </button>
            <a href="{{ route('sales_orders.show', $salesOrder->id) }}" 
               class="text-gray-400 hover:text-gray-200">Cancel</a>
        </div>
    </form>
</div>

<script>
let itemIndex = 0;
const approvedItems = @json($items);

function addItemRow() {
    const container = document.getElementById('items-container');
    const index = itemIndex++;
    
    const itemRow = `
        <div class="border border-gray-700 bg-gray-900/60 rounded-lg p-4 relative hover:border-blue-500 transition" data-index="${index}">
            <button type="button" onclick="removeItem(this)" 
                class="absolute top-2 right-2 text-red-500 hover:text-red-400 text-xl">
                ✕
            </button>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-xs text-gray-400 mb-1">Select Item</label>
                    <select name="items[${index}][item_id]" required
                        onchange="populateItemData(this, ${index})"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm">
                        <option value="">-- Select Item --</option>
                        ${approvedItems.map(item => `
                            <option value="${item.id}" 
                                data-code="${item.item_code || ''}"
                                data-description="${item.item_description || ''}"
                                data-brand="${item.brand || ''}"
                                data-category="${item.item_category || ''}"
                                data-unit="${item.unit || 'Kgs'}">
                                ${item.item_code} - ${item.item_description}
                            </option>
                        `).join('')}
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Delivery Date</label>
                    <input type="date" name="items[${index}][request_delivery_date]" required
                        class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm">
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Quantity</label>
                    <input type="number" name="items[${index}][quantity]" required step="0.01"
                        onchange="calculateTotal(${index})"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right">
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Unit Price</label>
                    <input type="number" name="items[${index}][price]" required step="0.01"
                        onchange="calculateTotal(${index})"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right">
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Total</label>
                    <input type="number" readonly
                        class="item-total w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-gray-400 text-right">
                </div>
            </div>

            <input type="hidden" name="items[${index}][item_code]">
            <input type="hidden" name="items[${index}][item_description]">
            <input type="hidden" name="items[${index}][brand]">
            <input type="hidden" name="items[${index}][item_category]">
            <input type="hidden" name="items[${index}][unit]">
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemRow);
}

function populateItemData(select, index) {
    const option = select.options[select.selectedIndex];
    const row = select.closest('[data-index]');
    
    row.querySelector(`input[name="items[${index}][item_code]"]`).value = option.dataset.code || '';
    row.querySelector(`input[name="items[${index}][item_description]"]`).value = option.dataset.description || '';
    row.querySelector(`input[name="items[${index}][brand]"]`).value = option.dataset.brand || '';
    row.querySelector(`input[name="items[${index}][item_category]"]`).value = option.dataset.category || '';
    row.querySelector(`input[name="items[${index}][unit]"]`).value = option.dataset.unit || 'Kgs';
}

function calculateTotal(index) {
    const row = document.querySelector(`[data-index="${index}"]`);
    const qty = parseFloat(row.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
    const price = parseFloat(row.querySelector(`input[name="items[${index}][price]"]`).value) || 0;
    const total = qty * price;
    
    row.querySelector('.item-total').value = total.toFixed(2);
}

function removeItem(btn) {
    btn.closest('[data-index]').remove();
}

// Add first item on load
document.addEventListener('DOMContentLoaded', () => {
    addItemRow();
});
</script>
@endsection