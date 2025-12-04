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

            <!-- Searchable Customer Code Dropdown -->
        <div class="mb-4">
            <label for="customer_code" class="block text-sm font-medium text-gray-300 mb-1">
                Customer Code <span class="text-red-500">*</span>
            </label>
            <div class="relative customer-search-container">
                <div class="relative">
                    <input 
                        type="text" 
                        id="customer_search_input"
                        class="w-full bg-gray-900 border-2 border-gray-700 rounded-lg px-3 py-2 pr-10 text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none transition-colors"
                        placeholder="Search by code or name..."
                        autocomplete="off">
                    <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <!-- Dropdown Container -->
                <div id="customer_dropdown" class="absolute z-[9999] w-full bg-gray-800 border-2 border-gray-600 rounded-lg mt-1 shadow-2xl hidden max-h-80 overflow-y-auto">
                    <div class="sticky top-0 bg-gray-700 px-3 py-2 text-xs text-gray-300 font-semibold border-b border-gray-600">
                        Select a customer
                    </div>
                    @foreach($customers as $customer)
                        @if(strtolower($customer->status) === 'enabled')
                            <div 
                                class="customer-option px-4 py-3 hover:bg-blue-600 cursor-pointer text-white border-b border-gray-700 last:border-b-0 transition-colors"
                                data-code="{{ $customer->customer_code }}"
                                data-name="{{ $customer->customer_name }}"
                                data-search="{{ strtolower($customer->customer_code . ' ' . $customer->customer_name) }}">
                                <div class="font-semibold text-base mb-1">{{ $customer->customer_code }}</div>
                                <div class="text-sm text-gray-300">{{ $customer->customer_name }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <!-- Hidden input to store the selected customer code -->
            <input type="hidden" id="customer_code" name="customer_code" required>
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
                <label for="tin_no" class="block text-sm font-medium text-gray-300 mb-1">TIN</label>
                <input type="text" id="tin_no" name="tin_no" readonly
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div class="mt-4">
                <label for="shipping_address" class="block text-sm font-medium text-gray-300 mb-1">Shipping Address</label>
                <input type="text" id="shipping_address" name="shipping_address" readonly
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div class="mb-4">
                <label for="sales_rep" class="block text-sm font-medium text-gray-300 mb-1">Sales Representative</label>
                <input type="text" id="sales_rep" name="sales_rep" readonly
                  class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg focus:border-blue-500 focus:ring-blue-500">
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
                    <label class="block text-sm">Sales Executive <span class="text-red-500">*</span></label>
                    <input type="text" id="sales_executive" name="sales_executive" required
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
        <div class="bg-gray-900 text-white p-6 rounded-xl mt-8" style="overflow: visible;">
            <h2 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Items <span class="text-red-500">*</span></h2>

            <div style="overflow: visible; position: relative;">
                <table class="min-w-full border border-gray-700 rounded-lg text-left" id="itemsTable" style="position: relative;">
                    <thead class="bg-gray-800 text-gray-300">
                        <tr>
                            <th class="px-3 py-2 border border-gray-700" style="position: relative;">Item Description</th>
                            <th class="px-3 py-2 border border-gray-700">Item Code</th>
                            <th class="px-3 py-2 border border-gray-700">Item Category</th>
                            <th class="px-3 py-2 border border-gray-700">Brand</th>
                            <th class="px-3 py-2 border border-gray-700">Quantity</th>
                            <th class="px-3 py-2 border border-gray-700">UOM (kgs)</th>
                            <th class="px-3 py-2 border border-gray-700">Unit Selling Price</th>
                            <th class="px-3 py-2 border border-gray-700">Amount</th>
                            <th class="px-3 py-2 border border-gray-700">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-2 border border-gray-700" style="position: relative; overflow: visible;">
                                <div class="relative item-search-container" style="position: relative;">
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            class="item-search w-full bg-gray-800 border-2 border-gray-600 rounded-lg px-3 py-2 pr-10 text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none transition-colors"
                                            placeholder="Type to search items..."
                                            autocomplete="off">
                                        <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="item-dropdown absolute z-[9999] w-full bg-gray-800 border-2 border-gray-600 rounded-lg mt-1 shadow-2xl hidden max-h-80 overflow-y-auto" style="position: absolute; left: 0;">
                                        <div class="sticky top-0 bg-gray-700 px-3 py-2 text-xs text-gray-300 font-semibold border-b border-gray-600">
                                            Select an item
                                        </div>
                                        @foreach($items as $item)
                                            @if($item->is_enabled && $item->approval_status === 'approved')
                                                <div 
                                                    class="item-option px-4 py-3 hover:bg-blue-600 cursor-pointer text-white border-b border-gray-700 last:border-b-0 transition-colors"
                                                    data-id="{{ $item->id }}"
                                                    data-description="{{ $item->item_description }}"
                                                    data-code="{{ $item->item_code }}" 
                                                    data-category="{{ $item->item_category }}" 
                                                    data-brand="{{ $item->brand }}" 
                                                    data-price="{{ $item->unit_price }}"
                                                    data-search="{{ strtolower($item->item_description . ' ' . $item->item_code . ' ' . $item->item_category . ' ' . $item->brand) }}">
                                                    <div class="font-semibold text-base mb-1">{{ $item->item_description ?? '' }}</div>
                                                    <div class="text-sm text-gray-300 flex items-center gap-3">
                                                        <span class="bg-gray-700 px-2 py-0.5 rounded text-xs">{{ $item->brand ?? '' }}</span>
                                                        <span class="text-gray-400">{{ $item->item_category ?? '' }}</span>
                                                        <span class="text-gray-500">Code: {{ $item->item_code ?? '' }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <!-- Hidden fields for item details -->
                                <input type="hidden" name="items[0][item_id]" class="item-id-hidden" required>
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
                            <td class="px-2 py-2 border border-gray-700">
                                <textarea 
                                    name="items[0][note]" 
                                    class="item-note w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white"
                                    rows="1"
                                    placeholder="Optional note..."
                                ></textarea>
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
        // Store items data globally for dynamic rows
        const itemsData = [
            @foreach($items as $item)
                @if($item->is_enabled && $item->approval_status === 'approved')
                    {
                        id: "{{ $item->id }}",
                        description: "{{ $item->item_description }}",
                        code: "{{ $item->item_code }}",
                        category: "{{ $item->item_category }}",
                        brand: "{{ $item->brand }}",
                        price: "{{ $item->unit_price }}",
                        search: "{{ strtolower($item->item_description . ' ' . $item->item_code . ' ' . $item->item_category . ' ' . $item->brand) }}"
                    },
                @endif
            @endforeach
        ];

        // ================= SEARCHABLE CUSTOMER DROPDOWN =================
        const customerSearchInput = document.getElementById('customer_search_input');
        const customerDropdown = document.getElementById('customer_dropdown');
        const customerCodeInput = document.getElementById('customer_code');
        let originalCustomerDropdownHTML = customerDropdown.innerHTML;

        // Show dropdown on focus
        customerSearchInput.addEventListener('focus', function() {
            customerDropdown.classList.remove('hidden');
            if (this.value === '') {
                const allOptions = customerDropdown.querySelectorAll('.customer-option');
                allOptions.forEach(opt => opt.style.display = 'block');
            }
        });

        // Filter customers as user types
        customerSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            if (searchTerm === '') {
                customerDropdown.innerHTML = originalCustomerDropdownHTML;
                rebindCustomerClicks();
                customerDropdown.classList.remove('hidden');
                return;
            }
            
            let visibleCount = 0;
            const options = customerDropdown.querySelectorAll('.customer-option');
            
            options.forEach(option => {
                const searchText = option.getAttribute('data-search');
                if (searchText.includes(searchTerm)) {
                    option.style.display = 'block';
                    visibleCount++;
                } else {
                    option.style.display = 'none';
                }
            });

            // Show "no results" message if nothing matches
            if (visibleCount === 0) {
                customerDropdown.innerHTML = `
                    <div class="px-4 py-8 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="font-medium">No customers found</div>
                        <div class="text-sm mt-1">Try a different search term</div>
                    </div>
                `;
            }
        });

        // Handle customer selection
        function rebindCustomerClicks() {
            const options = customerDropdown.querySelectorAll('.customer-option');
            options.forEach(option => {
                option.addEventListener('click', handleCustomerClick);
            });
        }

        function handleCustomerClick() {
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            
            // Update the search input to show selected customer
            customerSearchInput.value = code + ' - ' + name;
            
            // Set the hidden customer code value
            customerCodeInput.value = code;
            
            // Hide dropdown
            customerDropdown.classList.add('hidden');
            
            // Trigger customer autofill
            customerCodeInput.dispatchEvent(new Event('change'));
        }

        // Initial binding - THIS WAS MISSING!
        rebindCustomerClicks();

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!document.querySelector('.customer-search-container').contains(e.target)) {
                customerDropdown.classList.add('hidden');
            }
        });

        // Clear customer selection when input is cleared
        customerSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' || e.key === 'Delete') {
                if (this.value.length <= 1) {
                    customerCodeInput.value = '';
                    // Clear customer details
                    document.getElementById('customer_name').value = '';
                    document.getElementById('business_style').value = '';
                    document.getElementById('billing_address').value = '';
                    document.getElementById('tin_no').value = '';
                    document.getElementById('shipping_address').value = '';
                    document.getElementById('sales_rep').value = '';
                }
            }
        });

        // ================= SEARCHABLE ITEM DROPDOWN =================
        function initializeItemSearch(row) {
            const searchInput = row.querySelector('.item-search');
            const dropdown = row.querySelector('.item-dropdown');
            let originalDropdownHTML = dropdown.innerHTML;

            searchInput.addEventListener('focus', function() {
                dropdown.classList.remove('hidden');
                if (this.value === '') {
                    filterOptions('');
                }
            });

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                if (searchTerm === '') {
                    dropdown.innerHTML = originalDropdownHTML;
                    rebindOptionClicks();
                    dropdown.classList.remove('hidden');
                    return;
                }
                
                filterOptions(searchTerm);
                dropdown.classList.remove('hidden');
            });

            function filterOptions(searchTerm) {
                let visibleCount = 0;
                const options = dropdown.querySelectorAll('.item-option');
                
                options.forEach(option => {
                    const searchText = option.getAttribute('data-search');
                    if (searchText.includes(searchTerm)) {
                        option.style.display = 'block';
                        visibleCount++;
                    } else {
                        option.style.display = 'none';
                    }
                });

                if (visibleCount === 0) {
                    dropdown.innerHTML = '<div class="px-4 py-8 text-center text-gray-400"><svg class="w-12 h-12 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><div class="font-medium">No items found</div><div class="text-sm mt-1">Try a different search term</div></div>';
                }
            }

            function rebindOptionClicks() {
                const options = dropdown.querySelectorAll('.item-option');
                options.forEach(option => {
                    option.addEventListener('click', handleOptionClick);
                });
            }

            function handleOptionClick() {
                const id = this.getAttribute('data-id');
                const description = this.getAttribute('data-description');
                const code = this.getAttribute('data-code');
                const category = this.getAttribute('data-category');
                const brand = this.getAttribute('data-brand');
                const price = this.getAttribute('data-price');

                searchInput.value = description + ' - ' + brand;

                row.querySelector('.item-id-hidden').value = id;
                row.querySelector('.item-description-hidden').value = description;
                row.querySelector('.item-code-hidden').value = code;
                row.querySelector('.item-category-hidden').value = category;
                row.querySelector('.item-brand-hidden').value = brand;

                row.querySelector('.item-code').value = code;
                row.querySelector('.item-category').value = category;
                row.querySelector('.item-brand').value = brand;
                row.querySelector('.item-price').value = price;

                const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
                row.querySelector('.item-amount').value = (qty * parseFloat(price)).toFixed(2);

                dropdown.classList.add('hidden');
            }

            rebindOptionClicks();

            document.addEventListener('click', function(e) {
                if (!row.querySelector('.item-search-container').contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        initializeItemSearch(document.querySelector('#itemsTable tbody tr'));

        // ================= FORM VALIDATION (FIXED - NO DUPLICATES) =================
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
                const itemId = row.querySelector('.item-id-hidden').value;
                const qty = row.querySelector('.item-quantity').value;
                const price = row.querySelector('.item-price').value;
                
                if (itemId && qty && price && parseFloat(qty) > 0 && parseFloat(price) >= 0) {
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
                    document.getElementById('tin_no').value = data.tin_no || '';
                    document.getElementById('shipping_address').value = data.shipping_address || '';
                    document.getElementById('sales_rep').value = data.sales_rep || '';
                })
                .catch(err => console.error(err));
        });

        // ================= ADD NEW ITEM ROW =================
        document.getElementById("addRowBtn").addEventListener("click", () => {
            const table = document.querySelector("#itemsTable tbody");
            const rowCount = table.querySelectorAll("tr").length;
            const newRow = document.createElement("tr");

            // Build dropdown HTML from items data
            let dropdownHTML = '<div class="sticky top-0 bg-gray-700 px-3 py-2 text-xs text-gray-300 font-semibold border-b border-gray-600">Select an item</div>';
            itemsData.forEach(item => {
                dropdownHTML += `
                    <div 
                        class="item-option px-4 py-3 hover:bg-blue-600 cursor-pointer text-white border-b border-gray-700 last:border-b-0 transition-colors"
                        data-id="${item.id}"
                        data-description="${item.description}"
                        data-code="${item.code}" 
                        data-category="${item.category}" 
                        data-brand="${item.brand}" 
                        data-price="${item.price}"
                        data-search="${item.search}">
                        <div class="font-semibold text-base mb-1">${item.description}</div>
                        <div class="text-sm text-gray-300 flex items-center gap-3">
                            <span class="bg-gray-700 px-2 py-0.5 rounded text-xs">${item.brand}</span>
                            <span class="text-gray-400">${item.category}</span>
                            <span class="text-gray-500">Code: ${item.code}</span>
                        </div>
                    </div>
                `;
            });

            newRow.innerHTML = `
                <td class="border border-gray-700 px-2 py-1">
                    <div class="relative item-search-container">
                        <div class="relative">
                            <input 
                                type="text" 
                                class="item-search w-full bg-gray-800 border-2 border-gray-600 rounded-lg px-3 py-2 pr-10 text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none transition-colors"
                                placeholder="Type to search items..."
                                autocomplete="off">
                            <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <div class="item-dropdown absolute z-50 w-full bg-gray-800 border-2 border-gray-600 rounded-lg mt-1 shadow-2xl hidden max-h-80 overflow-y-auto">
                            ${dropdownHTML}
                        </div>
                    </div>
                    <input type="hidden" name="items[${rowCount}][item_id]" class="item-id-hidden" required>
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
                <td class="border border-gray-700 px-2 py-1">
                    <textarea 
                        name="items[${rowCount}][note]" 
                        class="item-note w-full bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-white"
                        rows="1"
                        placeholder="Optional note..."
                    ></textarea>
                </td>
            `;
            table.appendChild(newRow);
            
            // Initialize search functionality for the new row
            initializeItemSearch(newRow);
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

        // ================= CALCULATION ================= 
        const table = document.getElementById('itemsTable');

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