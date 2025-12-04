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

    <!-- 📋 Delivery Items Table -->
    <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-1">Delivery Items</h3>
    <div class="overflow-x-auto">
        <table class="w-full border border-gray-700 mb-4 rounded-md overflow-hidden">
            <thead class="bg-gray-800 text-gray-300">
                <tr>
                    @foreach(['Item Code', 'Description', 'Brand', 'Category','Original Qty', 'Delivered Qty', 'Remaining', 'UOM', 'Unit Price', 'Amount', 'Note'] as $header)
                        <th class="border border-gray-700 px-4 py-2 text-left">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-gray-900" id="items_tbody"></tbody>
        </table>
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

// ✅ Calculate remaining quantity and update display
function calculateRemaining(row) {
    const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
    const deliveredInput = row.querySelector('.delivered-qty-input');
    const remainingCell = row.querySelector('.remaining-qty');
    
    if (deliveredInput && remainingCell) {
        const deliveredQty = parseFloat(deliveredInput.value) || 0;
        const variance = deliveredQty - originalQty;
        
        remainingCell.textContent = variance === 0 ? '—' : (variance > 0 ? '+' : '') + variance.toFixed(2);
        
        // Visual feedback based on variance
        remainingCell.classList.remove('text-orange-400', 'text-red-400', 'text-green-400', 'font-semibold');
        row.classList.remove('bg-orange-900/10', 'bg-red-900/10');
        
        if (variance > 0) {
            // Over-delivery (red)
            remainingCell.classList.add('text-red-400', 'font-semibold');
            row.classList.add('bg-red-900/10');
        } else if (variance < 0) {
            // Under-delivery (orange)
            remainingCell.classList.add('text-orange-400', 'font-semibold');
            row.classList.add('bg-orange-900/10');
        } else {
            // Perfect match (green)
            remainingCell.classList.add('text-green-400');
        }
    }
}

// Calculate row amount
function calculateRowAmount(row) {
    const qtyInput = row.querySelector('.delivered-qty-input');
    const priceCell = row.querySelector('.price-cell');
    const amountInput = row.querySelector('.amount-input');

    if (qtyInput && priceCell && amountInput) {
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceCell.innerText) || 0;
        amountInput.value = (qty * price).toFixed(2);
    }
    
    calculateRemaining(row);
    checkPartialDelivery();
}

// ✅ UPDATED: Auto-set delivery type based on quantities
function checkPartialDelivery() {
    const tbody = document.getElementById('items_tbody');
    const rows = tbody.querySelectorAll('tr');
    let hasUnderDelivery = false;
    let hasOverDelivery = false;
    let partialItems = [];
    let overDeliveryItems = [];
    
    rows.forEach(row => {
        const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
        const deliveredInput = row.querySelector('.delivered-qty-input');
        const deliveredQty = parseFloat(deliveredInput?.value) || 0;
        const variance = deliveredQty - originalQty;
        
        const itemCode = row.querySelector('td:first-child')?.textContent || '';
        const itemDesc = row.querySelector('td:nth-child(2)')?.textContent || '';
        
        if (variance < 0) {
            hasUnderDelivery = true;
            partialItems.push({
                code: itemCode,
                description: itemDesc,
                original: originalQty,
                delivered: deliveredQty,
                variance: variance
            });
        } else if (variance > 0) {
            hasOverDelivery = true;
            overDeliveryItems.push({
                code: itemCode,
                description: itemDesc,
                original: originalQty,
                delivered: deliveredQty,
                variance: variance
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
                summaryHTML += `<li>• <strong>${item.code}</strong> - ${item.description}: Delivered <span class="text-red-400">${item.delivered}</span> vs SO <span class="text-blue-400">${item.original}</span> (Excess: <span class="text-red-400">+${item.variance.toFixed(2)}</span>)</li>`;
            });
            summaryHTML += '</ul></div>';
        }
        
        if (hasUnderDelivery) {
            summaryHTML += '<div class="bg-orange-950/30 p-2 rounded"><strong class="text-orange-300">📦 Under-Delivery:</strong><ul class="mt-1 space-y-1">';
            partialItems.forEach(item => {
                summaryHTML += `<li>• <strong>${item.code}</strong> - ${item.description}: Delivered <span class="text-orange-400">${item.delivered}</span> of <span class="text-blue-400">${item.original}</span> (Short: <span class="text-orange-400">${item.variance.toFixed(2)}</span>)</li>`;
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
        
        // ✅ Reset to Full if all quantities match
        if (deliveryTypeSelect && canManageDeliveries) {
            const allPerfect = Array.from(rows).every(row => {
                const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
                const deliveredInput = row.querySelector('.delivered-qty-input');
                const deliveredQty = parseFloat(deliveredInput?.value) || 0;
                return deliveredQty === originalQty;
            });
            
            if (allPerfect) {
                deliveryTypeSelect.value = 'Full';
            }
        }
    }
}

// ✅ Handle quantity change
function handleQuantityChange(e) {
    const input = e.target;
    const row = input.closest('tr');
    const currentQty = parseFloat(input.value) || 0;
    
    // Only prevent negative quantities
    if (currentQty < 0) {
        input.value = 0;
    }
    
    calculateRowAmount(row);
}

// Attach quantity listeners to all inputs
function attachQuantityListeners() {
    document.querySelectorAll('.delivered-qty-input').forEach(input => {
        input.addEventListener('input', handleQuantityChange);
    });
}

// Populate items table
function populateItemsTable(items) {
    const tbody = document.getElementById('items_tbody');
    tbody.innerHTML = ''; // Clear existing rows
    
    if (items && items.length > 0) {
        console.log(`📋 Loading ${items.length} items`);
        
        items.forEach((item, index) => {
            const tr = document.createElement("tr");
            tr.classList.add("hover:bg-gray-800/70", "transition-colors");
            tr.setAttribute('data-original-qty', item.original_quantity || item.quantity);
            
            const originalQty = item.original_quantity || item.quantity;
            const deliveredQty = item.quantity || 0;
            const variance = deliveredQty - originalQty;
            
            tr.innerHTML = `
                <td class="border border-gray-700 px-4 py-2">${item.item_code || '—'}</td>
                <td class="border border-gray-700 px-4 py-2">${item.item_description || '—'}</td>
                <td class="border border-gray-700 px-4 py-2">${item.brand || '—'}</td>
                <td class="border border-gray-700 px-4 py-2">${item.item_category || '—'}</td>
                <td class="border border-gray-700 px-4 py-2 text-center">
                    <div class="font-semibold text-blue-400">${originalQty}</div>
                    <div class="text-xs text-gray-500">${item.uom || 'Kgs'}</div>
                </td>
                <td class="border border-gray-700 px-4 py-2">
                    <input type="number" 
                        class="delivered-qty-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-1 text-center" 
                        value="${deliveredQty}" 
                        step="0.01" 
                        min="0"
                        ${canManageDeliveries ? '' : 'readonly'}>
                </td>
                <td class="border border-gray-700 px-4 py-2 text-center">
                    <div class="remaining-qty font-semibold ${variance > 0 ? 'text-red-400' : (variance < 0 ? 'text-orange-400' : 'text-green-400')}">
                        ${variance === 0 ? '—' : (variance > 0 ? '+' : '') + variance.toFixed(2)}
                    </div>
                </td>
                <td class="border border-gray-700 px-4 py-2 text-center">${item.uom || 'Kgs'}</td>
                <td class="border border-gray-700 px-4 py-2 price-cell text-right">${item.unit_price || 0}</td>
                <td class="border border-gray-700 px-4 py-2">
                    <input type="number" 
                        class="amount-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-1 text-right" 
                        value="${((deliveredQty || 0) * (item.unit_price || 0)).toFixed(2)}" 
                        readonly>
                </td>
                <td class="border border-gray-700 px-4 py-2">
                    <input type="text"
                        class="notes-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-1"
                        value="${item.notes || ''}"
                        ${canManageDeliveries ? '' : 'readonly'}>
                </td>
            `;
            tbody.appendChild(tr);
        });

        attachQuantityListeners();
        checkPartialDelivery();
    }
}

// =====================================================
// SEARCH SALES ORDER
// =====================================================
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

        // ✅ Handle errors first
        if (!response.ok) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: data.message || data.error || "Sales Order not found",
                confirmButtonText: 'OK'
            });
            return;
        }

        // ✅ Show info message for partial delivery history
        if (data.show_partial_alert === true && data.info_message) {
            console.log('📋 Showing partial delivery history alert');
            await Swal.fire({
                icon: 'info',
                title: 'Partial Delivery History',
                html: `
                    <div class="text-left">
                        <p class="mb-2">${data.info_message}</p>
                        ${data.delivery_count > 0 ? `<p class="text-sm text-gray-600">Previous deliveries: <strong>${data.delivery_count}</strong></p>` : ''}
                        ${data.items_count > 0 ? `<p class="text-sm text-gray-600">Items to deliver: <strong>${data.items_count}</strong></p>` : ''}
                    </div>
                `,
                confirmButtonText: 'Continue'
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

        // ✅ Show batch name prominently
        const batchValue = data.delivery_batch || 'Not Set';
        document.getElementById("delivery_batch").value = batchValue;
        
        // ✅ Display batch info with improved styling
        const batchDisplay = document.createElement('div');
        batchDisplay.id = 'batch_info_display';
        batchDisplay.className = 'mb-4 p-3 bg-blue-900/20 border border-blue-700 rounded-lg';
        
        // Determine badge style based on batch type
        let badgeHtml = '';
        if (data.is_edit_mode) {
            badgeHtml = '<span class="px-2 py-1 bg-yellow-600/20 text-yellow-400 text-xs rounded border border-yellow-600">Editing</span>';
        } else if (batchValue === 'Full Delivery') {
            badgeHtml = '<span class="px-2 py-1 bg-green-600/20 text-green-400 text-xs rounded border border-green-600">Full Delivery</span>';
        } else if (batchValue.startsWith('Batch')) {
            const batchNumber = batchValue.replace('Batch ', '');
            badgeHtml = `<span class="px-2 py-1 bg-orange-600/20 text-orange-400 text-xs rounded border border-orange-600">Partial - ${batchValue}</span>`;
        } else {
            badgeHtml = '<span class="px-2 py-1 bg-blue-600/20 text-blue-400 text-xs rounded border border-blue-600">New Delivery</span>';
        }
        
        batchDisplay.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-gray-400">Delivery Batch</p>
                    <p class="text-lg font-semibold text-blue-300">${batchValue}</p>
                </div>
                ${badgeHtml}
            </div>
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
        
        // ✅ Set delivery type
        document.getElementById("delivery_type").value = data.delivery_type || 'Full';

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
        populateItemsTable(data.items);

        // ✅ Show appropriate success message
        let successMessage = '';
        if (data.is_edit_mode) {
            successMessage = `Editing delivery: ${batchValue}`;
        } else if (batchValue === 'Full Delivery') {
            successMessage = `Ready to create full delivery`;
        } else if (batchValue.startsWith('Batch')) {
            successMessage = `Ready to create ${batchValue} with ${data.items_count} remaining item(s)`;
        } else {
            successMessage = `Ready to create delivery`;
        }

        Swal.fire({
            icon: 'success',
            title: 'Found!',
            text: successMessage,
            timer: 2000,
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

        // Check for variances
        const tbody = document.getElementById("items_tbody");
        let hasUnderDelivery = false;
        let hasOverDelivery = false;
        
        tbody.querySelectorAll("tr").forEach(row => {
            const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
            const qtyInput = row.querySelector('.delivered-qty-input');
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
            delivery_type: document.getElementById("delivery_type").value, // ✅ NEW
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

        tbody.querySelectorAll("tr").forEach(row => {
            const tds = row.querySelectorAll("td");
            if (tds.length < 11) return;
            
            const deliveredQty = parseFloat(tds[5].querySelector("input").value) || 0;
            const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
            
            const calculatedRemaining = originalQty - deliveredQty;
            const remainingQty = calculatedRemaining < 0 ? 0 : calculatedRemaining;
            
            payload.items.push({
                item_code: tds[0].innerText || '',
                item_description: tds[1].innerText || '',
                brand: tds[2].innerText || '',
                item_category: tds[3].innerText || '',
                quantity: deliveredQty,
                original_quantity: originalQty,
                remaining_quantity: remainingQty,
                uom: tds[7].innerText || '',
                unit_price: parseFloat(tds[8].innerText) || 0,
                total_amount: parseFloat(tds[9].querySelector("input").value) || 0,
                notes: tds[10].querySelector("input")?.value || ''
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