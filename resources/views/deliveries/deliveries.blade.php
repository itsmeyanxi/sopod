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

    {{-- ✅ NEW: Batch Selector (Hidden by default) --}}
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

            <!-- Find this section in your blade file and replace it: -->

            @foreach([
                'plate_no' => 'Plate No',
                'sales_invoice_no' => 'Sales Invoice No (Optional)', // ✅ Added (Optional)
                'dr_no' => 'DR No',
                'status' => 'Status'
            ] as $id => $label)
                <div>
                    <label class="block text-gray-400 text-sm">{{ $label }}</label>
                    @if($id === 'status')
                        <select id="status"
                                class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2"
                                {{ \App\Helpers\RoleHelper::canManageDeliveries() ? '' : 'disabled' }}>
                            <option value="Delivered">Delivered</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    @else
                        <input id="{{ $id }}" type="text"
                            class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2"
                            {{ \App\Helpers\RoleHelper::canManageDeliveries() ? '' : 'readonly' }}
                            placeholder="{{ $id === 'sales_invoice_no' ? 'Optional' : '' }}"> <!-- ✅ Added placeholder -->
                    @endif
                </div>
            @endforeach

            <!-- Replace the delivery type section with this: -->

            <div>
                <label class="block text-gray-400 text-sm mb-1">Type of Delivery</label>
                
                <div id="delivery_type_display" class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-2 flex items-center justify-between">
                    <span id="delivery_type_text">Not set</span>
                    <span id="delivery_type_badge" class="px-2 py-1 rounded text-xs border bg-gray-600/20 text-gray-400 border-gray-600">
                        —
                    </span>
                </div>
                
                <!-- Hidden input to store the value -->
                <input type="hidden" id="delivery_type" name="delivery_type" value="">
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
                    @foreach(['Item Code', 'Description', 'Brand', 'Category','Quantity', 'UOM', 'Unit Price', 'Amount'] as $header)
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
const canManageDeliveries = {{ \App\Helpers\RoleHelper::canManageDeliveries() ? 'true' : 'false' }};
const searchUrl = "{{ route('deliveries.search') }}";
const storeUrl = "{{ route('deliveries.store') }}";
const baseUpdateUrl = "{{ url('/deliveries') }}";
let deliveryId = null;
let selectedBatch = null;

const attachmentContainer = document.getElementById("current_attachment_container");
const attachmentLink = document.getElementById("current_attachment_link");
const attachmentName = document.getElementById("current_attachment_name");

// Calculate row amount
function calculateRowAmount(row) {
    const qtyInput = row.querySelector('.qty-input');
    const priceCell = row.querySelector('.price-cell');
    const amountInput = row.querySelector('.amount-input');

    if (qtyInput && priceCell && amountInput) {
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceCell.innerText) || 0;
        amountInput.value = (qty * price).toFixed(2);
    }
}

// ✅ FIXED: Single quantity change handler with validation
function handleQuantityChange(e) {
    const input = e.target;
    const row = input.closest('tr');
    const maxQty = parseFloat(input.getAttribute('data-max')) || 0;
    const currentQty = parseFloat(input.value) || 0;
    
    // Prevent quantity from exceeding original SO quantity
    if (currentQty > maxQty) {
        Swal.fire({
            icon: 'warning',
            title: 'Quantity Limit Exceeded',
            text: `Quantity cannot exceed the original Sales Order quantity of ${maxQty}. You can only decrease the quantity.`,
            showConfirmButton: true
        });
        input.value = maxQty;
    }
    
    // Visual feedback if at max
    if (currentQty === maxQty) {
        input.classList.add('border-yellow-500');
    } else {
        input.classList.remove('border-yellow-500');
    }
    
    calculateRowAmount(row);
}

// Attach listeners to quantity inputs
function attachQuantityListeners() {
    if (!canManageDeliveries) return;
    const tbody = document.getElementById('items_tbody');
    tbody.querySelectorAll('.qty-input').forEach(input => {
        input.removeEventListener('input', handleQuantityChange);
        input.addEventListener('input', handleQuantityChange);
    });
}

// ✅ Show batch selector
function showBatchSelector(batches) {
    const container = document.getElementById('batch_selector_container');
    const select = document.getElementById('delivery_batch_select');
    
    select.innerHTML = '<option value="">-- Select Delivery Batch --</option>';
    
    batches.forEach(batch => {
        const option = document.createElement('option');
        option.value = batch.batch_id;
        
        let batchLabel = batch.batch_name;
        if (batch.delivery_date) {
            const date = new Date(batch.delivery_date);
            batchLabel += ` (Delivery: ${date.toLocaleDateString()})`;
        }
        
        option.textContent = batchLabel;
        select.appendChild(option);
    });
    
    container.classList.remove('hidden');
    
    Swal.fire({
        icon: 'info',
        title: 'Multiple Delivery Batches',
        text: 'This Sales Order has multiple delivery batches. Please select one to continue.',
        confirmButtonText: 'OK'
    });
}

// ✅ Handle batch selection
document.getElementById('delivery_batch_select').addEventListener('change', function() {
    selectedBatch = this.value;
    if (selectedBatch) {
        document.getElementById("search_btn").click();
    }
});

// ✅ FIXED SEARCH FUNCTION
document.getElementById("search_btn").addEventListener("click", async () => {
    const soNumber = document.getElementById("so_search").value.trim();
    
    console.log('🔍 Search initiated for:', soNumber);
    
    if (!soNumber) {
        Swal.fire("Oops!", "Please enter a Sales Order Number.", "warning");
        return;
    }

    try {
        // Construct URL properly
        const url = `${searchUrl}?so_number=${encodeURIComponent(soNumber)}`;
        const finalUrl = selectedBatch 
            ? `${url}&delivery_batch=${encodeURIComponent(selectedBatch)}`
            : url;

        console.log('📡 Fetching URL:', finalUrl);

        const response = await fetch(finalUrl, {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            }
        });

        console.log('📥 Response status:', response.status);

        const data = await response.json();
        console.log('📦 Response data:', data);

        if (!response.ok) {
            Swal.fire("Not Found", data.error || "Sales Order not found.", "error");
            return;
        }

        // Handle multiple batches response
        if (data.multiple_batches) {
            showBatchSelector(data.batches);
            document.getElementById("items_tbody").innerHTML = "";
            return;
        }

        // Hide batch selector if showing single batch/selected batch
        if (!data.has_multiple_batches || selectedBatch) {
            document.getElementById('batch_selector_container').classList.add('hidden');
        }
        
        // Store delivery batch
        document.getElementById('delivery_batch').value = data.delivery_batch || '';
        
        deliveryId = data.id;

        // Populate header fields
        const headerFields = [
            'sales_order_number', 'customer_code', 'customer_name', 'branch', 'tin_no',
            'sales_representative', 'sales_executive', 'po_number', 'request_delivery_date',
            'approved_by', 'plate_no', 'sales_invoice_no', 'dr_no', 'status', 'additional_instructions'
        ];

        headerFields.forEach(field => {
            const el = document.getElementById(field);
            if (!el) {
                console.warn(`⚠️ Element not found: ${field}`);
                return;
            }
            
            if (field === 'request_delivery_date' && data[field]) {
                el.value = new Date(data[field]).toISOString().split('T')[0];
            } else if (field === 'status') {
                el.value = data[field] || 'Delivered';
            } else if (field === 'approved_by') {
                if (data[field]) el.value = data[field];
            } else {
                el.value = data[field] || '';
            }
        });

        // Handle delivery_type display
        if (data.delivery_type) {
            const deliveryType = data.delivery_type;
            const deliveryTypeText = deliveryType === 'Partial' ? 'Partial Order' : 'Full Delivery';
            const badgeColor = deliveryType === 'Partial' 
                ? 'bg-blue-600/20 text-blue-400 border-blue-600' 
                : 'bg-green-600/20 text-green-400 border-green-600';
            
            document.getElementById('delivery_type_text').textContent = deliveryTypeText;
            document.getElementById('delivery_type').value = deliveryType;
            
            const badge = document.getElementById('delivery_type_badge');
            badge.textContent = deliveryType;
            badge.className = `px-2 py-1 rounded text-xs border ${badgeColor}`;
        }

        // Handle attachment
        if (data.attachment) {
            attachmentContainer.classList.remove('hidden');
            attachmentLink.href = `/delivery_images/${data.attachment}`;
            attachmentName.textContent = data.attachment;
        } else {
            attachmentContainer.classList.add('hidden');
        }
        document.getElementById("attachment").value = '';

        // Populate items table
        const tbody = document.getElementById("items_tbody");
        tbody.innerHTML = "";

        if (data.items && data.items.length > 0) {
            console.log(`📋 Loading ${data.items.length} items`);
            
            data.items.forEach((item, index) => {
                const tr = document.createElement("tr");
                tr.classList.add("hover:bg-gray-800/70");
                tr.setAttribute('data-original-qty', item.original_quantity || item.quantity);
                
                tr.innerHTML = `
                    <td class="border border-gray-700 px-4 py-2">${item.item_code || '—'}</td>
                    <td class="border border-gray-700 px-4 py-2">${item.item_description || '—'}</td>
                    <td class="border border-gray-700 px-4 py-2">${item.brand || '—'}</td>
                    <td class="border border-gray-700 px-4 py-2">${item.item_category || '—'}</td>
                    <td class="border border-gray-700 px-4 py-2">
                        <input type="number" 
                            class="qty-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-1" 
                            value="${item.quantity}" 
                            step="0.01" 
                            max="${item.original_quantity || item.quantity}"
                            data-max="${item.original_quantity || item.quantity}"
                            ${canManageDeliveries ? '' : 'readonly'}>
                        <div class="text-xs text-gray-400 mt-1">Max: ${item.original_quantity || item.quantity}</div>
                    </td>
                    <td class="border border-gray-700 px-4 py-2">${item.uom || '—'}</td>
                    <td class="border border-gray-700 px-4 py-2 price-cell">${item.unit_price || 0}</td>
                    <td class="border border-gray-700 px-4 py-2">
                        <input type="number" 
                            class="amount-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-1" 
                            value="${((item.quantity || 0) * (item.unit_price || 0)).toFixed(2)}" 
                            readonly>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            attachQuantityListeners();
        } else {
            console.warn('⚠️ No items found in response');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-400">No items found</td></tr>';
        }

        let successMsg = deliveryId ? 'Existing delivery loaded for editing.' : 'Sales Order loaded successfully.';
        if (data.delivery_batch) {
            successMsg += ` (Batch: ${data.delivery_batch})`;
        }

        Swal.fire({
            icon: 'success',
            title: 'Found!',
            text: successMsg,
            showConfirmButton: false,
            timer: 2000
        });

    } catch (err) {
        console.error('💥 FETCH ERROR:', err);
        Swal.fire("Error", `Network error while searching: ${err.message}`, "error");
    }
});

// ✅ Press Enter to search
document.getElementById("so_search").addEventListener("keypress", function(e) {
    if (e.key === 'Enter') {
        document.getElementById("search_btn").click();
    }
});

// 💾 Save Delivery
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

        // Validate quantities before saving
        const tbody = document.getElementById("items_tbody");
        let quantityError = false;
        
        tbody.querySelectorAll("tr").forEach(row => {
            const qtyInput = row.querySelector('.qty-input');
            if (!qtyInput) return;
            
            const maxQty = parseFloat(qtyInput.getAttribute('data-max')) || 0;
            const currentQty = parseFloat(qtyInput.value) || 0;
            
            if (currentQty > maxQty) {
                quantityError = true;
            }
        });
        
        if (quantityError) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Quantities',
                text: 'One or more items exceed the original Sales Order quantity. Please adjust before saving.',
                showConfirmButton: true
            });
            return;
        }

        const payload = {
            sales_order_number: document.getElementById("sales_order_number").value.trim(),
            delivery_batch: document.getElementById("delivery_batch").value.trim() || null,
            dr_no: drNo,
            customer_name: document.getElementById("customer_name").value.trim() || null,
            tin_no: document.getElementById("tin_no").value.trim() || null,
            branch: document.getElementById("branch").value.trim() || null,
            sales_representative: document.getElementById("sales_representative").value.trim() || null,
            sales_executive: document.getElementById("sales_executive").value.trim() || null,
            po_number: document.getElementById("po_number").value.trim() || null,
            request_delivery_date: document.getElementById("request_delivery_date").value || null,
            delivery_type: document.getElementById("delivery_type").value,
            plate_no: document.getElementById("plate_no").value.trim() || null,
            sales_invoice_no: document.getElementById("sales_invoice_no").value.trim() || null,
            approved_by: document.getElementById("approved_by").value.trim() || null,
            status: document.getElementById("status").value,
            additional_instructions: document.getElementById("additional_instructions").value.trim() || null,
            items: []
        };

        tbody.querySelectorAll("tr").forEach(row => {
            const tds = row.querySelectorAll("td");
            if (tds.length < 8) return;
            
            payload.items.push({
                item_code: tds[0].innerText || '',
                item_description: tds[1].innerText || '',
                brand: tds[2].innerText || '',
                item_category: tds[3].innerText || '',
                quantity: parseFloat(tds[4].querySelector("input").value) || 0,
                uom: tds[5].innerText || '',
                unit_price: parseFloat(tds[6].innerText) || 0,
                total_amount: parseFloat(tds[7].querySelector("input").value) || 0
            });
        });

        try {
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

            if(response.ok && data.success){
                Swal.fire({
                    icon:'success',
                    title: deliveryId ? 'Updated!' : 'Created!',
                    text: data.message || 'Delivery saved successfully!',
                    showConfirmButton:false,
                    timer:1500
                }).then(() => window.location.href = "{{ route('deliveries.index') }}");
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
            Swal.fire("Error","Network or server error while saving.","error");
        }
    });
}
</script>
@endsection