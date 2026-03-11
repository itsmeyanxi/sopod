@extends('layouts.app')

@section('title', 'Create Issue Slip')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CREATE ISSUE SLIP</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">IS NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded italic text-sm">Auto-generated</span>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <form action="{{ route('issue_slips.store') }}" method="POST" id="issueSlipForm">
            @csrf

            <!-- Header Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DATE: <span class="text-red-400">*</span></label>
                        <input type="date" name="date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">ORIGIN:</label>
                        <input type="text" name="origin" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" value="{{ old('origin') }}" placeholder="Warehouse / Source location">
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SALES ORDER: <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <input type="text" id="so_search" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" placeholder="Search SO number or customer..." autocomplete="off">
                            <input type="hidden" name="sales_order_id" id="sales_order_id" value="{{ old('sales_order_id') }}">
                            <div id="so_dropdown" class="hidden absolute z-50 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto" style="top:100%"></div>
                        </div>
                        <div id="so_info" class="mt-2 text-sm text-gray-400 hidden">
                            <span class="text-green-400 font-semibold" id="so_display"></span>
                            <span class="ml-2" id="so_customer"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DESTINATION (Customer):</label>
                        <div class="relative">
                            <input type="text" id="dest_search" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" placeholder="Search customer name..." autocomplete="off" value="{{ old('destination') }}">
                            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">
                            <input type="hidden" name="destination" id="destination_value" value="{{ old('destination') }}">
                            <div id="dest_dropdown" class="hidden absolute z-50 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto" style="top:100%"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">REMARKS:</label>
                        <textarea name="remarks" rows="2" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" placeholder="Optional remarks...">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-white mb-2">Items</h3>
                <div id="no_so_msg" class="text-gray-400 text-center py-8 border border-gray-700 rounded">
                    <i class="fas fa-search mr-2"></i> Search and select a Sales Order above to load items.
                </div>
                <div id="items_section" class="hidden overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-700" id="itemsTable">
                        <thead class="bg-red-700 text-white">
                            <tr>
                                <th class="border border-gray-700 px-2 py-2 w-12">NO.</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">ITEM CODE</th>
                                <th class="border border-gray-700 px-2 py-2" style="min-width:200px">DESCRIPTION</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">BRAND</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">CATEGORY</th>
                                <th class="border border-gray-700 px-2 py-2 w-28">SO QTY</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">NUMBER OF BOXES</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">NET WEIGHT</th>
                                <th class="border border-gray-700 px-2 py-2 w-32">ORIGIN</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signature Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">ISSUED BY:</label>
                    <input type="text" name="issued_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" value="{{ old('issued_by') }}" placeholder="Name / Signature">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">TRANSPORT:</label>
                    <input type="text" name="transport" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" value="{{ old('transport') }}" placeholder="Name / Signature">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">SERVICE PROVIDERS CHECKER:</label>
                    <input type="text" name="service_providers_checker" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" value="{{ old('service_providers_checker') }}" placeholder="Name / Signature">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">RECEIVED BY:</label>
                    <input type="text" name="received_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" value="{{ old('received_by') }}" placeholder="Name / Signature">
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('issue_slips.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">Cancel</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-save mr-1"></i> Create Issue Slip
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const SO_SEARCH_URL = '{{ route("issue_slips.search_sales_orders") }}';
const SO_ITEMS_URL = '{{ url("/issue-slips/sales-order-items") }}';
const CUSTOMER_SEARCH_URL = '{{ route("issue_slips.search_customers") }}';

let soTimeout;
let destTimeout;

// ========== SO SEARCH ==========
const soSearch = document.getElementById('so_search');
const soDropdown = document.getElementById('so_dropdown');
const soIdInput = document.getElementById('sales_order_id');
const soInfo = document.getElementById('so_info');
const soDisplay = document.getElementById('so_display');
const soCustomer = document.getElementById('so_customer');

soSearch.addEventListener('input', function() {
    const q = this.value.trim();
    clearTimeout(soTimeout);
    if (q.length < 1) { soDropdown.classList.add('hidden'); return; }
    soTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`${SO_SEARCH_URL}?q=${encodeURIComponent(q)}`);
            const orders = await res.json();
            if (!orders.length) { soDropdown.innerHTML = '<div class="px-3 py-2 text-gray-400 text-sm">No sales orders found.</div>'; soDropdown.classList.remove('hidden'); return; }
            soDropdown.innerHTML = orders.map(so => {
                const isUsed = so.has_issue_slip;
                const className = isUsed ? 'text-gray-500 bg-gray-900' : 'text-gray-200 hover:bg-gray-700 cursor-pointer';
                const disabledAttr = isUsed ? 'disabled' : '';
                return `<div class="px-3 py-2 text-sm so-option ${className}" ${disabledAttr}
                      data-id="${so.id}" data-number="${so.sales_order_number}" data-customer="${so.customer_name || ''}" data-used="${isUsed}">
                    <strong>${so.sales_order_number}</strong>
                    <span class="ml-2">${so.customer_name || ''}</span>
                    ${isUsed ? '<span class="text-xs ml-2 text-red-400 font-semibold">[ALREADY USED]</span>' : '<span class="text-xs text-gray-500 ml-2">' + so.status + '</span>'}
                </div>`;
            }).join('');
            soDropdown.classList.remove('hidden');
            soDropdown.querySelectorAll('.so-option').forEach(opt => {
                if (opt.getAttribute('data-used') !== 'true') {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectSO(this.dataset.id, this.dataset.number, this.dataset.customer);
                    });
                }
            });
        } catch (e) { soDropdown.classList.add('hidden'); }
    }, 250);
});

soSearch.addEventListener('blur', () => setTimeout(() => soDropdown.classList.add('hidden'), 200));

async function selectSO(id, number, customer) {
    soIdInput.value = id;
    soSearch.value = number;
    soDisplay.textContent = number;
    soCustomer.textContent = customer ? '- ' + customer : '';
    soInfo.classList.remove('hidden');
    soDropdown.classList.add('hidden');

    // Fetch items and auto-fill destination from SO customer
    try {
        const res = await fetch(`${SO_ITEMS_URL}/${id}`);
        const data = await res.json();
        loadSOItems(data.items);

        // Auto-fill destination with the SO's customer
        if (data.sales_order && data.sales_order.customer_name) {
            document.getElementById('dest_search').value = data.sales_order.customer_name;
            document.getElementById('destination_value').value = data.sales_order.customer_name;
            if (data.sales_order.customer_id) {
                document.getElementById('customer_id').value = data.sales_order.customer_id;
            }
        }
    } catch (e) {
        console.error('Failed to load SO items:', e);
    }
}

function loadSOItems(items) {
    const tbody = document.getElementById('itemsBody');
    const section = document.getElementById('items_section');
    const noMsg = document.getElementById('no_so_msg');

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-400 py-4">No items in this Sales Order.</td></tr>';
        section.classList.remove('hidden');
        noMsg.classList.add('hidden');
        return;
    }

    tbody.innerHTML = '';
    items.forEach((item, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="border border-gray-700 px-2 py-2 text-center text-gray-300">${i + 1}</td>
            <td class="border border-gray-700 px-2 py-2 text-gray-300">
                ${item.item_code || ''}
                <input type="hidden" name="items[${i}][sales_order_item_id]" value="${item.id}">
                <input type="hidden" name="items[${i}][item_code]" value="${item.item_code || ''}">
                <input type="hidden" name="items[${i}][item_description]" value="${item.item_description || ''}">
                <input type="hidden" name="items[${i}][brand]" value="${item.brand || ''}">
                <input type="hidden" name="items[${i}][item_category]" value="${item.item_category || ''}">
                <input type="hidden" name="items[${i}][so_quantity]" value="${item.quantity || 0}">
            </td>
            <td class="border border-gray-700 px-2 py-2 text-gray-300">${item.item_description || ''}</td>
            <td class="border border-gray-700 px-2 py-2 text-gray-300">${item.brand || ''}</td>
            <td class="border border-gray-700 px-2 py-2 text-gray-300">${item.item_category || ''}</td>
            <td class="border border-gray-700 px-2 py-2 text-gray-300 text-center">${item.quantity || 0}</td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="number" step="0.01" name="items[${i}][number_of_boxes]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white text-center" value="0">
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="number" step="0.0001" name="items[${i}][net_weight]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white text-center" value="0">
            </td>
            <td class="border border-gray-700 px-2 py-2 text-gray-300 text-center">
                <input type="hidden" name="items[${i}][origin]" value="${item.note || ''}">
                ${item.note || '-'}
            </td>
        `;
        tbody.appendChild(tr);
    });

    section.classList.remove('hidden');
    noMsg.classList.add('hidden');
}

// ========== DESTINATION (CUSTOMER) SEARCH ==========
const destSearch = document.getElementById('dest_search');
const destDropdown = document.getElementById('dest_dropdown');
const customerIdInput = document.getElementById('customer_id');
const destinationValue = document.getElementById('destination_value');

destSearch.addEventListener('input', function() {
    const q = this.value.trim();
    destinationValue.value = q;
    customerIdInput.value = '';
    clearTimeout(destTimeout);
    if (q.length < 1) { destDropdown.classList.add('hidden'); return; }
    destTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`${CUSTOMER_SEARCH_URL}?q=${encodeURIComponent(q)}`);
            const customers = await res.json();
            if (!customers.length) { destDropdown.classList.add('hidden'); return; }
            destDropdown.innerHTML = customers.map(c =>
                `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 dest-option"
                      data-id="${c.id}" data-name="${c.customer_name}" data-address="${c.shipping_address || ''}">
                    <strong>${c.customer_name}</strong>
                    <span class="text-gray-400 text-xs ml-1">${c.customer_code || ''}</span>
                </div>`
            ).join('');
            destDropdown.classList.remove('hidden');
            destDropdown.querySelectorAll('.dest-option').forEach(opt => {
                opt.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    destSearch.value = this.dataset.name;
                    destinationValue.value = this.dataset.name;
                    customerIdInput.value = this.dataset.id;
                    destDropdown.classList.add('hidden');
                });
            });
        } catch (e) { destDropdown.classList.add('hidden'); }
    }, 250);
});

destSearch.addEventListener('blur', () => setTimeout(() => destDropdown.classList.add('hidden'), 200));
</script>
@endsection
