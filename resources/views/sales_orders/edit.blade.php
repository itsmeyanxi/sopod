@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <h1 class="text-2xl font-bold mb-6 border-b border-gray-700 pb-2">
        ✏️ Edit Sales Order
    </h1>
    {{-- Add this at the top of sales_orders/show.blade.php and edit.blade.php --}}
{{-- Right after the page title --}}

@if($salesOrder->is_closed)
    <div class="bg-green-900/40 border-2 border-green-600 text-green-300 p-4 rounded-lg mb-6 flex items-center gap-3">
        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <strong class="text-lg">✅ Sales Order Closed</strong>
            <p class="text-sm mt-1">All items have been fully delivered. This Sales Order can no longer be edited.</p>
        </div>
    </div>
@endif

    @if(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ route('sales_orders.update', $salesOrder->id) }}" method="POST" id="editForm">
        @csrf
        @method('PUT')

        <!-- General Info -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm mb-1 text-gray-300">Customer</label>
                <input type="text" 
                    value="{{ $salesOrder->customer->customer_name }}" 
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
                <input type="hidden" name="customer_id" value="{{ $salesOrder->customer_id }}">
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
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2" readonly>
            </div>

            <div>
                <label class="block text-sm mb-1 text-gray-300">Sales Representative</label>
                <input type="text" name="sales_rep"
                    value="{{ old('sales_rep', $salesOrder->sales_rep) }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
            </div>
        </div>

        <!-- Items Table -->
        <h3 class="text-lg font-semibold mt-6 mb-4">Order Items</h3>
        <div class="space-y-4">
            @foreach($salesOrder->items as $item)
                <div class="item-row border-b border-gray-700 pb-4 mb-4">
                    <!-- CRITICAL FIX: Add item_id hidden field -->
                    <input type="hidden" name="items[{{ $loop->index }}][item_id]" value="{{ $item->item_id }}">

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 mb-4">
                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Item Description</label>
                            <input type="text" name="items[{{ $loop->index }}][item_description]"
                                value="{{ old('items.' . $loop->index . '.item_description', $item->item_description) }}" 
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Item Code</label>
                            <input type="text" name="items[{{ $loop->index }}][item_code]"
                                value="{{ old('items.' . $loop->index . '.item_code', $item->item_code) }}" 
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Category</label>
                            <input type="text" name="items[{{ $loop->index }}][item_category]"
                                value="{{ old('items.' . $loop->index . '.item_category', $item->item_category) }}" 
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Brand</label>
                            <input type="text" name="items[{{ $loop->index }}][brand]"
                                value="{{ old('items.' . $loop->index . '.brand', $item->brand) }}" 
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 mb-4">
                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Quantity</label>
                            <input type="number" name="items[{{ $loop->index }}][quantity]" 
                                   value="{{ old('items.' . $loop->index . '.quantity', $item->quantity) }}" 
                                   step="any" class="item-quantity w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Unit</label>
                            <input type="text" name="items[{{ $loop->index }}][unit]"
                                   value="{{ old('items.' . $loop->index . '.unit', $item->unit) }}" 
                                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Unit Price</label>
                            <input type="number" name="items[{{ $loop->index }}][unit_price]" 
                                   value="{{ old('items.' . $loop->index . '.unit_price', $item->unit_price) }}" 
                                   step="any"
                                   class="item-price w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Total Amount</label>
                            <input type="text" name="items[{{ $loop->index }}][total_amount]" 
                                   value="{{ old('items.' . $loop->index . '.total_amount', $item->total_amount) }}" 
                                   class="item-total w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
                        </div>
                    </div>

                    <!-- Note Field -->
                    <div class="mt-4">
                        <label class="block text-sm mb-1 text-gray-300">Note</label>
                        <textarea name="items[{{ $loop->index }}][note]" 
                                  rows="2"
                                  class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2"
                                  placeholder="Add any notes for this item...">{{ old('items.' . $loop->index . '.note', $item->note) }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Submit Button -->
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
    // Recalculate total when quantity or price changes
    const items = document.querySelectorAll('.item-row');
    items.forEach(item => {
        const qtyInput = item.querySelector('.item-quantity');
        const priceInput = item.querySelector('.item-price');
        const totalInput = item.querySelector('.item-total');

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