@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <!-- Header -->
    <h1 class="text-2xl font-bold mb-6 border-b border-gray-700 pb-2">
        Sales Order Creation
    </h1>

    @if ($errors->any())
    <div class="bg-red-500 text-white p-3 rounded mb-4">
        <strong>Whoops!</strong> Please fix the following errors:
        <ul class="list-disc list-inside mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form Wrapper -->
    <form action="{{ route('sales_orders.store') }}" method="POST" onsubmit="return validateForm()">
        @csrf

        <!-- Sales Order Number -->
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2">Sales Order Number:</label>
            <input 
                type="text" 
                name="sales_order_number"
                value="{{ $nextNumber ?? 'SO-0001' }}" 
                readonly
                class="w-64 bg-gray-800 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-500"
            >
        </div>

        <!-- ================= CUSTOMER DETAILS SECTION ================= -->
        <div class="bg-gray-800 text-white p-6 rounded-xl shadow-md mb-8">
            <h2 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-1">Customer Information</h2>

            <div class="mb-4">
                <label for="customer_code" class="block text-sm font-medium text-gray-300 mb-1">Customer Code <span class="text-red-500">*</span></label>
                <select id="customer_code" name="customer_code" required
                    class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Select Customer Code --</option>
                    @foreach($customers as $customer)
                        @if(strtolower($customer->status) === 'enabled')
                            <option value="{{ $customer->customer_code }}">
                                {{ $customer->customer_code }} - {{ $customer->customer_name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-300 mb-1">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" readonly
                        class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                    <label for="business_style" class="block text-sm font-medium text-gray-300 mb-1">Business Style</label>
                    <input type="text" id="business_style" name="business_style" readonly
                        class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500" />
                </div>
            </div>

            <div class="mt-4">
                <label for="billing_address" class="block text-sm font-medium text-gray-300 mb-1">Billing Address</label>
                <input type="text" id="billing_address" name="billing_address" readonly
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div class="mt-4">
                <label for="tin" class="block text-sm font-medium text-gray-300 mb-1">TIN</label>
                <input type="text" id="tin" name="tin" readonly
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div class="mt-4">
                <label for="shipping_address" class="block text-sm font-medium text-gray-300 mb-1">Shipping Address</label>
                <input type="text" id="shipping_address" name="shipping_address" readonly
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div class="mb-4">
                <label for="sales_executive" class="block text-sm font-medium text-gray-300 mb-1">Sales Executive</label>
                <input type="text" id="sales_executive" name="sales_executive" readonly
                  class=" w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="mt-4">
                <label for="additional_instructions" class="block text-sm font-medium text-gray-300 mb-1">Additional Delivery Instructions</label>
                <textarea id="additional_instructions" name="additional_instructions" rows="2"
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
        </div>
        <!-- ================= END CUSTOMER DETAILS ================= -->

        <!-- ================= ORDER DETAILS SECTION ================= -->
        <div class="bg-gray-800 p-6 rounded-lg mb-8">
            <h2 class="text-lg font-semibold mb-3 border-b border-gray-700 pb-1">Order Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm">Request Delivery Date <span class="text-red-500">*</span></label>
                    <input type="date" id="request_delivery_date" name="request_delivery_date" required
                        class="w-full bg-gray-900 text-white border border-gray-700 rounded px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm">PO Reference No <span class="text-red-500">*</span></label>
                    <input type="text" id="po_reference_no" name="po_reference_no" required
                        class="w-full bg-gray-900 text-white border border-gray-700 rounded px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm">Sales Representative <span class="text-red-500">*</span></label>
                    <input type="text" id="sales_representative" name="sales_representative" required
                        class="w-full bg-gray-900 text-white border border-gray-700 rounded px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm">Branch <span class="text-red-500">*</span></label>
                    <input type="text" id="branch" name="branch" required
                        class="w-full bg-gray-900 text-white border border-gray-700 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>
        <!-- ================= END ORDER DETAILS ================= -->

        <!-- ================= ITEM TABLE ================= -->
        <div class="bg-gray-900 text-white p-6 rounded-xl mt-8">
            <h2 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Items <span class="text-red-500">*</span></h2>

            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-700 rounded-lg text-left" id="itemsTable">
                    <thead class="bg-gray-800 text-gray-300">
                        <tr>
                            <th class="px-3 py-2 border border-gray-700">Item Description</th>
                            <th class="px-3 py-2 border border-gray-700">Item Code</th>
                            <th class="px-3 py-2 border border-gray-700">Item Category</th>
                            <th class="px-3 py-2 border border-gray-700">Brand</th>
                            <th class="px-3 py-2 border border-gray-700">Quantity</th>
                            <th class="px-3 py-2 border border-gray-700">UOM (kgs)</th>
                            <th class="px-3 py-2 border border-gray-700">Unit Selling Price</th>
                            <th class="px-3 py-2 border border-gray-700">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-2 border border-gray-700">
                                <select 
                                    name="items[0][item_id]" 
                                    class="item-description w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white"
                                    required>
                                   <option value="">-- Select Item --</option>
                                    @foreach($items as $item)
                                        @if($item->is_enabled && $item->approval_status === 'approved')
                                            <option 
                                                value="{{ $item->id }}" 
                                                data-description="{{ $item->item_description }}"
                                                data-code="{{ $item->item_code }}" 
                                                data-category="{{ $item->item_category }}" 
                                                data-brand="{{ $item->brand }}" 
                                                data-price="{{ $item->unit_price }}">
                                                   {{ $item->item_description ?? '' }}  -  {{ $item->brand ?? '' }}     
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <!-- Hidden fields for item details -->
                                <input type="hidden" name="items[0][item_description]" class="item-description-hidden">
                                <input type="hidden" name="items[0][item_code]" class="item-code-hidden">
                                <input type="hidden" name="items[0][item_category]" class="item-category-hidden">
                                <input type="hidden" name="items[0][brand]" class="item-brand-hidden">
                            </td>
                            <td class="px-2 py-2 border border-gray-700">
                                <input type="text" name="items[0][code_display]" class="item-code w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
                            </td>
                            <td class="px-2 py-2 border border-gray-700">
                                <input type="text" name="items[0][category_display]" class="item-category w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
                            </td>
                            <td class="px-2 py-2 border border-gray-700">
                                <input type="text" name="items[0][brand_display]" class="item-brand w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
                            </td>
                            <td class="px-2 py-2 border border-gray-700">
                                <input type="number" name="items[0][quantity]" class="item-quantity w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white" min="0.01" step="0.01" required>
                            </td>
                            <td class="px-2 py-2 border border-gray-700">
                                <input type="text" name="items[0][unit]" value="Kgs" class="item-unit w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400 text-center" readonly>
                            </td>
                            <td class="px-2 py-2 border border-gray-700">
                                <input type="number" name="items[0][price]" class="item-price w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white" step="0.01" required>
                            </td>
                            <td class="px-2 py-2 border border-gray-700">
                                <input type="text" name="items[0][amount]" class="item-amount w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-start space-y-4 mt-4">
                <div class="flex space-x-2">
                    <button type="button" id="RemoveRowBtn" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded">
                        – Remove
                    </button>
                    <button type="button" id="addRowBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
                        + Add
                    </button>
                </div>
            </div>
        </div>
        <!-- ================= END ITEM TABLE ================= -->

        <!-- Submit -->
        <div class="mt-8">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded">
                Save Sales Order
            </button>       
        </div>
    </form>
</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
@if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 2000
    });
@endif

@if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '{{ session('error') }}',
        showConfirmButton: true
    });
@endif
</script>

<!-- ================= SCRIPTS ================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ================= FORM VALIDATION =================
    window.validateForm = function() {
        const customerCode = document.getElementById('customer_code').value;
        if (!customerCode) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please select a customer.',
                showConfirmButton: true
            });
            return false;
        }

        const rows = document.querySelectorAll('#itemsTable tbody tr');
        let hasValidItem = false;
        
        rows.forEach(row => {
            const itemSelect = row.querySelector('.item-description');
            const qty = row.querySelector('.item-quantity').value;
            const price = row.querySelector('.item-price').value;
            
            if (itemSelect.value && qty && price && parseFloat(qty) > 0 && parseFloat(price) >= 0) {
                hasValidItem = true;
            }
        });
        
        if (!hasValidItem) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please add at least one item with description, quantity, and price.',
                showConfirmButton: true
            });
            return false;
        }
        
        return true;
    };

    // ================= AUTOFILL CUSTOMER =================
    document.getElementById('customer_code').addEventListener('change', function () {
        const code = this.value;
        if (!code) return;

        fetch(`/customers/get/${code}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Customer not found!',
                        showConfirmButton: true
                    });
                    return;
                }

                document.getElementById('customer_name').value = data.customer_name || '';
                document.getElementById('business_style').value = data.business_style || '';
                document.getElementById('billing_address').value = data.billing_address || '';
                document.getElementById('tin').value = data.tin || '';
                document.getElementById('shipping_address').value = data.shipping_address || '';
                document.getElementById('sales_executive').value = data.sales_executive || '';
            })
            .catch(err => console.error(err));
    });

    // ================= ADD NEW ITEM ROW =================
    document.getElementById("addRowBtn").addEventListener("click", () => {
        const table = document.querySelector("#itemsTable tbody");
        const rowCount = table.querySelectorAll("tr").length;
        const newRow = document.createElement("tr");

        newRow.innerHTML = `
            <td class="border border-gray-700 px-2 py-1">
                <select 
                    name="items[${rowCount}][item_id]" 
                    class="item-description w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white"
                    required>
                    <option value="">-- Select Item --</option>
                    @foreach($items as $item)
                        @if($item->is_enabled && $item->approval_status === 'approved')
                            <option 
                                value="{{ $item->id }}" 
                                data-description="{{ $item->item_description }}"
                                data-code="{{ $item->item_code }}" 
                                data-category="{{ $item->item_category }}" 
                                data-brand="{{ $item->brand }}" 
                                data-price="{{ $item->unit_price }}">
                                   {{ $item->item_description ?? '' }} -  {{ $item->brand ?? '' }} 
                            </option>
                        @endif
                    @endforeach
                </select>
                <input type="hidden" name="items[${rowCount}][item_description]" class="item-description-hidden">
                <input type="hidden" name="items[${rowCount}][item_code]" class="item-code-hidden">
                <input type="hidden" name="items[${rowCount}][item_category]" class="item-category-hidden">
                <input type="hidden" name="items[${rowCount}][brand]" class="item-brand-hidden">
            </td>
            <td class="border border-gray-700 px-2 py-1">
                <input type="text" name="items[${rowCount}][code_display]" class="item-code w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
            </td>
            <td class="border border-gray-700 px-2 py-1">
                <input type="text" name="items[${rowCount}][category_display]" class="item-category w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
            </td>
            <td class="border border-gray-700 px-2 py-1">
                <input type="text" name="items[${rowCount}][brand_display]" class="item-brand w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
            </td>
            <td class="border border-gray-700 px-2 py-1">
                <input type="number" name="items[${rowCount}][quantity]" class="item-quantity w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white" min="0.01" step="0.01" required>
            </td>
            <td class="border border-gray-700 px-2 py-1">
                <input type="text" name="items[${rowCount}][unit]" value="Kgs" class="item-unit w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400 text-center" readonly>
            </td>
            <td class="border border-gray-700 px-2 py-1">
                <input type="number" name="items[${rowCount}][price]" class="item-price w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white" step="0.01" required>
            </td>
            <td class="border border-gray-700 px-2 py-1">
                <input type="text" name="items[${rowCount}][amount]" class="item-amount w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-gray-400" readonly>
            </td>
        `;
        table.appendChild(newRow);
    });

    // ================= REMOVE ROW =================
    document.getElementById("RemoveRowBtn").addEventListener("click", () => {
        const table = document.querySelector("#itemsTable tbody");
        const rows = table.querySelectorAll("tr");
        if (rows.length > 1) {
            table.removeChild(rows[rows.length - 1]);
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'You must keep at least one item row.',
                showConfirmButton: true
            });
        }
    });

    // ================= ITEM AUTOFILL + CALCULATION ================= 
    const table = document.getElementById('itemsTable');

    table.querySelector('tbody').addEventListener('change', function(e) {
        if (e.target.classList.contains('item-description')) {
            const selected = e.target.options[e.target.selectedIndex];
            const row = e.target.closest('tr');

            const description = selected.getAttribute('data-description') || '';
            const code = selected.getAttribute('data-code') || '';
            const category = selected.getAttribute('data-category') || '';
            const brand = selected.getAttribute('data-brand') || '';
            const price = selected.getAttribute('data-price') || '';

            // Store in hidden fields
            row.querySelector('.item-description-hidden').value = description;
            row.querySelector('.item-code-hidden').value = code;
            row.querySelector('.item-category-hidden').value = category;
            row.querySelector('.item-brand-hidden').value = brand;
            
            // Update visible readonly fields
            row.querySelector('.item-code').value = code;
            row.querySelector('.item-category').value = category;
            row.querySelector('.item-brand').value = brand;
            row.querySelector('.item-price').value = price;

            const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
            row.querySelector('.item-amount').value = (qty * parseFloat(price)).toFixed(2);
        }
    });

    table.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-price')) {
            const row = e.target.closest('tr');
            const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            row.querySelector('.item-amount').value = (qty * price).toFixed(2);
        }
    });
});
</script>

@endsection