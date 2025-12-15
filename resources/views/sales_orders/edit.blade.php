@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <h1 class="text-2xl font-bold mb-6 border-b border-gray-700 pb-2">
        ✏️ Edit Sales Order
    </h1>

    @php
        // ✅ NEW LOGIC: Check if delivery exists and is delivered
        $hasDelivery = $salesOrder->deliveries !== null;
        $isDelivered = false;
        $needsApproval = false;
        $user = auth()->user();
        
        if ($hasDelivery) {
            $delivery = $salesOrder->deliveries;
            // Check if delivery is marked as Delivered
            if ($delivery->status === 'Delivered') {
                $isDelivered = true;
                
                // If delivered AND user is CSR AND not CC-approved for editing
                if ($user->canEditAfterCCApproval() && !$salesOrder->isEditApprovedByCC()) {
                    $needsApproval = true;
                }
            }
        }
    @endphp

    {{-- ✅ Show warning if order is delivered and CSR needs approval --}}
    @if($isDelivered && $needsApproval)
        <div class="bg-yellow-900/40 border-2 border-yellow-600 text-yellow-300 p-4 rounded-lg mb-6 flex items-center gap-3">
            <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <strong class="text-lg">⚠️ Editing Not Permitted</strong>
                <p class="text-sm mt-1">This Sales Order has been delivered and requires CC Approver permission to edit. Please request approval from CC Approver first.</p>
            </div>
        </div>
    @endif

    {{-- ✅ Show info if order is delivered (for CC_Approver or approved CSR) --}}
    @if($isDelivered && !$needsApproval)
        <div class="bg-blue-900/40 border-2 border-blue-600 text-blue-300 p-4 rounded-lg mb-6 flex items-center gap-3">
            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <strong class="text-lg">📦 Delivered Order</strong>
                <p class="text-sm mt-1">This Sales Order has been delivered. You have permission to edit it.</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ route('sales_orders.update', $salesOrder->id) }}" method="POST" enctype="multipart/form-data" id="editForm">
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
                <input type="text" name="po_number" id="po_number"
                    value="{{ old('po_number', $salesOrder->po_number) }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2"
                    placeholder="Enter PO Number (optional)">
            </div>

            <div>
                <label class="block text-sm mb-1 text-gray-300">Request Delivery Date *</label>
                <input type="date" name="request_delivery_date" 
                    value="{{ old('request_delivery_date', $salesOrder->request_delivery_date) }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm mb-1 text-gray-300">Sales Representative</label>
                <input type="text" name="sales_rep"
                    value="{{ old('sales_rep', $salesOrder->sales_rep) }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-400" readonly>
            </div>

            {{-- ✅ PO Image Upload Section --}}
            <div class="col-span-2" id="po_image_container">
                <div class="bg-gray-800/60 border border-gray-700 rounded-lg p-4">
                    <label class="block text-sm mb-2 text-gray-300 font-semibold">
                        📸 PO Proof / Order Evidence
                    </label>

                    {{-- Current PO Image Display --}}
                    @if($salesOrder->po_image)
                        <div class="mb-3 bg-gray-900/50 border border-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-400 mb-2">Current PO Proof:</p>
                            @if(Str::endsWith($salesOrder->po_image, '.pdf'))
                                <a href="{{ asset('po_images/' . $salesOrder->po_image) }}" 
                                   target="_blank" 
                                   class="text-blue-400 hover:text-blue-300 flex items-center gap-2 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="underline">View Current PO Document (PDF)</span>
                                </a>
                            @else
                                <a href="{{ asset('po_images/' . $salesOrder->po_image) }}" 
                                   target="_blank"
                                   class="block">
                                    <img src="{{ asset('po_images/' . $salesOrder->po_image) }}" 
                                        alt="Current PO Proof" 
                                        class="max-w-xs rounded border border-gray-600 hover:border-blue-500 transition-all cursor-pointer">
                                </a>
                                <p class="text-xs text-gray-400 mt-2">Click to view full size</p>
                            @endif
                        </div>
                    @endif

                    {{-- Upload New PO Image --}}
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">
                            @if($salesOrder->po_image)
                                Upload New PO Proof (Optional - will replace current)
                            @else
                                Upload PO Proof (Optional)
                            @endif
                        </label>
                        <input type="file" id="po_image" name="po_image" accept="image/*,application/pdf"
                            class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 
                                file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 
                                file:bg-blue-600 file:text-white hover:file:bg-blue-500">
                        <p class="text-xs text-gray-400 mt-2">
                            Accepts JPG, PNG, or PDF (max 4MB). Leave empty to keep current proof.
                        </p>
                        
                        {{-- Preview for new upload --}}
                        <div id="po_image_preview" class="mt-3 hidden">
                            <p class="text-xs text-gray-400 mb-2">New upload preview:</p>
                            <img id="po_image_preview_img" src="" alt="New PO Preview" 
                                class="max-w-xs rounded border border-gray-700">
                        </div>
                    </div>

                    {{-- Hidden input to track if we should remove the image --}}
                    @if($salesOrder->po_image)
                        <div class="mt-3">
                            <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer hover:text-red-400 transition-colors">
                                <input type="checkbox" name="remove_po_image" value="1" class="rounded bg-gray-700 border-gray-600">
                                <span>Remove current PO proof (check this to delete the current file)</span>
                            </label>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Order Items</h3>
            <button type="button" onclick="addNewItem()" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                ➕ Add Item
            </button>
        </div>

        <div id="items-container" class="space-y-4 mb-6">
            @foreach($salesOrder->items as $item)
                <div class="item-row border border-gray-700 rounded-lg p-4 bg-gray-800/50" data-index="{{ $loop->index }}">
                    <div class="flex justify-between items-start mb-4">
                        <h4 class="text-sm font-semibold text-gray-300">Item #{{ $loop->index + 1 }}</h4>
                        <button type="button" onclick="removeItem(this)" 
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition">
                            🗑️ Remove
                        </button>
                    </div>

                    <!-- Item Selection -->
                    <div class="mb-4">
                        <label class="block text-sm mb-1 text-gray-300">Select Item *</label>
                        <select name="items[{{ $loop->index }}][item_id]" 
                            class="item-select select2-items w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" 
                            onchange="updateItemDetails(this)"
                            data-placeholder="Search for an item..."
                            required>
                            <option value=""></option>
                            @foreach($items as $masterItem)
                                <option value="{{ $masterItem->id }}" 
                                    data-code="{{ $masterItem->item_code }}"
                                    data-description="{{ $masterItem->item_description }}"
                                    data-category="{{ $masterItem->item_category }}"
                                    data-brand="{{ $masterItem->brand }}"
                                    data-unit="{{ $masterItem->unit }}"
                                    {{ $item->item_id == $masterItem->id ? 'selected' : '' }}>
                                    {{ $masterItem->item_code }} - {{ $masterItem->item_description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Item Code</label>
                            <input type="text" name="items[{{ $loop->index }}][item_code]"
                                value="{{ old('items.' . $loop->index . '.item_code', $item->item_code) }}" 
                                class="item-code w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Item Description</label>
                            <input type="text" name="items[{{ $loop->index }}][item_description]"
                                value="{{ old('items.' . $loop->index . '.item_description', $item->item_description) }}" 
                                class="item-description w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Category</label>
                            <input type="text" name="items[{{ $loop->index }}][item_category]"
                                value="{{ old('items.' . $loop->index . '.item_category', $item->item_category) }}" 
                                class="item-category w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Brand</label>
                            <input type="text" name="items[{{ $loop->index }}][brand]"
                                value="{{ old('items.' . $loop->index . '.brand', $item->brand) }}" 
                                class="item-brand w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Quantity *</label>
                            <input type="number" name="items[{{ $loop->index }}][quantity]" 
                                   value="{{ old('items.' . $loop->index . '.quantity', $item->quantity) }}" 
                                   step="any" 
                                   class="item-quantity w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" 
                                   oninput="calculateTotal(this)" required>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Unit</label>
                            <input type="text" name="items[{{ $loop->index }}][unit]"
                                   value="{{ old('items.' . $loop->index . '.unit', $item->unit) }}" 
                                   class="item-unit w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Unit Price *</label>
                            <input type="number" name="items[{{ $loop->index }}][unit_price]" 
                                   value="{{ old('items.' . $loop->index . '.unit_price', $item->unit_price) }}" 
                                   step="any"
                                   class="item-price w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2"
                                   oninput="calculateTotal(this)" required>
                        </div>

                        <div>
                            <label class="block text-sm mb-1 text-gray-300">Total Amount</label>
                            <input type="text" name="items[{{ $loop->index }}][total_amount]" 
                                   value="{{ old('items.' . $loop->index . '.total_amount', $item->total_amount) }}" 
                                   class="item-total w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-gray-400" readonly>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-300">Note</label>
                        <textarea name="items[{{ $loop->index }}][note]" 
                                  rows="2"
                                  class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2"
                                  placeholder="Add any notes for this item...">{{ old('items.' . $loop->index . '.note', $item->note) }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Submit Button -->
        <div class="flex items-center gap-4">
            <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold transition">
                💾 Update Sales Order
            </button>
            <a href="{{ route('sales_orders.show', $salesOrder->id) }}" 
               class="text-gray-400 hover:text-gray-200">Cancel</a>
        </div>
    </form>
</div>

{{-- Load jQuery and Select2 --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

{{-- Select2 Dark Theme CSS --}}
<style>
/* Select2 Dark Theme Customization */
.select2-container--default .select2-selection--single {
    background-color: #374151 !important;
    border: 1px solid #4b5563 !important;
    border-radius: 0.5rem !important;
    height: 42px !important;
    padding: 0.5rem !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: white !important;
    line-height: 26px !important;
    padding-left: 0 !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #9ca3af !important;
}

.select2-dropdown {
    background-color: #1f2937 !important;
    border: 1px solid #4b5563 !important;
    border-radius: 0.5rem !important;
}

.select2-container--default .select2-search--dropdown .select2-search__field {
    background-color: #374151 !important;
    border: 1px solid #4b5563 !important;
    color: white !important;
    border-radius: 0.375rem !important;
    padding: 0.5rem !important;
}

.select2-container--default .select2-search--dropdown .select2-search__field::placeholder {
    color: #9ca3af !important;
}

.select2-container--default .select2-results__option {
    background-color: #1f2937 !important;
    color: white !important;
    padding: 0.75rem !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3b82f6 !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #2563eb !important;
}

.select2-container--default .select2-results__option--disabled {
    color: #6b7280 !important;
}

.select2-container {
    width: 100% !important;
}

/* Loading state */
.select2-container--default .select2-results__option--loading {
    color: #9ca3af !important;
}
</style>

<script>
let itemIndex = {{ count($salesOrder->items) }};
const availableItems = @json($items);

// Initialize Select2 when document is ready
$(document).ready(function() {
    initializeSelect2();
});

// Initialize Select2 on all item selects
function initializeSelect2() {
    $('.select2-items').select2({
        placeholder: 'Search for an item...',
        allowClear: true,
        width: '100%',
        matcher: customMatcher
    });
}

// Custom matcher for better search
function customMatcher(params, data) {
    if ($.trim(params.term) === '') {
        return data;
    }
    
    if (typeof data.text === 'undefined') {
        return null;
    }
    
    var text = data.text.toLowerCase();
    var term = params.term.toLowerCase();
    
    if (text.indexOf(term) > -1) {
        return data;
    }
    
    return null;
}

// PO Image Preview
document.getElementById('po_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('po_image_preview');
    const previewImg = document.getElementById('po_image_preview_img');
    
    if (file) {
        if (file.size > 4 * 1024 * 1024) {
            alert('File size must not exceed 4MB.');
            e.target.value = '';
            previewContainer.classList.add('hidden');
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload a JPG, PNG, or PDF file.');
            e.target.value = '';
            previewContainer.classList.add('hidden');
            return;
        }
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.classList.add('hidden');
        }
    } else {
        previewContainer.classList.add('hidden');
    }
});

// Add new item row
function addNewItem() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row border border-gray-700 rounded-lg p-4 bg-gray-800/50';
    newRow.dataset.index = itemIndex;
    
    let optionsHtml = '<option value=""></option>';
    availableItems.forEach(item => {
        optionsHtml += `<option value="${item.id}" 
            data-code="${item.item_code || ''}"
            data-description="${item.item_description || ''}"
            data-category="${item.item_category || ''}"
            data-brand="${item.brand || ''}"
            data-unit="${item.unit || 'Kgs'}">
            ${item.item_code} - ${item.item_description}
        </option>`;
    });
    
    newRow.innerHTML = `
        <div class="flex justify-between items-start mb-4">
            <h4 class="text-sm font-semibold text-gray-300">Item #${itemIndex + 1}</h4>
            <button type="button" onclick="removeItem(this)" 
                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition">
                🗑️ Remove
            </button>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1 text-gray-300">Select Item *</label>
            <select name="items[${itemIndex}][item_id]" 
                class="item-select select2-items w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" 
                onchange="updateItemDetails(this)"
                data-placeholder="Search for an item..."
                required>
                ${optionsHtml}
            </select>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm mb-1 text-gray-300">Item Code</label>
                <input type="text" name="items[${itemIndex}][item_code]" 
                    class="item-code w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Item Description</label>
                <input type="text" name="items[${itemIndex}][item_description]" 
                    class="item-description w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Category</label>
                <input type="text" name="items[${itemIndex}][item_category]" 
                    class="item-category w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Brand</label>
                <input type="text" name="items[${itemIndex}][brand]" 
                    class="item-brand w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm mb-1 text-gray-300">Quantity *</label>
                <input type="number" name="items[${itemIndex}][quantity]" step="any" 
                    class="item-quantity w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" 
                    oninput="calculateTotal(this)" required>
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Unit</label>
                <input type="text" name="items[${itemIndex}][unit]" 
                    class="item-unit w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2" readonly>
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Unit Price *</label>
                <input type="number" name="items[${itemIndex}][unit_price]" step="any" 
                    class="item-price w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2"
                    oninput="calculateTotal(this)" required>
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Total Amount</label>
                <input type="text" name="items[${itemIndex}][total_amount]" 
                    class="item-total w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-gray-400" readonly>
            </div>
        </div>

        <div>
            <label class="block text-sm mb-1 text-gray-300">Note</label>
            <textarea name="items[${itemIndex}][note]" rows="2"
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2"
                placeholder="Add any notes for this item..."></textarea>
        </div>
    `;
    
    container.appendChild(newRow);
    
    $(newRow).find('.select2-items').select2({
        placeholder: 'Search for an item...',
        allowClear: true,
        width: '100%',
        matcher: customMatcher
    });
    
    itemIndex++;
}

// Remove item row
function removeItem(button) {
    const container = document.getElementById('items-container');
    if (container.children.length <= 1) {
        alert('You must have at least one item in the order.');
        return;
    }
    
    const selectElement = $(button).closest('.item-row').find('.select2-items');
    if (selectElement.data('select2')) {
        selectElement.select2('destroy');
    }
    
    button.closest('.item-row').remove();
    renumberItems();
}

// Renumber items after removal
function renumberItems() {
    const items = document.querySelectorAll('.item-row');
    items.forEach((item, index) => {
        item.querySelector('h4').textContent = `Item #${index + 1}`;
    });
}

// Update item details when selection changes
function updateItemDetails(selectElement) {
    const row = selectElement.closest('.item-row');
    const option = selectElement.options[selectElement.selectedIndex];
    
    if (option.value) {
        row.querySelector('.item-code').value = option.dataset.code || '';
        row.querySelector('.item-description').value = option.dataset.description || '';
        row.querySelector('.item-category').value = option.dataset.category || '';
        row.querySelector('.item-brand').value = option.dataset.brand || '';
        row.querySelector('.item-unit').value = option.dataset.unit || 'Kgs';
    } else {
        row.querySelector('.item-code').value = '';
        row.querySelector('.item-description').value = '';
        row.querySelector('.item-category').value = '';
        row.querySelector('.item-brand').value = '';
        row.querySelector('.item-unit').value = '';
    }
}

// Calculate total amount
function calculateTotal(input) {
    const row = input.closest('.item-row');
    const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-total').value = (qty * price).toFixed(2);
}
</script>

@endsection