@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <h1 class="text-2xl font-bold mb-6 border-b border-gray-700 pb-2">
        ✏️ Edit Sales Order
    </h1>

    <!-- Flash messages -->
    @if(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <form action="{{ route('sales_orders.update', $salesOrder->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- General Info -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm mb-1 text-gray-300">Customer</label>
                <select name="customer_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2">
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" 
                            {{ $salesOrder->customer_id == $customer->id ? 'selected' : '' }}>
                            {{ $customer->customer_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm mb-1 text-gray-300">PO Number</label>
                <input type="text" name="po_number" 
                    value="{{ old('po_number', $salesOrder->po_number) }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
            </div>

            <div>
                <label class="block text-sm mb-1 text-gray-300">Request Delivery Date</label>
                <input type="date" name="request_delivery_date" 
                    value="{{ old('request_delivery_date', $salesOrder->request_delivery_date) }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm mb-1 text-gray-300">Sales Representative</label>
                <input type="text" name="sales_rep" 
                    value="{{ old('sales_rep', $salesOrder->sales_rep) }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2">
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                🧾 Order Items
            </h2>

            <div id="items-container" class="space-y-4">
                @foreach ($salesOrder->items as $index => $item)
                <div class="border border-gray-700 bg-gray-900/60 rounded-lg p-4 relative hover:border-blue-500 transition">
                    <div class="absolute top-2 right-3 text-sm text-gray-500"></div>

                    <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->item_id }}">

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Item Code</label>
                            <input type="text" name="items[{{ $index }}][item_code]" 
                                value="{{ $item->item_code }}" readonly
                                class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-xs text-gray-400 mb-1">Description</label>
                            <input type="text" name="items[{{ $index }}][item_description]" 
                                value="{{ $item->item_description ?: ($item->item->item_description ?? '') }}" readonly
                                class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Brand</label>
                            <input type="text" name="items[{{ $index }}][brand]" 
                                value="{{ $item->brand }}" readonly
                                class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm">
                        </div>
                       
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Category</label>
                            <input type="text" name="items[{{ $index }}][item_category]" 
                                value="{{ $item->item_category ?: ($item->item->item_category ?? '') }}" readonly
                                class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm">
                        </div>

                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-400 mb-1">Quantity</label>
                                <input type="number" name="items[{{ $index }}][quantity]" 
                                    value="{{ $item->quantity }}" 
                                    class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-400 mb-1">Unit Price</label>
                                <input type="number" name="items[{{ $index }}][unit_price]" 
                                    value="{{ $item->unit_price }}" 
                                    step="0.01"
                                    class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-400 mb-1">Total</label>
                                <input type="number" 
                                    value="{{ $item->total_amount }}" 
                                    readonly
                                    class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-gray-400 text-right">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center">
            <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold transition">
                💾 Update Sales Order
            </button>

            <a href="{{ route('sales_orders.index') }}" 
               class="ml-4 text-gray-400 hover:text-gray-200">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('#items-container > div');

    rows.forEach(row => {
        const qtyInput = row.querySelector('input[name*="[quantity]"]');
        const priceInput = row.querySelector('input[name*="[unit_price]"]');
        const totalInput = row.querySelector('input[readonly]');

        function updateTotal() {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            totalInput.value = (qty * price).toFixed(2);
        }

        qtyInput.addEventListener('input', updateTotal);
        priceInput.addEventListener('input', updateTotal);
    });
});
</script>

@endsection
