@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <h1 class="text-2xl font-bold mb-6 border-b border-gray-700 pb-2">
        ✏️ Edit Sales Order
    </h1>

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

            <div>
                <label class="block text-sm mb-1 text-gray-300">Delivery Type <span class="text-red-500">*</span></label>
                <select id="delivery_type" name="delivery_type" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2">
                    <option value="Partial" {{ old('delivery_type', $salesOrder->delivery_type) == 'Partial' ? 'selected' : '' }}>Partial</option>
                    <option value="Full" {{ old('delivery_type', $salesOrder->delivery_type) == 'Full' ? 'selected' : '' }}>Full</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">💡 Delivery type is automatically set based on active batches</p>
            </div>
        </div>

        <!-- Add Items Button (Show if Partial and Approved) -->
        @if($salesOrder->status === 'Approved')
            <div id="add-items-section" class="mb-6" style="display: none;">
                <a href="{{ route('sales_orders.addItemsForm', $salesOrder->id) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded inline-block transition">
                    ➕ Add More Items (New Batch)
                </a>
                <p class="text-sm text-gray-400 mt-2">
                    💡 Adding items will create a new delivery batch
                </p>
            </div>
        @endif

        <!-- Order Items by Batch -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                🧾 Order Items (By Delivery Batch)
            </h2>

            @php
                $itemsByBatch = $salesOrder->items->groupBy('delivery_batch');
            @endphp

            <div id="batches-container" class="space-y-6">
                @foreach($itemsByBatch as $batchName => $batchItems)
                <div class="batch-section border-2 border-gray-700 rounded-lg p-4 bg-gray-900/60" 
                     data-batch="{{ $batchName }}" 
                     data-batch-status="{{ $batchItems->first()->batch_status ?? 'Active' }}">
                    <!-- Batch Header -->
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-semibold text-blue-400">📦 {{ $batchName }}</span>
                            <span class="text-sm text-gray-400">
                                ({{ $batchItems->first()->request_delivery_date ?? 'No date' }})
                            </span>
                        </div>
                        
                        <!-- Batch Status Selector - ALWAYS VISIBLE -->
                        <div class="batch-status-container flex items-center gap-2">
                            <label class="text-sm text-gray-400">Batch Status:</label>
                            <select class="batch-status-select bg-gray-800 border border-gray-700 rounded px-3 py-1 text-sm">
                                <option value="Active" {{ ($batchItems->first()->batch_status ?? 'Active') == 'Active' ? 'selected' : '' }}>✅ Active</option>
                                <option value="Cancelled" {{ ($batchItems->first()->batch_status ?? 'Active') == 'Cancelled' ? 'selected' : '' }}>❌ Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <!-- Items in this batch -->
                    <div class="space-y-3">
                        @foreach($batchItems as $index => $item)
                        <div class="border border-gray-700 bg-gray-900/80 rounded-lg p-4 relative hover:border-blue-500 transition item-row">
                            <input type="hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][item_id]" value="{{ $item->item_id }}">
                            <input type="hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][delivery_batch]" value="{{ $batchName }}">
                            <input type="hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][request_delivery_date]" value="{{ $item->request_delivery_date }}">
                            <input type="hidden" class="batch-status-hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][batch_status]" value="{{ $item->batch_status ?? 'Active' }}">

                            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Searchable Item Description -->
                                <div class="lg:col-span-2">
                                    <label class="block text-xs text-gray-400 mb-1">Item Description</label>
                                    <div class="relative item-search-container">
                                        <input 
                                            type="text" 
                                            class="item-search-input w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm"
                                            value="{{ $item->item_description }}"
                                            placeholder="Search item..."
                                            autocomplete="off">
                                        <div class="item-dropdown absolute z-[9999] w-full bg-gray-800 border-2 border-gray-600 rounded-lg mt-1 shadow-2xl hidden max-h-60 overflow-y-auto">
                                            @foreach($items as $availableItem)
                                            <div class="item-option px-3 py-2 hover:bg-blue-600 cursor-pointer text-white border-b border-gray-700"
                                                data-id="{{ $availableItem->id }}"
                                                data-description="{{ $availableItem->item_description }}"
                                                data-code="{{ $availableItem->item_code }}"
                                                data-category="{{ $availableItem->item_category }}"
                                                data-brand="{{ $availableItem->brand }}"
                                                data-price="{{ $availableItem->unit_price }}"
                                                data-search="{{ strtolower($availableItem->item_description . ' ' . $availableItem->item_code) }}">
                                                <div class="font-semibold text-sm">{{ $availableItem->item_description }}</div>
                                                <div class="text-xs text-gray-400">{{ $availableItem->item_code }} - {{ $availableItem->brand }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <input type="hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][item_description]" class="item-description-hidden" value="{{ $item->item_description }}">
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Item Code</label>
                                    <input type="text" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][item_code]" 
                                        class="item-code-field w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm" 
                                        value="{{ $item->item_code }}" readonly>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Category</label>
                                    <input type="text" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][item_category]" 
                                        class="item-category-field w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm" 
                                        value="{{ $item->item_category }}" readonly>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Brand</label>
                                    <input type="text" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][brand]" 
                                        class="item-brand-field w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm" 
                                        value="{{ $item->brand }}" readonly>
                                </div>

                                <div class="flex gap-2 items-end lg:col-span-3">
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-400 mb-1">Quantity</label>
                                        <input type="number" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][quantity]" 
                                            value="{{ $item->quantity }}" 
                                            class="item-quantity w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right"
                                            step="0.01">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-400 mb-1">Unit Price</label>
                                        <input type="number" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][unit_price]" 
                                            value="{{ $item->unit_price }}" 
                                            step="0.01"
                                            class="item-price w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-400 mb-1">Total</label>
                                        <input type="number" 
                                            value="{{ $item->total_amount }}" 
                                            readonly
                                            class="item-total w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-gray-400 text-right">
                                    </div>
                                </div>

                                <div class="lg:col-span-4">
                                    <label class="block text-xs text-gray-400 mb-1">Note</label>
                                    <textarea name="items[{{ $loop->parent->index }}_{{ $loop->index }}][note]" 
                                        rows="2"
                                        class="w-full bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm">{{ $item->note ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        @endforeach
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
    // Calculate totals for each item
    const batchSections = document.querySelectorAll('.batch-section');
    
    batchSections.forEach(section => {
        const items = section.querySelectorAll('.item-row');
        
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

    // ✅ Handle batch status changes
    const batchStatusSelects = document.querySelectorAll('.batch-status-select');
    batchStatusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const batchSection = this.closest('.batch-section');
            const hiddenInputs = batchSection.querySelectorAll('.batch-status-hidden');
            
            // Update all hidden inputs in this batch
            hiddenInputs.forEach(input => {
                input.value = this.value;
            });
            
            // Update data attribute
            batchSection.setAttribute('data-batch-status', this.value);
            
            // Visual feedback
            if (this.value === 'Cancelled') {
                batchSection.classList.add('opacity-50', 'border-red-500');
                batchSection.classList.remove('border-gray-700');
            } else {
                batchSection.classList.remove('opacity-50', 'border-red-500');
                batchSection.classList.add('border-gray-700');
            }
            
            // Update delivery type based on active batches
            updateDeliveryType();
        });
        
        // Set initial visual state
        const batchSection = select.closest('.batch-section');
        if (select.value === 'Cancelled') {
            batchSection.classList.add('opacity-50', 'border-red-500');
            batchSection.classList.remove('border-gray-700');
        }
    });

    // ✅ Function to update delivery type based on active batches
    function updateDeliveryType() {
        const allBatches = document.querySelectorAll('.batch-section');
        const activeBatches = Array.from(allBatches).filter(batch => {
            return batch.getAttribute('data-batch-status') !== 'Cancelled';
        });
        
        const deliveryTypeSelect = document.getElementById('delivery_type');
        
        if (activeBatches.length === 0) {
            // No active batches - shouldn't happen, but handle it
            deliveryTypeSelect.value = 'Full';
        } else if (activeBatches.length === 1) {
            // Only one active batch - should be Full
            deliveryTypeSelect.value = 'Full';
        } else {
            // Multiple active batches - should be Partial
            deliveryTypeSelect.value = 'Partial';
        }
        
        // Update add items button visibility
        toggleAddItemsButton();
    }

    // ✅ Toggle add items button based on delivery type
    const deliveryTypeSelect = document.getElementById('delivery_type');
    const addItemsSection = document.getElementById('add-items-section');
    
    function toggleAddItemsButton() {
        const isApproved = '{{ $salesOrder->status }}' === 'Approved';
        const deliveryType = deliveryTypeSelect.value;
        
        // Show "Add Items" button only if Approved AND Partial
        if (isApproved && deliveryType === 'Partial') {
            addItemsSection.style.display = 'block';
        } else {
            addItemsSection.style.display = 'none';
        }
    }
    
    // Initial check on page load
    updateDeliveryType();
    
    // Allow manual override of delivery type
    deliveryTypeSelect.addEventListener('change', toggleAddItemsButton);

    // Searchable item dropdown functionality
    const searchContainers = document.querySelectorAll('.item-search-container');
    
    searchContainers.forEach(container => {
        const searchInput = container.querySelector('.item-search-input');
        const dropdown = container.querySelector('.item-dropdown');
        const parentItem = container.closest('.item-row');
        
        searchInput.addEventListener('focus', () => {
            dropdown.classList.remove('hidden');
        });
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const options = dropdown.querySelectorAll('.item-option');
            
            options.forEach(option => {
                const searchText = option.getAttribute('data-search');
                option.style.display = searchText.includes(searchTerm) ? 'block' : 'none';
            });
            
            dropdown.classList.remove('hidden');
        });
        
        const options = dropdown.querySelectorAll('.item-option');
        options.forEach(option => {
            option.addEventListener('click', function() {
                const description = this.getAttribute('data-description');
                const code = this.getAttribute('data-code');
                const category = this.getAttribute('data-category');
                const brand = this.getAttribute('data-brand');
                const price = this.getAttribute('data-price');
                const id = this.getAttribute('data-id');
                
                searchInput.value = description;
                parentItem.querySelector('.item-description-hidden').value = description;
                parentItem.querySelector('.item-code-field').value = code;
                parentItem.querySelector('.item-category-field').value = category;
                parentItem.querySelector('.item-brand-field').value = brand;
                parentItem.querySelector('.item-price').value = price;
                parentItem.querySelector('input[name*="[item_id]"]').value = id;
                
                // Recalculate total
                const qty = parseFloat(parentItem.querySelector('.item-quantity').value) || 0;
                parentItem.querySelector('.item-total').value = (qty * parseFloat(price)).toFixed(2);
                
                dropdown.classList.add('hidden');
            });
        });
        
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    });
});
</script>

@endsection