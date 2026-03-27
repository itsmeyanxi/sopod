@extends('layouts.app')

@section('title', 'Edit Issue Slip - ' . $issueSlip->issue_slip_number)

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">EDIT ISSUE SLIP</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-500">IS NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-50 border border-gray-200 text-gray-800 rounded">{{ $issueSlip->issue_slip_number }}</span>
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

        <form action="{{ route('issue_slips.update', $issueSlip->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Header Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800" value="{{ old('date', $issueSlip->date->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">ORIGIN:</label>
                        <input type="text" name="origin" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800" value="{{ old('origin', $issueSlip->origin) }}" placeholder="Warehouse / Source location">
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">SALES ORDER:</label>
                        <p class="bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800">
                            {{ $issueSlip->sales_order_number }} - {{ $issueSlip->customer_name }}
                        </p>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">DESTINATION (Customer):</label>
                        <div class="relative">
                            <input type="text" id="dest_search" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800" placeholder="Search customer name..." autocomplete="off" value="{{ old('destination', $issueSlip->destination) }}">
                            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $issueSlip->customer_id) }}">
                            <input type="hidden" name="destination" id="destination_value" value="{{ old('destination', $issueSlip->destination) }}">
                            <div id="dest_dropdown" class="hidden absolute z-50 left-0 right-0 bg-white border border-gray-300 rounded shadow-lg max-h-48 overflow-y-auto" style="top:100%"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">REMARKS:</label>
                        <textarea name="remarks" rows="2" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800" placeholder="Optional remarks...">{{ old('remarks', $issueSlip->remarks) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Signature Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">ISSUED BY:</label>
                    <input type="text" name="issued_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-white" value="{{ old('issued_by', $issueSlip->issued_by) }}" placeholder="Name / Signature">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">TRANSPORT:</label>
                    <input type="text" name="transport" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-white" value="{{ old('transport', $issueSlip->transport) }}" placeholder="Name / Signature">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">SERVICE PROVIDERS CHECKER:</label>
                    <input type="text" name="service_providers_checker" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-white" value="{{ old('service_providers_checker', $issueSlip->service_providers_checker) }}" placeholder="Name / Signature">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">RECEIVED BY:</label>
                    <input type="text" name="received_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-white" value="{{ old('received_by', $issueSlip->received_by) }}" placeholder="Name / Signature">
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Items</h3>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-200" id="itemsTable">
                        <thead class="bg-red-700 text-white">
                            <tr>
                                <th class="border border-gray-200 px-2 py-2 w-12">NO.</th>
                                <th class="border border-gray-200 px-2 py-2 w-32">ITEM CODE</th>
                                <th class="border border-gray-200 px-2 py-2" style="min-width:200px">DESCRIPTION</th>
                                <th class="border border-gray-200 px-2 py-2 w-32">BRAND</th>
                                <th class="border border-gray-200 px-2 py-2 w-32">CATEGORY</th>
                                <th class="border border-gray-200 px-2 py-2 w-28">SO QTY</th>
                                <th class="border border-gray-200 px-2 py-2 w-32">NUMBER OF BOXES</th>
                                <th class="border border-gray-200 px-2 py-2 w-32">NET WEIGHT</th>
                                <th class="border border-gray-200 px-2 py-2 w-32">ACTUAL WEIGHT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issueSlip->items as $index => $item)
                            <tr>
                                <td class="border border-gray-200 px-2 py-2 text-center text-gray-500">{{ $index + 1 }}</td>
                                <td class="border border-gray-200 px-2 py-2 text-gray-500">
                                    {{ $item->item_code }}
                                    <input type="hidden" name="items[{{ $index }}][sales_order_item_id]" value="{{ $item->sales_order_item_id }}">
                                    <input type="hidden" name="items[{{ $index }}][item_code]" value="{{ $item->item_code }}">
                                    <input type="hidden" name="items[{{ $index }}][item_description]" value="{{ $item->item_description }}">
                                    <input type="hidden" name="items[{{ $index }}][brand]" value="{{ $item->brand }}">
                                    <input type="hidden" name="items[{{ $index }}][item_category]" value="{{ $item->item_category }}">
                                    <input type="hidden" name="items[{{ $index }}][so_quantity]" value="{{ $item->so_quantity }}">
                                </td>
                                <td class="border border-gray-200 px-2 py-2 text-gray-500">{{ $item->item_description }}</td>
                                <td class="border border-gray-200 px-2 py-2 text-gray-500">{{ $item->brand }}</td>
                                <td class="border border-gray-200 px-2 py-2 text-gray-500">{{ $item->item_category }}</td>
                                <td class="border border-gray-200 px-2 py-2 text-center text-gray-500">{{ $item->so_quantity }}</td>
                                <td class="border border-gray-200 px-2 py-2">
                                    <input type="number" step="0.01" name="items[{{ $index }}][number_of_boxes]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 text-center" value="{{ $item->number_of_boxes }}">
                                </td>
                                <td class="border border-gray-200 px-2 py-2">
                                    <input type="number" step="0.0001" name="items[{{ $index }}][net_weight]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 text-center" value="{{ $item->net_weight }}">
                                </td>
                                <td class="border border-gray-200 px-2 py-2">
                                    <input type="number" step="0.0001" name="items[{{ $index }}][actual_weight]" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 text-center" value="{{ $item->actual_weight }}">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('issue_slips.show', $issueSlip->id) }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-100 transition">Cancel</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-save mr-1"></i> Update Issue Slip
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const CUSTOMER_SEARCH_URL = '{{ route("issue_slips.search_customers") }}';
let destTimeout;

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
                `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-700 dest-option"
                      data-id="${c.id}" data-name="${c.customer_name}">
                    <strong>${c.customer_name}</strong>
                    <span class="text-gray-500 text-xs ml-1">${c.customer_code || ''}</span>
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
