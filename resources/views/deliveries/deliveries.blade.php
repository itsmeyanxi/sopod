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

    <!-- 🧾 Sales Order Information -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-1">Sales Order Information</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach([
                'sales_order_number' => 'Sales Order Number',
                'customer_code' => 'Customer Code',
                'customer_name' => 'Customer Name',
                'tin' => 'Tin',
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
                'sales_invoice_no' => 'Sales Invoice No',
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
                            {{ \App\Helpers\RoleHelper::canManageDeliveries() ? '' : 'readonly' }}>
                    @endif
                </div>
            @endforeach

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

// Attach listeners to quantity inputs
function attachQuantityListeners() {
    if (!canManageDeliveries) return;
    const tbody = document.getElementById('items_tbody');
    tbody.querySelectorAll('.qty-input').forEach(input => {
        input.removeEventListener('input', handleQuantityChange);
        input.addEventListener('input', handleQuantityChange);
    });
}

function handleQuantityChange(e) {
    const row = e.target.closest('tr');
    calculateRowAmount(row);
}

// 🔍 Search
document.getElementById("search_btn").addEventListener("click", async () => {
    const soNumber = document.getElementById("so_search").value.trim();
    if (!soNumber) {
        Swal.fire("Oops!", "Please enter a Sales Order Number.", "warning");
        return;
    }

    try {
        const response = await fetch(`${searchUrl}?so_number=${encodeURIComponent(soNumber)}`, {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (!response.ok) {
            Swal.fire("Not Found", data.error || "Sales Order not found.", "error");
            return;
        }

        deliveryId = data.id;

        // Populate headers
        const headerFields = [
            'sales_order_number', 'customer_code', 'customer_name', 'branch', 'tin',
            'sales_representative', 'sales_executive', 'po_number', 'request_delivery_date',
            'approved_by', 'plate_no', 'sales_invoice_no', 'dr_no', 'status', 'additional_instructions'
        ];

        headerFields.forEach(field => {
            const el = document.getElementById(field);
            if (!el) return;
            if (field === 'request_delivery_date' && data[field]) {
                el.value = new Date(data[field]).toISOString().split('T')[0];
            } else {
                el.value = data[field] || '';
            }
        });

        // Handle attachment
        if (data.attachment) {
            attachmentContainer.classList.remove('hidden');
            attachmentLink.href = `/delivery_images/${data.attachment}`;
            attachmentName.textContent = data.attachment;
        } else {
            attachmentContainer.classList.add('hidden');
        }
        document.getElementById("attachment").value = '';

        // Populate items
        const tbody = document.getElementById("items_tbody");
        tbody.innerHTML = "";

        if (data.items && data.items.length > 0) {
            data.items.forEach(item => {
                const tr = document.createElement("tr");
                tr.classList.add("hover:bg-gray-800/70");
                tr.innerHTML = `
                    <td class="border border-gray-700 px-4 py-2">${item.item_code}</td>
                    <td class="border border-gray-700 px-4 py-2">${item.item_description}</td>
                    <td class="border border-gray-700 px-4 py-2">${item.brand}</td>
                    <td class="border border-gray-700 px-4 py-2">${item.item_category}</td>
                    <td class="border border-gray-700 px-4 py-2">
                        <input type="number" class="qty-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-1" value="${item.quantity}" ${canManageDeliveries ? '' : 'readonly'}>
                    </td>
                    <td class="border border-gray-700 px-4 py-2">${item.uom}</td>
                    <td class="border border-gray-700 px-4 py-2 price-cell">${item.unit_price}</td>
                    <td class="border border-gray-700 px-4 py-2">
                        <input type="number" class="amount-input w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md p-1" value="${(item.quantity * item.unit_price).toFixed(2)}" readonly>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            attachQuantityListeners();
        }

        Swal.fire({
            icon: 'success',
            title: 'Found!',
            text: deliveryId ? 'Existing delivery loaded for editing.' : 'Sales Order loaded successfully.',
            showConfirmButton: false,
            timer: 1500
        });

    } catch (err) {
        console.error('FETCH ERROR:', err);
        Swal.fire("Error", "Network error while searching.", "error");
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

        // ✅ NEW: Validate DR No is filled
        const drNo = document.getElementById("dr_no").value.trim();
        if(!drNo) {
            Swal.fire("Required Field", "Please enter a DR Number before saving.", "warning");
            return;
        }

        // ✅ NEW: Validate Sales Invoice No is filled
        const salesInvoiceNo = document.getElementById("sales_invoice_no").value.trim();
        if(!salesInvoiceNo) {
            Swal.fire("Required Field", "Please enter a Sales Invoice Number before saving.", "warning");
            return;
        }

        const payload = {
            sales_order_number: document.getElementById("sales_order_number").value.trim(),
            dr_no: drNo,  // ✅ CHANGED: Use validated drNo variable
            customer_name: document.getElementById("customer_name").value.trim() || null,
            tin: document.getElementById("tin").value.trim() || null,
            branch: document.getElementById("branch").value.trim() || null,
            sales_representative: document.getElementById("sales_representative").value.trim() || null,
            sales_executive: document.getElementById("sales_executive").value.trim() || null,
            po_number: document.getElementById("po_number").value.trim() || null,
            request_delivery_date: document.getElementById("request_delivery_date").value || null,
            plate_no: document.getElementById("plate_no").value.trim() || null,
            sales_invoice_no: salesInvoiceNo,  // ✅ CHANGED: Use validated salesInvoiceNo variable
            approved_by: document.getElementById("approved_by").value.trim() || null,
            status: document.getElementById("status").value,
            additional_instructions: document.getElementById("additional_instructions").value.trim() || null,
            items: []
        };

        const tbody = document.getElementById("items_tbody");
        tbody.querySelectorAll("tr").forEach(row => {
            const tds = row.querySelectorAll("td");
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
