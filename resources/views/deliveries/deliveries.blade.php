@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="max-w-6xl mx-auto mt-10 bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg border border-gray-800">
    <h2 class="text-2xl font-bold mb-6 text-white">🚚 Delivery Module</h2>

    {{-- 🔒 View Only Notice --}}
    @if(!\App\Helpers\RoleHelper::canManageDeliveries())
    <div class="bg-blue-900/40 border border-blue-700 text-blue-300 p-4 rounded-lg mb-6">
        <strong>View Only Mode</strong>
        <p>You can view delivery information but cannot make changes. Only Delivery, Admin, or IT roles can modify deliveries.</p>
    </div>
    @endif

    <!-- 🔍 Search by Sales Order Number -->
    <div class="mb-6 bg-gray-800/80 p-4 rounded-lg border border-gray-700">
        <label class="block text-gray-300 font-medium mb-2">Search Sales Order Number</label>
        <div class="flex gap-2">
            <input type="text" id="so_search" placeholder="Enter SO Number (e.g. SO-2025-001)" 
                   class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2 focus:ring-2 focus:ring-blue-500">
            <button id="search_btn" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md transition-all">
                Search
            </button>
        </div>
    </div>

    {{-- ✅ Batch Selector (Hidden by default) --}}
    <div id="batch_selector_container" class="mb-6 bg-yellow-900/20 border border-yellow-700 p-4 rounded-lg hidden">
        <label class="block text-yellow-300 font-medium mb-2">
            📦 Multiple Delivery Batches Found - Select One:
        </label>
        <select id="delivery_batch_select" 
                class="w-full bg-gray-900 border border-yellow-700 text-gray-200 rounded-md p-2 focus:ring-2 focus:ring-yellow-500">
            <option value="">-- Select Delivery Batch --</option>
        </select>
        <p class="text-xs text-gray-400 mt-2">This Sales Order has multiple delivery dates. Please select the batch you want to create/edit a delivery for.</p>
    </div>

    {{-- ✅ Partial Delivery Warning --}}
    <div id="partial_delivery_warning" class="mb-6 bg-orange-900/20 border border-orange-700 p-4 rounded-lg hidden">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-orange-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h4 class="text-orange-300 font-semibold mb-2">⚠️ Partial Delivery Detected</h4>
                <p class="text-sm text-orange-200 mb-2">You have reduced quantities below the original Sales Order amounts. This will be marked as a <strong>Partial Delivery</strong>.</p>
                <div id="partial_items_summary" class="text-xs text-orange-100 bg-orange-950/30 p-2 rounded mt-2"></div>
            </div>
        </div>
    </div>

    {{-- Hidden field to store selected batch --}}
    <input type="hidden" id="delivery_batch" name="delivery_batch">

    <!-- 🧾 Sales Order Information -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-1">Sales Order Information</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach([
                'sales_order_number' => 'Sales Order Number',
                'customer_code' => 'Customer Code',
                'customer_name' => 'Customer Name',
                'tin_no' => 'TIN',
                'branch' => 'Branch',
                'sales_representative' => 'Sales Representative',
                'sales_executive' => 'Sales Executive',
                'po_number' => 'PO Number',
                'request_delivery_date' => 'Request Delivery Date'
            ] as $id => $label)
                <div>
                    <label class="block text-gray-400 text-sm">{{ $label }}</label>
                    <input id="{{ $id }}" type="{{ $id === 'request_delivery_date' ? 'date' : 'text' }}" 
                           class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2" readonly>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 📦 Delivery Details -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-1">Delivery Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-400 text-sm">Approved By</label>
                <input id="approved_by" type="text" 
                       value="{{ auth()->user()->name }}" 
                       class="w-full bg-gray-800 border border-gray-700 text-gray-300 rounded-md p-2" readonly>
            </div>

            @foreach([
                'plate_no' => 'Plate No',
                'sales_invoice_no' => 'Sales Invoice No (Optional)',
                'dr_no' => 'DR No'
            ] as $id => $label)
                <div>
                    <label class="block text-gray-400 text-sm">{{ $label }}</label>
                    <input id="{{ $id }}" type="text"
                        class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2"
                        {{ \App\Helpers\RoleHelper::canManageDeliveries() ? '' : 'readonly' }}
                        placeholder="{{ $id === 'sales_invoice_no' ? 'Optional' : '' }}">
                </div>
            @endforeach

            {{-- ✅ NEW: Type of Delivery (Replaces Partial in Status) --}}
            <div>
                <label class="block text-gray-400 text-sm mb-1">Type of Delivery</label>
                <select id="delivery_type"
                        class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2"
                        {{ \App\Helpers\RoleHelper::canManageDeliveries() ? '' : 'disabled' }}>
                    <option value="Full">Full Delivery</option>
                    <option value="Partial">Partial Delivery</option>
                </select>
            </div>

            {{-- ✅ UPDATED: Status (Only Delivered or Cancelled) --}}
            <div>
                <label class="block text-gray-400 text-sm">Status</label>
                <select id="status"
                        class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2"
                        {{ \App\Helpers\RoleHelper::canManageDeliveries() ? '' : 'disabled' }}>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-400 text-sm">Attachment (Optional)</label>
                <input id="attachment" type="file" accept="image/*,application/pdf"
                       class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2 file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-500"
                       {{ \App\Helpers\RoleHelper::canManageDeliveries() ? '' : 'disabled' }}>
                <p class="text-xs text-gray-500 mt-1">Upload an image or PDF file (JPG, PNG, PDF)</p>
                <div id="current_attachment_container" class="mt-2 hidden">
                    <a id="current_attachment_link" href="#" target="_blank" class="text-blue-400 hover:text-blue-300 text-sm flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        <span id="current_attachment_name">View Current Attachment</span>
                    </a>
                </div>
            </div>

            <div class="col-span-2">
                <label class="block text-gray-400 text-sm">Additional Instructions</label>
                <textarea id="additional_instructions" readonly
                        class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2" rows="3"></textarea>
            </div>
        </div>
    </div>

    
<!-- 📋 Delivery Items Section - REDESIGNED -->
<div class="mb-8">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-white border-b border-gray-700 pb-1">Delivery Items</h3>
        @if(\App\Helpers\RoleHelper::canManageDeliveries())
        <button type="button" onclick="addDeliveryItem()" 
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Item
        </button>
        @endif
    </div>

    <div id="delivery-items-container" class="space-y-4"></div>
</div>

    @if(\App\Helpers\RoleHelper::canManageDeliveries())
        <div class="text-right mt-6">
            <button id="save_btn" class="bg-green-600 hover:bg-green-500 text-white px-5 py-2 rounded-md shadow-sm transition-all">
                💾 Save Delivery
            </button>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const canManageDeliveries = {!! \App\Helpers\RoleHelper::canManageDeliveries() ? 'true' : 'false' !!};
const searchUrl = "{{ route('deliveries.search') }}";
const storeUrl = "{{ route('deliveries.store') }}";
const baseUpdateUrl = "{{ url('/deliveries') }}";
const deliveriesIndexUrl = "{{ route('deliveries.index') }}";
let deliveryId = null;
let selectedBatch = null;

const attachmentContainer = document.getElementById("current_attachment_container");
const attachmentLink = document.getElementById("current_attachment_link");
const attachmentName = document.getElementById("current_attachment_name");

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

function calculateRemaining(card) {
    const originalQty = parseFloat(card.getAttribute('data-original-qty')) || 0;
    const deliveredInput = card.querySelector('.delivered-qty-input');
    const remainingCell = card.querySelector('.remaining-qty');
    
    if (deliveredInput && remainingCell) {
        const currentDeliveryQty = parseFloat(deliveredInput.value) || 0;
        const alreadyDelivered = parseFloat(card.getAttribute('data-already-delivered')) || 0;
        const totalDelivered = alreadyDelivered + currentDeliveryQty;
        const remaining = originalQty - totalDelivered;
        
        // Remove all status classes first
        card.classList.remove('bg-orange-900/10', 'bg-red-900/10');
        remainingCell.classList.remove('text-orange-400', 'text-green-400', 'text-red-400', 'font-semibold');
        
        if (remaining < 0) {
            // Over-delivery
            remainingCell.textContent = 'OVER: +' + Math.abs(remaining).toFixed(2);
            remainingCell.classList.add('text-red-400', 'font-semibold');
            card.classList.add('bg-red-900/10');
        } else if (remaining > 0) {
            // Under-delivery (partial)
            remainingCell.textContent = remaining.toFixed(2);
            remainingCell.classList.add('text-orange-400', 'font-semibold');
            card.classList.add('bg-orange-900/10');
        } else {
            // Perfect (fully delivered)
            remainingCell.textContent = '—';
            remainingCell.classList.add('text-green-400');
        }
    }
}

// Calculate row amount
function calculateRowAmount(card) {
    const qtyInput = card.querySelector('.delivered-qty-input');
    const priceCell = card.querySelector('.price-cell');
    const amountInput = card.querySelector('.amount-input');

    if (qtyInput && priceCell && amountInput) {
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceCell.innerText) || 0;
        amountInput.value = (qty * price).toFixed(2);
    }
    
    calculateRemaining(card);
    checkPartialDelivery();
}

// ✅ UPDATED: Auto-set delivery type based on quantities
function checkPartialDelivery() {
    const container = document.getElementById('delivery-items-container');
    const cards = container.querySelectorAll('.delivery-item-card');
    let hasUnderDelivery = false;
    let hasOverDelivery = false;
    let partialItems = [];
    let overDeliveryItems = [];
    
    cards.forEach(card => {
        const originalQty = parseFloat(card.getAttribute('data-original-qty')) || 0;
        const alreadyDelivered = parseFloat(card.getAttribute('data-already-delivered')) || 0;
        const deliveredInput = card.querySelector('.delivered-qty-input');
        const currentDeliveryQty = parseFloat(deliveredInput?.value) || 0;
        
        const totalDelivered = alreadyDelivered + currentDeliveryQty;
        const remaining = originalQty - totalDelivered;
        
        // Get item details from the card
        const itemCodeDiv = card.querySelector('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm.font-mono');
        const itemDescDiv = card.querySelectorAll('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm')[0];
        
        const itemCode = itemCodeDiv?.textContent.trim() || '';
        const itemDesc = itemDescDiv?.textContent.trim() || '';
        
        if (remaining > 0) {
            // Under-delivery (partial)
            hasUnderDelivery = true;
            partialItems.push({
                code: itemCode,
                description: itemDesc,
                original: originalQty,
                delivered: totalDelivered,
                remaining: remaining
            });
        } else if (remaining < 0) {
            // Over-delivery
            hasOverDelivery = true;
            overDeliveryItems.push({
                code: itemCode,
                description: itemDesc,
                original: originalQty,
                delivered: totalDelivered,
                excess: Math.abs(remaining)
            });
        }
    });
    
    const warningDiv = document.getElementById('partial_delivery_warning');
    const summaryDiv = document.getElementById('partial_items_summary');
    const deliveryTypeSelect = document.getElementById('delivery_type');
    
    if (hasUnderDelivery || hasOverDelivery) {
        warningDiv.classList.remove('hidden');
        
        let summaryHTML = '';
        
        if (hasOverDelivery) {
            summaryHTML += '<div class="bg-red-950/30 p-2 rounded mb-2"><strong class="text-red-300">⚠️ Over-Delivery:</strong><ul class="mt-1 space-y-1">';
            overDeliveryItems.forEach(item => {
                summaryHTML += `<li>• <strong>${item.code}</strong> - ${item.description}: Total Delivered <span class="text-red-400">${item.delivered.toFixed(2)}</span> vs SO <span class="text-blue-400">${item.original}</span> (Excess: <span class="text-red-400">+${item.excess.toFixed(2)}</span>)</li>`;
            });
            summaryHTML += '</ul></div>';
        }
        
        if (hasUnderDelivery) {
            summaryHTML += '<div class="bg-orange-950/30 p-2 rounded"><strong class="text-orange-300">📦 Partial Delivery:</strong><ul class="mt-1 space-y-1">';
            partialItems.forEach(item => {
                summaryHTML += `<li>• <strong>${item.code}</strong> - ${item.description}: Total Delivered <span class="text-orange-400">${item.delivered.toFixed(2)}</span> of <span class="text-blue-400">${item.original}</span> (Remaining: <span class="text-orange-400">${item.remaining.toFixed(2)}</span>)</li>`;
            });
            summaryHTML += '</ul></div>';
        }
        
        summaryDiv.innerHTML = summaryHTML;
        
        // ✅ Auto-set delivery type to Partial if under-delivered
        if (hasUnderDelivery && deliveryTypeSelect && canManageDeliveries) {
            deliveryTypeSelect.value = 'Partial';
        }
    } else {
        warningDiv.classList.add('hidden');
        summaryDiv.innerHTML = '';
        
        // ✅ Reset to Full if all quantities match perfectly
        if (deliveryTypeSelect && canManageDeliveries) {
            deliveryTypeSelect.value = 'Full';
        }
    }
}


// ✅ Handle quantity change
function handleQuantityChange(e) {
    const input = e.target;
    const card = input.closest('.delivery-item-card');
    const currentQty = parseFloat(input.value) || 0;
    
    if (currentQty < 0) {
        input.value = 0;
    }
    
    calculateRowAmount(card);
}

// Attach quantity listeners to all inputs
function attachQuantityListeners() {
    document.querySelectorAll('.delivered-qty-input').forEach(input => {
        input.removeEventListener('input', handleQuantityChange); // Remove old listeners
        input.addEventListener('input', handleQuantityChange);
    });
}

function populateItemsTable(items, isViewOnly = false) {
    const container = document.getElementById('delivery-items-container');
    container.innerHTML = ''; // Clear existing items
    
    if (items && items.length > 0) {
        console.log(`📋 Loading ${items.length} items (view-only: ${isViewOnly})`);
        
        items.forEach((item, index) => {
            const originalQty = item.original_quantity || item.quantity;
            const deliveredQty = item.quantity || 0;
            const alreadyDelivered = item.already_delivered || 0;
            const remaining = item.remaining_quantity || 0;
            const isHidden = item.is_hidden || false; // ✅ NEW: Check if item should be hidden
            
            const itemCard = document.createElement('div');
            itemCard.className = 'delivery-item-card border border-gray-700 rounded-lg p-4 bg-gray-800/50 hover:bg-gray-800/70 transition-colors';
            itemCard.setAttribute('data-original-qty', originalQty);
            itemCard.setAttribute('data-already-delivered', alreadyDelivered);
            itemCard.setAttribute('data-index', index);
            
            // ✅ NEW: Mark card as hidden if quantity is 0 (was removed)
            if (isHidden) {
                itemCard.setAttribute('data-hidden', 'true');
                itemCard.style.display = 'none';
            }
            
            // Determine remaining color and status
            let remainingClass = 'text-green-400';
            let remainingDisplay = '—';
            let rowBgClass = '';
            
            if (remaining > 0) {
                remainingClass = 'text-orange-400 font-semibold';
                remainingDisplay = remaining.toFixed(2);
                rowBgClass = 'bg-orange-900/10';
            } else if (remaining < 0) {
                remainingClass = 'text-red-400 font-semibold';
                remainingDisplay = 'OVER: +' + Math.abs(remaining).toFixed(2);
                rowBgClass = 'bg-red-900/10';
            }
            
            if (rowBgClass && !isHidden) {
                itemCard.classList.add(rowBgClass);
            }
            
            const inputReadonly = (isViewOnly || !canManageDeliveries) ? 'readonly' : '';
            const inputDisabled = (isViewOnly || !canManageDeliveries) ? 'disabled' : '';
            
           itemCard.innerHTML = `
            <div class="flex justify-between items-start mb-4">
                <h4 class="text-sm font-semibold text-gray-300">Item #${index + 1}</h4>
                ${!isViewOnly && canManageDeliveries ? `
                <button type="button" onclick="removeDeliveryItem(this)" 
                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-all flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Remove
                </button>
                ` : ''}
            </div>

            <!-- ✅ ADD THESE HIDDEN INPUTS -->
            <input type="hidden" class="data-item-code" value="${item.item_code || ''}">
            <input type="hidden" class="data-item-description" value="${item.item_description || ''}">
            <input type="hidden" class="data-brand" value="${item.brand || ''}">
            <input type="hidden" class="data-item-category" value="${item.item_category || ''}">

            <!-- Item Details Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Item Code</label>
                        <div class="bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-sm font-mono">
                            ${item.item_code || '—'}
                        </div>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-400 mb-1">Description</label>
                        <div class="bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-sm">
                            ${item.item_description || '—'}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Brand</label>
                        <div class="bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-sm">
                            ${item.brand || '—'}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Category</label>
                        <div class="bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-sm">
                            ${item.item_category || '—'}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Original Qty</label>
                        <div class="bg-gray-900 border border-blue-600/50 text-blue-400 rounded-md px-3 py-2 text-center">
                            <div class="font-semibold">${originalQty}</div>
                            <div class="text-xs text-gray-500">${item.uom || 'Kgs'}</div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Delivered Qty *</label>
                        <input type="number" 
                            class="delivered-qty-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-center font-semibold" 
                            value="${deliveredQty}" 
                            step="0.01" 
                            min="0"
                            ${inputReadonly}>
                    </div>
                    
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Remaining</label>
                        <div class="bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-center">
                            <div class="remaining-qty ${remainingClass}">${remainingDisplay}</div>
                            <div class="text-xs text-gray-500">${item.uom || 'Kgs'}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">UOM</label>
                        <div class="bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-sm text-center">
                            ${item.uom || 'Kgs'}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Unit Price</label>
                        <div class="price-cell bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-sm text-right font-mono">
                            ${item.unit_price || 0}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Amount</label>
                        <input type="number" 
                            class="amount-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-right font-mono text-green-400" 
                            value="${((deliveredQty || 0) * (item.unit_price || 0)).toFixed(2)}" 
                            readonly>
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Note</label>
                    <input type="text"
                        class="notes-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2 text-sm"
                        value="${item.notes || ''}"
                        placeholder="Add notes for this item..."
                        ${inputReadonly}>
                </div>
            `;
            
            container.appendChild(itemCard);
        });

        if (!isViewOnly) {
            attachQuantityListeners();
            checkPartialDelivery();
        }
    } else {
        container.innerHTML = `
            <div class="border border-gray-700 rounded-lg p-8 bg-gray-800/30 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-gray-400 text-lg">No items available for delivery</p>
                <p class="text-gray-500 text-sm mt-2">All items have been fully delivered or no items remaining</p>
            </div>
        `;
    }
}

// Add new delivery item 
function addDeliveryItem() {
    const container = document.getElementById('delivery-items-container');
    const hiddenItems = container.querySelectorAll('.delivery-item-card[data-hidden="true"]');
    
    if (hiddenItems.length === 0) {
        Swal.fire('No Items to Restore', 'All items are already visible. Search for a Sales Order to load items.', 'info');
        return;
    }
    
    // Build options for dropdown
    let optionsHTML = '<option value="">-- Select Item to Restore --</option>';
    hiddenItems.forEach((card, index) => {
        const itemCodeDiv = card.querySelector('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm.font-mono');
        const itemDescDiv = card.querySelectorAll('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm')[0];
        const itemCode = itemCodeDiv?.textContent.trim() || 'Unknown';
        const itemDesc = itemDescDiv?.textContent.trim() || 'No description';
        
        optionsHTML += `<option value="${index}">${itemCode} - ${itemDesc}</option>`;
    });
    
    Swal.fire({
        title: 'Restore Removed Item',
        html: `
            <div class="text-left">
                <label class="block text-sm text-gray-600 mb-2">Select an item to restore:</label>
                <select id="restore-item-select" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    ${optionsHTML}
                </select>
                <p class="text-xs text-gray-500 mt-2">💡 These items were temporarily removed but are still part of the Sales Order.</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Restore Item',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const selectedIndex = document.getElementById('restore-item-select').value;
            if (!selectedIndex) {
                Swal.showValidationMessage('Please select an item to restore');
                return false;
            }
            return selectedIndex;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const selectedIndex = parseInt(result.value);
            const cardToRestore = hiddenItems[selectedIndex];
            
            if (cardToRestore) {
                // Remove hidden status
                cardToRestore.removeAttribute('data-hidden');
                cardToRestore.style.display = '';
                
                renumberDeliveryItems();
                checkPartialDelivery();
                
                // Get item details for confirmation
                const itemCodeDiv = cardToRestore.querySelector('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm.font-mono');
                const itemCode = itemCodeDiv?.textContent.trim() || 'Item';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Item Restored',
                    text: `${itemCode} has been restored to the delivery.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }
    });
}

// ✅ UPDATED: Temporarily hide item instead of removing
function removeDeliveryItem(button) {
    const card = button.closest('.delivery-item-card');
    const container = document.getElementById('delivery-items-container');
    const visibleItems = container.querySelectorAll('.delivery-item-card:not([data-hidden="true"])');
    
    if (visibleItems.length <= 1) {
        Swal.fire('Cannot Remove', 'You must have at least one visible item in the delivery.', 'warning');
        return;
    }
    
    // Get item details for confirmation
    const itemCodeDiv = card.querySelector('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm.font-mono');
    const itemDescDiv = card.querySelectorAll('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm')[0];
    const itemCode = itemCodeDiv?.textContent.trim() || 'this item';
    const itemDesc = itemDescDiv?.textContent.trim() || '';
    
    Swal.fire({
        title: 'Remove Item Temporarily?',
        html: `
            <div class="text-left">
                <p class="mb-2">Item: <strong>${itemCode}</strong></p>
                <p class="text-sm text-gray-600 mb-3">${itemDesc}</p>
                <p class="text-sm text-orange-600">⚠️ This item will be temporarily removed from this delivery.</p>
                <p class="text-sm text-gray-600 mt-2">You can restore it using the "Add Item" button.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mark as hidden instead of removing from DOM
            card.setAttribute('data-hidden', 'true');
            card.style.display = 'none';
            
            // Set quantity to 0 when hidden
            const qtyInput = card.querySelector('.delivered-qty-input');
            if (qtyInput) {
                qtyInput.value = 0;
            }
            
            renumberDeliveryItems();
            checkPartialDelivery();
            
            Swal.fire({
                icon: 'success',
                title: 'Item Removed',
                text: `${itemCode} has been temporarily removed. Use "Add Item" to restore it.`,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

function renumberDeliveryItems() {
    const items = document.querySelectorAll('.delivery-item-card');
    items.forEach((item, index) => {
        item.querySelector('h4').textContent = `Item #${index + 1}`;
        item.setAttribute('data-index', index);
    });
}

// ✅ FIND THIS SECTION IN YOUR CODE (around line 710-950)
// Replace the ENTIRE search button event listener with this fixed version:

document.getElementById("search_btn").addEventListener("click", async () => {
    const soNumber = document.getElementById("so_search").value.trim();
    
    if (!soNumber) {
        Swal.fire("Error", "Please enter a Sales Order Number", "warning");
        return;
    }

    try {
        Swal.fire({
            title: 'Searching...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const response = await fetch(`${searchUrl}?so_number=${soNumber}`, {
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Accept": "application/json"
            }
        });

        const data = await response.json();
        Swal.close();

        // ✅ Handle errors with specific messages for rejected/cancelled/delivered
        if (!response.ok) {
            let alertConfig = {
                icon: "error",
                title: "Cannot Search",
                text: data.message || data.error || "Sales Order not found",
                confirmButtonText: 'OK'
            };

            // ✅ Customize alert based on error type
            if (data.error_type === 'rejected') {
                alertConfig = {
                    icon: "warning",
                    title: "⚠️ Delivery Rejected",
                    html: `
                        <div class="text-left">
                            <p class="mb-2">This Sales Order has been <strong class="text-red-600">rejected for delivery</strong>.</p>
                            <p class="text-sm text-gray-600">Status: View-only</p>
                            <p class="text-sm text-gray-600 mt-2">You cannot create new deliveries for rejected orders.</p>
                        </div>
                    `,
                    confirmButtonText: 'Understood',
                    confirmButtonColor: '#dc2626'
                };
            } else if (data.error_type === 'cancelled') {
                alertConfig = {
                    icon: "warning",
                    title: "🚫 Delivery Pulled Out",
                    html: `
                        <div class="text-left">
                            <p class="mb-2">This Sales Order has been <strong class="text-orange-600">pulled out for delivery</strong>.</p>
                            <p class="text-sm text-gray-600">Status: View-only</p>
                            <p class="text-sm text-gray-600 mt-2">You cannot create new deliveries for cancelled orders.</p>
                        </div>
                    `,
                    confirmButtonText: 'Understood',
                    confirmButtonColor: '#ea580c'
                };
            } else if (data.error_type === 'delivered') {
                alertConfig = {
                    icon: "info",
                    title: "✅ Already Delivered",
                    html: `
                        <div class="text-left">
                            <p class="mb-2">This Sales Order has already been <strong class="text-green-600">delivered and approved</strong>.</p>
                            <p class="text-sm text-gray-600 mt-2">No new deliveries can be created.</p>
                            <p class="text-sm text-blue-600 mt-2">💡 You can view delivery details in the <strong>Deliveries List</strong>.</p>
                        </div>
                    `,
                    confirmButtonText: 'Go to Deliveries',
                    confirmButtonColor: '#2563eb',
                    showCancelButton: true,
                    cancelButtonText: 'Close'
                };
            }

            const result = await Swal.fire(alertConfig);
            
            // ✅ Redirect to deliveries list if user clicks "Go to Deliveries" for delivered orders
            if (data.error_type === 'delivered' && result.isConfirmed) {
                window.location.href = deliveriesIndexUrl;
            }
            
            return;
        }

        // ✅✅✅ AUTO-APPROVE NOTIFICATION (MOVED HERE FROM SAVE BUTTON)
        if (data.will_auto_approve && !data.is_edit_mode && !data.is_view_only) {
            await Swal.fire({
                icon: 'info',
                title: '✅ Auto-Approve Enabled',
                html: `
                    <div class="text-left">
                        <p class="mb-2">As a <strong class="text-green-600">Delivery Approver</strong>, your deliveries will be <strong>automatically approved</strong> upon creation.</p>
                        <p class="text-sm text-gray-600 mt-2">This delivery will be marked as "Approved" and "Delivered" immediately after saving.</p>
                    </div>
                `,
                confirmButtonText: 'Understood',
                confirmButtonColor: '#16a34a'
            });
        }

        // ✅ Show status/info alert if present
        if (data.show_partial_alert === true && data.info_message) {
            console.log('📋 Showing status alert:', data.info_message);
            
            let iconType = 'info';
            let titleText = 'Sales Order Information';
            
            // Determine alert type based on SO status
            if (data.so_status === 'Declined' || data.so_status === 'Cancelled') {
                iconType = 'warning';
                titleText = `Sales Order ${data.so_status}`;
            } else if (data.is_closed) {
                iconType = 'info';
                titleText = 'Fully Delivered/Closed';
            } else if (data.so_status === 'Pending') {
                iconType = 'warning';
                titleText = 'Pending Approval';
            } else if (data.delivery_history?.pulled_out || data.delivery_history?.cancelled || data.delivery_history?.rejected) {
                iconType = 'warning';
                titleText = 'Delivery History Available';
            }
            
            await Swal.fire({
                icon: iconType,
                title: titleText,
                html: `
                    <div class="text-left">
                        <p class="mb-2">${data.info_message}</p>
                        ${data.delivery_count > 0 ? `<p class="text-sm text-gray-600 mt-2">Previous deliveries: <strong>${data.delivery_count}</strong></p>` : ''}
                        ${data.is_view_only ? `<p class="text-sm text-red-600 mt-2">⚠️ View-only mode - Cannot create new deliveries</p>` : ''}
                        ${!data.is_view_only && data.items_count > 0 ? `<p class="text-sm text-gray-600">Items available: <strong>${data.items_count}</strong></p>` : ''}
                    </div>
                `,
                confirmButtonText: data.is_view_only ? 'View Details' : 'Continue'
            });
        }

        // ✅ Store delivery ID ONLY if in edit mode
        deliveryId = data.is_edit_mode ? (data.id || null) : null;

        // Populate Sales Order Information
        document.getElementById("sales_order_number").value = data.sales_order_number || '';
        document.getElementById("customer_code").value = data.customer_code || '';
        document.getElementById("customer_name").value = data.customer_name || '';
        document.getElementById("tin_no").value = data.tin_no || '';
        document.getElementById("branch").value = data.branch || '';
        document.getElementById("sales_representative").value = data.sales_representative || '';
        document.getElementById("sales_executive").value = data.sales_executive || '';
        document.getElementById("po_number").value = data.po_number || '';
        document.getElementById("request_delivery_date").value = data.request_delivery_date || '';
        document.getElementById("additional_instructions").value = data.additional_instructions || '';

        // ✅ Show batch name prominently with status indicators
        const batchValue = data.delivery_batch || 'Not Set';
        document.getElementById("delivery_batch").value = batchValue;
        
        // ✅ Display batch info with improved styling and status indicators
        const batchDisplay = document.createElement('div');
        batchDisplay.id = 'batch_info_display';
        batchDisplay.className = 'mb-4 p-3 rounded-lg';
        
        // Determine badge and styling based on status
        let badgeHtml = '';
        let bgClass = 'bg-blue-900/20 border-blue-700';
        let iconColor = 'text-blue-400';
        let textColor = 'text-blue-300';
        
        if (data.is_view_only) {
            bgClass = 'bg-gray-800/40 border-gray-600';
            iconColor = 'text-gray-400';
            textColor = 'text-gray-300';
            badgeHtml = '<span class="px-2 py-1 bg-gray-600/30 text-gray-300 text-xs rounded border border-gray-500">View Only</span>';
        } else if (data.is_edit_mode) {
            bgClass = 'bg-yellow-900/20 border-yellow-700';
            iconColor = 'text-yellow-400';
            textColor = 'text-yellow-300';
            badgeHtml = '<span class="px-2 py-1 bg-yellow-600/20 text-yellow-400 text-xs rounded border border-yellow-600">Editing</span>';
        } else if (batchValue === 'Full Delivery') {
            bgClass = 'bg-green-900/20 border-green-700';
            iconColor = 'text-green-400';
            textColor = 'text-green-300';
            badgeHtml = '<span class="px-2 py-1 bg-green-600/20 text-green-400 text-xs rounded border border-green-600">Full Delivery</span>';
        } else if (batchValue.startsWith('Batch')) {
            bgClass = 'bg-orange-900/20 border-orange-700';
            iconColor = 'text-orange-400';
            textColor = 'text-orange-300';
            badgeHtml = `<span class="px-2 py-1 bg-orange-600/20 text-orange-400 text-xs rounded border border-orange-600">Partial - ${batchValue}</span>`;
        } else {
            badgeHtml = '<span class="px-2 py-1 bg-blue-600/20 text-blue-400 text-xs rounded border border-blue-600">New Delivery</span>';
        }
        
        batchDisplay.className += ' border ' + bgClass;
        
        batchDisplay.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-gray-400">Delivery Batch</p>
                    <p class="text-lg font-semibold ${textColor}">${batchValue}</p>
                </div>
                ${badgeHtml}
            </div>
            ${data.so_status ? `<p class="text-xs text-gray-400 mt-2">SO Status: <span class="font-semibold">${data.so_status}</span></p>` : ''}
        `;
        
        // Remove old batch display if exists
        const oldBatchDisplay = document.getElementById('batch_info_display');
        if (oldBatchDisplay) oldBatchDisplay.remove();
        
        // Insert after search box
        const searchBox = document.getElementById("so_search").closest('.mb-6');
        searchBox.after(batchDisplay);

        // Populate Delivery Details
        document.getElementById("plate_no").value = data.plate_no || '';
        document.getElementById("sales_invoice_no").value = data.sales_invoice_no || '';
        document.getElementById("dr_no").value = data.is_edit_mode ? (data.dr_no || '') : '';
        document.getElementById("status").value = data.status || 'Delivered';
        document.getElementById("delivery_type").value = data.delivery_type || 'Full';

        // ✅ Disable inputs if view-only mode
        if (data.is_view_only) {
            document.getElementById("plate_no").readOnly = true;
            document.getElementById("sales_invoice_no").readOnly = true;
            document.getElementById("dr_no").readOnly = true;
            document.getElementById("status").disabled = true;
            document.getElementById("delivery_type").disabled = true;
            document.getElementById("attachment").disabled = true;
            
            // Hide save button
            const saveBtn = document.getElementById("save_btn");
            if (saveBtn) {
                saveBtn.style.display = 'none';
            }
        } else if (canManageDeliveries) {
            // Re-enable if user has permissions
            document.getElementById("plate_no").readOnly = false;
            document.getElementById("sales_invoice_no").readOnly = false;
            document.getElementById("dr_no").readOnly = false;
            document.getElementById("status").disabled = false;
            document.getElementById("delivery_type").disabled = false;
            document.getElementById("attachment").disabled = false;
            
            const saveBtn = document.getElementById("save_btn");
            if (saveBtn) {
                saveBtn.style.display = 'inline-block';
            }
        }

        // Attachment
        if (data.attachment_url && data.is_edit_mode) {
            attachmentContainer.classList.remove('hidden');
            attachmentLink.href = data.attachment_url;
            attachmentName.textContent = data.attachment_name || 'View Current Attachment';
        } else {
            attachmentContainer.classList.add('hidden');
        }

        // Hide batch selector (not needed with auto-batch system)
        document.getElementById("batch_selector_container").classList.add('hidden');

        // Populate items table
        populateItemsTable(data.items, data.is_view_only);

        // ✅ Show appropriate success message
        let successMessage = '';
        if (data.is_view_only) {
            successMessage = `Viewing ${data.so_status} Sales Order (Read-Only)`;
        } else if (data.is_edit_mode) {
            successMessage = `Editing delivery: ${batchValue}`;
        } else if (data.will_auto_approve) {
            successMessage = `Ready to create delivery (will auto-approve)`;
        } else if (batchValue === 'Full Delivery') {
            successMessage = `Ready to create full delivery`;
        } else if (batchValue.startsWith('Batch')) {
            successMessage = `Ready to create ${batchValue} with ${data.items_count} remaining item(s)`;
        } else {
            successMessage = `Ready to create delivery`;
        }

        Swal.fire({
            icon: data.is_view_only ? 'info' : 'success',
            title: data.is_view_only ? 'View Mode' : 'Found!',
            text: successMessage,
            timer: 2500,
            showConfirmButton: false
        });

    } catch (err) {
        console.error("Search error:", err);
        Swal.close();
        Swal.fire("Error", "Failed to search for Sales Order", "error");
    }
});

// ✅ SAVE DELIVERY
if (canManageDeliveries) {
    document.getElementById("save_btn").addEventListener("click", async () => {
        const soNumber = document.getElementById("sales_order_number").value;
        if(!soNumber){ 
            Swal.fire("Hold on!","Please search for a Sales Order first.","info"); 
            return; 
        }

        const drNo = document.getElementById("dr_no").value.trim();
        
        if (!deliveryId && !drNo) {
            Swal.fire("Required Field", "Please enter a DR Number before saving.", "warning");
            return;
        }

        // ✅ FIXED: Check for variances using ALL CARDS (including hidden ones)
        const container = document.getElementById("delivery-items-container");
        const cards = container.querySelectorAll('.delivery-item-card'); // ✅ Get ALL cards, not just visible
        let hasUnderDelivery = false;
        let hasOverDelivery = false;
        
        cards.forEach(card => {
            // ✅ Skip hidden items for variance check
            if (card.getAttribute('data-hidden') === 'true') {
                return;
            }
            
            const originalQty = parseFloat(card.getAttribute('data-original-qty')) || 0;
            const qtyInput = card.querySelector('.delivered-qty-input');
            if (!qtyInput) return;
            
            const currentQty = parseFloat(qtyInput.value) || 0;
            const variance = currentQty - originalQty;
            
            if (variance < 0) hasUnderDelivery = true;
            if (variance > 0) hasOverDelivery = true;
        });
        
        // Confirm over-delivery if detected
        if (hasOverDelivery) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Over-Delivery Detected',
                html: `
                    <p>Some items are being delivered in quantities <strong>exceeding</strong> the Sales Order.</p>
                    <p class="mt-2 text-sm text-red-600">This may indicate an error or special arrangement.</p>
                    <p class="mt-2 text-sm">Do you want to continue?</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Yes, Save with Over-Delivery',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
            });
            
            if (!result.isConfirmed) return;
        }
        
        // Confirm under-delivery if detected
        if (hasUnderDelivery) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Partial Delivery Confirmation',
                html: `
                    <p>Some items have quantities <strong>less than</strong> the Sales Order.</p>
                    <p class="mt-2 text-sm">This will be marked as a Partial Delivery.</p>
                    <p class="mt-2 text-sm">Do you want to continue?</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Yes, Save Partial Delivery',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#f97316',
            });
            
            if (!result.isConfirmed) return;
        }

        // Build payload
        const payload = {
            sales_order_number: document.getElementById("sales_order_number").value.trim(),
            delivery_batch: document.getElementById("delivery_batch").value.trim() || null,
            delivery_type: document.getElementById("delivery_type").value,
            dr_no: drNo,
            customer_name: document.getElementById("customer_name").value.trim() || null,
            tin_no: document.getElementById("tin_no").value.trim() || null,
            branch: document.getElementById("branch").value.trim() || null,
            sales_representative: document.getElementById("sales_representative").value.trim() || null,
            sales_executive: document.getElementById("sales_executive").value.trim() || null,
            po_number: document.getElementById("po_number").value.trim() || null,
            request_delivery_date: document.getElementById("request_delivery_date").value || null,
            plate_no: document.getElementById("plate_no").value.trim() || null,
            sales_invoice_no: document.getElementById("sales_invoice_no").value.trim() || null,
            approved_by: document.getElementById("approved_by").value.trim() || null,
            status: document.getElementById("status").value,
            additional_instructions: document.getElementById("additional_instructions").value.trim() || null,
            items: []
        };

cards.forEach(card => {
    // ✅ Get values from hidden inputs (RELIABLE METHOD)
    const itemCode = card.querySelector('.data-item-code')?.value || '';
    const itemDesc = card.querySelector('.data-item-description')?.value || '';
    const brand = card.querySelector('.data-brand')?.value || '';
    const itemCategory = card.querySelector('.data-item-category')?.value || '';
    
    // Get inputs
    const deliveredQtyInput = card.querySelector('.delivered-qty-input');
    const amountInput = card.querySelector('.amount-input');
    const notesInput = card.querySelector('.notes-input');
    const priceCell = card.querySelector('.price-cell');
    
    // Get UOM
    const uomDivs = card.querySelectorAll('.bg-gray-900.border.border-gray-700.text-gray-200.rounded-md.px-3.py-2.text-sm.text-center');
    
    const originalQty = parseFloat(card.getAttribute('data-original-qty')) || 0;
    const deliveredQty = parseFloat(deliveredQtyInput?.value) || 0;
    const alreadyDelivered = parseFloat(card.getAttribute('data-already-delivered')) || 0;
    
    // Calculate remaining (total delivered from all batches)
    const totalDelivered = alreadyDelivered + deliveredQty;
    const calculatedRemaining = originalQty - totalDelivered;
    const remainingQty = calculatedRemaining < 0 ? 0 : calculatedRemaining;
    
    payload.items.push({
        item_code: itemCode, 
        item_description: itemDesc,  
        brand: brand,  
        item_category: itemCategory, 
        quantity: deliveredQty,
        original_quantity: originalQty,
        remaining_quantity: remainingQty,
        uom: uomDivs[0]?.textContent.trim() || 'Kgs',
        unit_price: parseFloat(priceCell?.textContent.trim()) || 0,
        total_amount: parseFloat(amountInput?.value) || 0,
        notes: notesInput?.value || ''
    });
});

        try {
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            Object.keys(payload).forEach(key => {
                if (key !== 'items') formData.append(key, payload[key] !== null ? payload[key] : '');
            });
            payload.items.forEach((item, index) => {
                Object.keys(item).forEach(key => formData.append(`items[${index}][${key}]`, item[key]));
            });

            const attachmentInput = document.getElementById("attachment");
            if(attachmentInput && attachmentInput.files.length > 0){
                formData.append('attachment', attachmentInput.files[0]);
            }

            if(deliveryId) formData.append('_method', 'PUT');

            const response = await fetch(deliveryId ? `${baseUpdateUrl}/${deliveryId}` : storeUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();
            Swal.close();

            if(response.ok && data.success){
                Swal.fire({
                    icon:'success',
                    title: deliveryId ? 'Updated!' : 'Created!',
                    html: data.message || 'Delivery saved successfully!',
                    showConfirmButton:false,
                    timer:2000
                }).then(() => window.location.href = deliveriesIndexUrl);
            } else {
                let errorText = data.message || "Something went wrong.";
                if(data.errors) {
                    errorText += "\n\nDetails:\n" + Object.entries(data.errors)
                        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                        .join('\n');
                }
                Swal.fire("Validation Error", errorText, "error");
            }
        } catch(err){
            console.error('💥 SAVE ERROR:', err);
            Swal.close();
            Swal.fire("Error","Network or server error while saving.","error");
        }
    });
}
</script>
@endsection