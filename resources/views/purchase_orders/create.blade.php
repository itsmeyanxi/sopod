@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CREATE PURCHASE ORDER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">PO NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $poNo }}</span>
            </div>
        </div>

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Session error (from catch block) --}}
        @if(session('error'))
            <div class="bg-red-700 text-white px-4 py-3 rounded mb-4">
                <p class="font-semibold">{{ session('error') }}</p>
            </div>
        @endif

        <form action="{{ route('purchase_orders.store') }}" method="POST" id="poForm" enctype="multipart/form-data">
            @csrf

            <!-- Search PR Section -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-300 mb-2">SEARCH PURCHASE REQUEST (Optional):</label>
                <p class="text-gray-300 text-sm mb-3">Search by PR Number, Requisitioner, or Company to auto-fill data</p>

                <div class="relative">
                    <input type="text" id="prSearchInput"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 pr-10"
                        placeholder="Type to search approved PRs..." autocomplete="off">
                    <span class="absolute right-3 top-2.5 text-gray-300"><i class="fas fa-search"></i></span>
                </div>

                <div id="prSearchResults" class="hidden mt-2 bg-gray-800 border border-gray-700 rounded max-h-64 overflow-y-auto"></div>

                <div id="selectedPRDisplay" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-green-700 font-semibold">Selected PR: </span>
                            <span id="selectedPRText" class="text-gray-300"></span>
                        </div>
                        <button type="button" onclick="clearSelectedPR()" class="text-red-700 hover:text-red-700">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>

                <input type="hidden" name="purchase_request_id" id="selectedPRId" value="{{ old('purchase_request_id', $selectedPR->id ?? '') }}">
            </div>

            <!-- Company (Hidden - MeatPlus Only) -->
            <input type="hidden" name="company" value="Meatplus Trading Corp.">

            <!-- Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">SUPPLIER: <span class="text-red-700">*</span></label>
                        <div class="relative">
                            <input type="text" id="topSupplierSearch"
                                   class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 pr-10"
                                   placeholder="Search supplier by name or code..."
                                   autocomplete="off"
                                   value="{{ old('supplier', $selectedPR->supplier->supplier_name ?? '') }}">
                            <span class="absolute right-3 top-2.5 text-gray-400"><i class="fas fa-search"></i></span>
                            <div id="topSupplierDropdown" class="hidden absolute z-30 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-64 overflow-y-auto" style="top:100%"></div>
                        </div>
                        <p id="topSupplierInfo" class="text-xs text-gray-400 mt-1 {{ old('supplier') || (isset($selectedPR) && $selectedPR->supplier) ? '' : 'hidden' }}">
                            <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
                            <span id="topSupplierCode"></span>
                        </p>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE:</label>
                        <input type="text" name="consignee" id="consignee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('consignee', $selectedPR->contact_person ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE ADDRESS:</label>
                        <textarea name="consignee_address" id="consignee_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('consignee_address', $selectedPR->address ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                        <textarea name="delivery_address" id="delivery_address" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ old('delivery_address', $selectedPR->delivery_address ?? '') }}</textarea>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">ORDER DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="order_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('order_date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">EXPECTED DELIVERY DATE:</label>
                        <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('expected_delivery_date', isset($selectedPR) && $selectedPR && $selectedPR->date_needed ? $selectedPR->date_needed->format('Y-m-d') : '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" id="payment_terms" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms', $selectedPR->terms ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LOCATION:</label>
                        <input type="text" name="location" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('location') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">HOUSE:</label>
                        <input type="text" name="house" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('house') }}">
                    </div>
                    <!-- <div>
                        <label class="block font-semibold text-gray-300 mb-1">BRAND:</label>
                        <input type="text" name="brand" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('brand') }}" placeholder="e.g. Brand name">
                    </div> -->
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">PR#:</label>
                        <input type="text" name="pr_no" id="pr_no" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('pr_no', $selectedPR->pr_no ?? '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">REFERENCE NUMBER:</label>
                        <input type="text" name="reference_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_number') }}" placeholder="e.g. REF-001">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LC PRICE:</label>
                        <input type="number" step="0.01" name="lc_price" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('lc_price') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CURRENCY:</label>
                        <select name="currency" id="currency_select" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="onCurrencyChange()">
                            @foreach($currencies as $cur)
                                <option value="{{ $cur->code }}"
                                        data-rate="{{ $cur->rate_to_php }}"
                                        data-symbol="{{ $cur->symbol }}"
                                        {{ old('currency', 'PHP') === $cur->code ? 'selected' : '' }}>
                                    {{ $cur->code }} — {{ $cur->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="exchange_rate_row" class="{{ old('currency', 'PHP') === 'PHP' ? 'hidden' : '' }}">
                        <label class="block font-semibold text-gray-300 mb-1">EXCHANGE RATE <span class="text-gray-300 text-xs" id="rate_label">(1 USD = ? PHP)</span>:</label>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-300">₱</span>
                            <input type="number" step="0.0001" name="exchange_rate" id="exchange_rate" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('exchange_rate', 1) }}">
                        </div>
                        <p class="text-gray-300 text-xs mt-1">Auto-filled from current rate. You may override.</p>
                    </div>
                </div>
            </div>

            <input type="hidden" name="supplier_id" id="supplier_id" value="">
            <input type="hidden" name="supplier" id="supplier_text" value="">
            <input type="hidden" name="supplier_address" id="supplier_address_hidden" value="">
            <input type="hidden" name="supplier_tin" id="supplier_tin_hidden" value="{{ old('supplier_tin') }}">
            <input type="hidden" name="po_type" id="poTypeInput" value="items">

            <!-- PO Type Toggle -->
            <div class="mb-4 flex items-center gap-3">
                <span class="text-gray-300 font-semibold">PO TYPE:</span>
                <button type="button" id="btnTypeItems" onclick="setPOType('items')"
                    class="px-5 py-2 rounded font-semibold transition bg-purple-600 text-white">
                    <i class="fas fa-boxes mr-1"></i> Items
                </button>
                <button type="button" id="btnTypeService" onclick="setPOType('service')"
                    class="px-5 py-2 rounded font-semibold transition bg-gray-700 text-gray-300 hover:bg-gray-600">
                    <i class="fas fa-tools mr-1"></i> Service
                </button>
            </div>

            <!-- Service Description (shown only for service POs) -->
            <div id="serviceDescSection" class="hidden mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-white mb-2">SERVICE DESCRIPTION: <span class="text-red-500">*</span></label>
                <textarea name="service_description" id="serviceDescription" rows="4"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Describe the service to be rendered...">{{ old('service_description') }}</textarea>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1">QUANTITY</label>
                        <input type="number" step="any" name="service_qty" id="service_qty"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="0" value="{{ old('service_qty') }}" oninput="calcServiceTotal()">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1">UOM</label>
                        <input type="text" name="service_uom" id="service_uom"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="e.g. hrs, units" value="{{ old('service_uom') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1">UNIT AMOUNT</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-300">₱</span>
                            <input type="number" step="0.01" name="service_amount" id="service_amount"
                                class="w-full bg-gray-800 border border-gray-700 rounded pl-7 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="0.00" value="{{ old('service_amount') }}" oninput="calcServiceTotal()">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1">VAT (12%)</label>
                        <div class="flex items-center gap-2 mt-2">
                            <input type="checkbox" name="service_vat" id="service_vat" value="1"
                                class="w-4 h-4 accent-purple-500" onchange="calcServiceTotal()"
                                {{ old('service_vat') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-300">Include 12% VAT</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3" id="service_total_wrapper">
                    <label class="block text-sm font-semibold text-gray-300 mb-1" id="service_total_label">TOTAL AMOUNT</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-300">₱</span>
                        <input type="number" step="0.01" id="service_total_display"
                            class="w-full bg-gray-700 border border-gray-600 rounded pl-7 pr-3 py-2 text-green-400 font-bold focus:outline-none"
                            placeholder="0.00" readonly>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6" id="itemsTableSection">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-white">Items</h3>
                    <button type="button" onclick="addRow()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="border-collapse border border-gray-700" id="itemsTable" style="min-width:1400px; width:100%;">
                        <thead class="bg-red-700 text-white">
                            <tr>
                                <th class="border border-gray-700 px-2 py-2" style="width:70px">ACTION</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:40px">NO.</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:130px">ITEM CODE</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:120px">QTY</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:80px">UOM</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:280px">DESCRIPTION</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:130px">BRAND</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:140px">DATE NEEDED</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:110px">UNIT PRICE</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:110px">VAT (12%)</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:110px">TOTAL</th>
                                <th class="border border-gray-700 px-2 py-2" style="width:150px">REMARKS</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @php
                                $items = (isset($selectedPR) && $selectedPR && isset($selectedPR->filteredItems) && $selectedPR->filteredItems->count() > 0)
                                    ? $selectedPR->filteredItems : null;
                                $prSupplierName = (isset($selectedPR) && $selectedPR && $selectedPR->supplier)
                                    ? $selectedPR->supplier->supplier_name : '';
                                $prSupplierId = (isset($selectedPR) && $selectedPR) ? ($selectedPR->supplier_id ?? '') : '';
                            @endphp
                            @if($items)
                                @foreach($items as $index => $item)
                                <tr>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2 text-center text-gray-400">{{ $index + 1 }}</td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[{{ $index }}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" autocomplete="off" value="{{ $item->item_code }}">
                                        <input type="hidden" name="items[{{ $index }}][purchase_request_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="any" name="items[{{ $index }}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" oninput="calculateRowTotal(this.closest('tr'));updateCurrencySummary();" value="{{ $item->remaining_qty }}" required>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[{{ $index }}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->uom }}">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <div class="relative">
                                            <input type="text" name="items[{{ $index }}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" value="{{ $item->description }}" required autocomplete="off">
                                            <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                        </div>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[{{ $index }}][brand]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white brand-input" value="{{ old('items.'.$index.'.brand') }}" placeholder="Brand">
                                    </td>
                                    <input type="hidden" name="items[{{ $index }}][supplier_id]" class="supplier-id-input" value="{{ $item->supplier_id ?? $prSupplierId }}">
                                    <input type="hidden" name="items[{{ $index }}][supplier_name]" class="supplier-name-input" value="{{ $item->supplier_name ?? $prSupplierName }}">
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="date" name="items[{{ $index }}][date_needed]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->date_needed ? \Carbon\Carbon::parse($item->date_needed)->format('Y-m-d') : '' }}">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price" oninput="calculateRowTotal(this.closest('tr'));updateCurrencySummary();" value="{{ $item->unit_price }}">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <label class="flex items-center justify-center gap-1 cursor-pointer mb-1">
                                            <input type="checkbox" name="items[{{ $index }}][vat]" class="item-vat" value="1" {{ ($item->vat ?? 0) ? 'checked' : '' }} onchange="calculateRowTotal(this.closest('tr'));updateCurrencySummary();">
                                            <span class="text-xs text-gray-300">VAT</span>
                                        </label>
                                        <input type="number" step="0.01" name="items[{{ $index }}][tax]" class="w-full px-1 py-1 bg-gray-800 border border-gray-700 rounded text-green-400 item-tax text-xs text-center" readonly value="{{ $item->tax ?? 0 }}" placeholder="0.00">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="0.01" name="items[{{ $index }}][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" readonly>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[{{ $index }}][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" value="{{ $item->remarks }}" placeholder="Remarks...">
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2 text-center text-gray-400">1</td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[0][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" autocomplete="off">
                                        <input type="hidden" name="items[0][purchase_request_item_id]" value="">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="any" name="items[0][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" oninput="calculateRowTotal(this.closest('tr'));updateCurrencySummary();" required>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[0][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <div class="relative">
                                            <input type="text" name="items[0][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off">
                                            <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
                                        </div>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[0][brand]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white brand-input" placeholder="Brand">
                                    </td>
                                    <input type="hidden" name="items[0][supplier_id]" class="supplier-id-input" value="">
                                    <input type="hidden" name="items[0][supplier_name]" class="supplier-name-input" value="">
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="date" name="items[0][date_needed]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="0.01" name="items[0][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price" oninput="calculateRowTotal(this.closest('tr'));updateCurrencySummary();">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <label class="flex items-center justify-center gap-1 cursor-pointer mb-1">
                                            <input type="checkbox" name="items[0][vat]" class="item-vat" value="1" onchange="calculateRowTotal(this.closest('tr'));updateCurrencySummary();">
                                            <span class="text-xs text-gray-300">VAT</span>
                                        </label>
                                        <input type="number" step="0.01" name="items[0][tax]" class="w-full px-1 py-1 bg-gray-800 border border-gray-700 rounded text-green-400 item-tax text-xs text-center" readonly value="0" placeholder="0.00">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="0.01" name="items[0][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" readonly>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[0][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" placeholder="Remarks...">
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Currency Totals Summary -->
            <div id="currency_summary" class="mb-4 hidden">
                <div class="bg-gray-900 border border-purple-700 rounded p-4">
                    <h3 class="font-semibold text-purple-700 mb-2">PHP Equivalent Summary</h3>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <span class="text-gray-300">Total (<span id="summary_currency">USD</span>):</span>
                            <span class="text-white font-bold ml-2" id="summary_foreign_total">0.00</span>
                        </div>
                        <div>
                            <span class="text-gray-300">Exchange Rate:</span>
                            <span class="text-white ml-2">1 <span id="summary_code">USD</span> = ₱<span id="summary_rate">0.00</span></span>
                        </div>
                        <div>
                            <span class="text-gray-300">Total (PHP):</span>
                            <span class="text-green-700 font-bold ml-2">₱<span id="summary_php_total">0.00</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">REMARKS:</label>
                <textarea name="remarks" id="remarks" rows="4"
                          class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                          placeholder="Enter remarks...">{{ old('remarks', $selectedPR->reason_for_requisition ?? '') }}</textarea>
            </div>

            <!-- Quotation File Upload -->
            <div class="mb-6">
                <label class="block font-semibold text-white mb-2">QUOTATION:</label>
                <div class="flex items-center gap-4">
                    <input type="file" name="quotation" id="quotation"
                           class="flex-1 bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    <span class="text-gray-300 text-sm">(PDF, Word, Excel, Image)</span>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-700 rounded">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Prepared By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Checked By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By:</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-700 text-gray-300 text-xs italic">
                                <td class="border border-gray-700 px-4 py-2 text-center">Purchasing Officer</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">Accounting Manager</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">Authorized Signatory</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('purchase_orders.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let rowCount = {{ ($items ? $items->count() : 1) }};

const availablePRs      = @json($purchaseRequests);
const prSearchInput     = document.getElementById('prSearchInput');
const prSearchResults   = document.getElementById('prSearchResults');
const selectedPRDisplay = document.getElementById('selectedPRDisplay');
const selectedPRText    = document.getElementById('selectedPRText');
const selectedPRId      = document.getElementById('selectedPRId');

// ── Route URLs ──────────────────────────────────────────────────────────────
const SUPPLIER_SEARCH_URL    = '{{ route("purchase_orders.search_suppliers") }}';
const SEARCH_URL             = '{{ route("items.search") }}';
const ITEM_CODE_SEARCH_URL   = '{{ route("purchase_orders.search_by_item_code") }}';
const GENERATE_ITEM_CODE_URL = '{{ route("purchase_orders.generate_item_code") }}';
const LAST_SUPPLIER_URL      = '{{ route("purchase_orders.last_supplier_for_item") }}';

// ======================== PR SEARCH ========================
prSearchInput.addEventListener('input', function () {
    const searchTerm = this.value.toLowerCase().trim();
    if (searchTerm.length < 2) { prSearchResults.classList.add('hidden'); return; }

    const filteredPRs = availablePRs.filter(pr =>
        pr.pr_no.toLowerCase().includes(searchTerm) ||
        pr.requisitioner.toLowerCase().includes(searchTerm) ||
        pr.company.toLowerCase().includes(searchTerm)
    );

    if (!filteredPRs.length) {
        prSearchResults.innerHTML = '<div class="p-3 text-gray-300 text-center">No matching PRs found</div>';
        prSearchResults.classList.remove('hidden');
        return;
    }

    prSearchResults.innerHTML = filteredPRs.map(pr => {
        const dateFormatted = new Date(pr.date_of_request).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        const badge = pr.is_partially_ordered
            ? '<span class="ml-2 px-2 py-0.5 bg-yellow-600 text-white text-xs rounded">Partially Ordered</span>'
            : '';
        return `
            <div class="p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700"
                 onclick="selectPR(${pr.id}, '${pr.pr_no}', '${pr.requisitioner}', '${dateFormatted}')">
                <div class="font-semibold text-white">${pr.pr_no}${badge}</div>
                <div class="text-sm text-gray-300">${pr.requisitioner} • ${pr.company}</div>
                <div class="text-xs text-gray-300">${dateFormatted}</div>
            </div>`;
    }).join('');
    prSearchResults.classList.remove('hidden');
});

document.addEventListener('click', function (e) {
    if (!prSearchInput.contains(e.target) && !prSearchResults.contains(e.target)) {
        prSearchResults.classList.add('hidden');
    }
});

function selectPR(prId, prNo, requisitioner, dateFormatted) {
    selectedPRId.value = prId;
    selectedPRText.textContent = `${prNo} - ${requisitioner} (${dateFormatted})`;
    selectedPRDisplay.classList.remove('hidden');
    prSearchResults.classList.add('hidden');
    prSearchInput.value = '';
    window.location.href = '{{ route("purchase_orders.create") }}?pr_id=' + prId;
}

function clearSelectedPR() {
    selectedPRId.value = '';
    selectedPRDisplay.classList.add('hidden');
    window.location.href = '{{ route("purchase_orders.create") }}';
}

@if($selectedPR)
document.addEventListener('DOMContentLoaded', function () {
    const dateFormatted = new Date('{{ $selectedPR->date_of_request->format('Y-m-d') }}').toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
    selectedPRText.textContent = '{{ $selectedPR->pr_no }} - {{ $selectedPR->requisitioner }} (' + dateFormatted + ')';
    selectedPRDisplay.classList.remove('hidden');
});
@endif

// ======================== ROW MANAGEMENT ========================
function addRow() {
    const tbody  = document.getElementById('itemsBody');
    const newRow = tbody.insertRow();
    newRow.innerHTML = `
        <td class="border border-gray-700 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold transition">
                <i class="fas fa-trash mr-1"></i>Delete
            </button>
        </td>
        <td class="border border-gray-700 px-2 py-2 text-center text-gray-400">${rowCount + 1}</td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][item_code]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-code-input" autocomplete="off">
            <input type="hidden" name="items[${rowCount}][purchase_request_item_id]" value="">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="number" step="any" name="items[${rowCount}][qty]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-qty" oninput="calculateRowTotal(this.closest('tr'));updateCurrencySummary();" required>
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][uom]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <div class="relative">
                <input type="text" name="items[${rowCount}][description]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white desc-input" required autocomplete="off">
                <div class="desc-dropdown hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-40 overflow-y-auto" style="top:100%"></div>
            </div>
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][brand]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white brand-input" placeholder="Brand">
        </td>
        <input type="hidden" name="items[${rowCount}][supplier_id]" class="supplier-id-input" value="">
        <input type="hidden" name="items[${rowCount}][supplier_name]" class="supplier-name-input" value="">
        <td class="border border-gray-700 px-2 py-2">
            <input type="date" name="items[${rowCount}][date_needed]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="number" step="0.01" name="items[${rowCount}][unit_price]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-price" oninput="calculateRowTotal(this.closest('tr'));updateCurrencySummary();">
        </td>
        <td class="border border-gray-700 px-2 py-2 text-center">
            <label class="flex items-center justify-center gap-1 cursor-pointer mb-1">
                <input type="checkbox" name="items[${rowCount}][vat]" class="item-vat" value="1" onchange="calculateRowTotal(this.closest('tr'));updateCurrencySummary();">
                <span class="text-xs text-gray-300">VAT</span>
            </label>
            <input type="number" step="0.01" name="items[${rowCount}][tax]" class="w-full px-1 py-1 bg-gray-800 border border-gray-700 rounded text-green-400 item-tax text-xs text-center" readonly value="0" placeholder="0.00">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="number" step="0.01" name="items[${rowCount}][total]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white item-total" readonly>
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowCount}][note]" class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white" placeholder="Remarks...">
        </td>
    `;
    rowCount++;
    attachCalculationListeners();
    attachItemCodeListeners();
    const newDesc = newRow.querySelector('.desc-input');
    if (newDesc) attachDescAutocomplete(newDesc);
}

function removeRow(btn) {
    btn.closest('tr').remove();
    reorderRows();
}

function reorderRows() {
    document.querySelectorAll('#itemsBody tr').forEach((row, index) => {
        row.cells[1].textContent = index + 1;
        row.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name');
            if (name) input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
        });
    });
    rowCount = document.querySelectorAll('#itemsBody tr').length;
}

// ======================== CALCULATION ========================
function attachCalculationListeners() {
    document.querySelectorAll('.item-qty, .item-price').forEach(input => {
        input.removeEventListener('input', calculateTotal);
        input.addEventListener('input', calculateTotal);
    });
}

function calculateRowTotal(row) {
    const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
    const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
    const vat   = row.querySelector('.item-vat')?.checked || false;
    const taxInput = row.querySelector('.item-tax');
    if (taxInput) taxInput.value = vat ? (qty * price * 0.12).toFixed(2) : '0.00';
    const total = row.querySelector('.item-total');
    if (total) total.value = (qty * price).toFixed(2);
}

function calculateTotal(e) {
    calculateRowTotal(e.target.closest('tr'));
    updateCurrencySummary();
}

function recalculateAllTotals() {
    document.querySelectorAll('#itemsBody tr').forEach(row => calculateRowTotal(row));
    updateCurrencySummary();
}

// ======================== CURRENCY ========================
function onCurrencyChange() {
    const select    = document.getElementById('currency_select');
    const code      = select.value;
    const rate      = parseFloat(select.selectedOptions[0].getAttribute('data-rate')) || 1;
    const rateRow   = document.getElementById('exchange_rate_row');
    const rateInput = document.getElementById('exchange_rate');
    if (code === 'PHP') {
        rateRow.classList.add('hidden');
        rateInput.value = 1;
    } else {
        rateRow.classList.remove('hidden');
        rateInput.value = rate.toFixed(4);
        document.getElementById('rate_label').textContent = `(1 ${code} = ? PHP)`;
    }
    updateCurrencySummary();
    calcServiceTotal();
}

function calcServiceTotal() {
    const qty     = parseFloat(document.getElementById('service_qty')?.value) || 0;
    const amt     = parseFloat(document.getElementById('service_amount')?.value) || 0;
    const vatChk  = document.getElementById('service_vat')?.checked || false;
    const code    = document.getElementById('currency_select').value;
    const rate    = parseFloat(document.getElementById('exchange_rate').value) || 1;
    const label   = document.getElementById('service_total_label');
    const disp    = document.getElementById('service_total_display');

    const subtotal   = qty * amt;
    const vatAmt     = vatChk ? subtotal * 0.12 : 0;
    const grandTotal = subtotal + vatAmt;

    if (disp) {
        if (code === 'PHP') {
            disp.value = grandTotal.toFixed(2);
            if (label) label.textContent = 'TOTAL AMOUNT' + (vatChk ? ' (incl. VAT)' : '');
        } else {
            disp.value = (grandTotal * rate).toFixed(2);
            if (label) label.textContent = `TOTAL (PHP equiv. @ ${rate.toFixed(4)})` + (vatChk ? ' incl. VAT' : '');
        }
    }
    updateCurrencySummary();
}

function updateCurrencySummary() {
    const code    = document.getElementById('currency_select').value;
    const rate    = parseFloat(document.getElementById('exchange_rate').value) || 1;
    const summary = document.getElementById('currency_summary');
    if (code === 'PHP') { summary.classList.add('hidden'); return; }

    const isService = document.getElementById('poTypeInput')?.value === 'service';
    let foreignTotal = 0;
    if (isService) {
        const sQty    = parseFloat(document.getElementById('service_qty')?.value) || 0;
        const sAmt    = parseFloat(document.getElementById('service_amount')?.value) || 0;
        const sVat    = document.getElementById('service_vat')?.checked || false;
        const subtotal = sQty * sAmt;
        foreignTotal  = subtotal + (sVat ? subtotal * 0.12 : 0);
    } else {
        document.querySelectorAll('.item-total').forEach(inp => foreignTotal += parseFloat(inp.value) || 0);
    }

    summary.classList.remove('hidden');
    document.getElementById('summary_currency').textContent      = code;
    document.getElementById('summary_code').textContent          = code;
    document.getElementById('summary_rate').textContent          = rate.toFixed(4);
    document.getElementById('summary_foreign_total').textContent = code + ' ' + foreignTotal.toFixed(2);
    document.getElementById('summary_php_total').textContent     = (foreignTotal * rate).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.getElementById('exchange_rate').addEventListener('input', () => { updateCurrencySummary(); calcServiceTotal(); });

// ======================== DROPDOWN POSITIONING ========================
function positionFixedDropdown(inputEl, dropdownEl) {
    const rect = inputEl.getBoundingClientRect();
    dropdownEl.style.position  = 'fixed';
    dropdownEl.style.top       = rect.bottom + 'px';
    dropdownEl.style.left      = rect.left + 'px';
    dropdownEl.style.width     = rect.width + 'px';
    dropdownEl.style.zIndex    = '9999';
    dropdownEl.style.maxHeight = '200px';
    dropdownEl.style.overflowY = 'auto';
}

let scrollTicking = false;
window.addEventListener('scroll', function () {
    if (!scrollTicking) {
        requestAnimationFrame(() => {
            document.querySelectorAll('.desc-dropdown:not(.hidden), .supplier-dropdown:not(.hidden)').forEach(dd => {
                const input = dd.closest('.relative')?.querySelector('input:first-child') || dd.previousElementSibling;
                if (input) positionFixedDropdown(input, dd);
            });
            scrollTicking = false;
        });
        scrollTicking = true;
    }
}, true);

// ======================== SUPPLIER SEARCH ========================
// KEY CHANGE: reads the description from the same row and passes it to the
// backend so only suppliers who have sold that item are returned.
let supplierSearchTimeout;

function attachSupplierSearch(input) {
    const wrapper   = input.closest('.relative');
    const dropdown  = wrapper.querySelector('.supplier-dropdown');
    const idInput   = wrapper.querySelector('.supplier-id-input');
    const nameInput = wrapper.querySelector('.supplier-name-input');

    function getRowDescription() {
        const row = input.closest('tr');
        return row ? (row.querySelector('.desc-input')?.value.trim() || '') : '';
    }

    async function doSearch() {
        const q    = input.value.trim();
        const desc = getRowDescription();
        clearTimeout(supplierSearchTimeout);

        if (q.length < 1 && !desc) {
            dropdown.classList.add('hidden');
            return;
        }

        supplierSearchTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q });
                if (desc) params.append('description', desc);
                // Always send current supplier ID so backend can guarantee it appears
                if (idInput.value) params.append('current_supplier_id', idInput.value);

                const res       = await fetch(`${SUPPLIER_SEARCH_URL}?${params}`);
                const suppliers = await res.json();

                if (!suppliers.length) {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-gray-300 text-sm">No suppliers found</div>';
                    positionFixedDropdown(input, dropdown);
                    dropdown.classList.remove('hidden');
                    return;
                }

                const isFallback = suppliers.some(s => s.is_fallback);

                // Header
                let headerHtml = '';
                if (desc && !isFallback) {
                    headerHtml = `<div class="px-3 py-1.5 text-xs text-green-700 bg-gray-900 border-b border-gray-700 font-semibold">
                        ✓ Showing suppliers confirmed to carry this item
                    </div>`;
                } else if (desc && isFallback) {
                    headerHtml = `<div class="px-3 py-1.5 text-xs text-yellow-700 bg-gray-900 border-b border-gray-700 font-semibold">
                        ⚠ This item has no confirmed suppliers in the library — showing all active suppliers
                    </div>`;
                }

                // Supplier rows
                const rowsHtml = suppliers.map(s => {
                    let badge = '';
                    if (s.is_current && s.carries_item === false) {
                        badge = `<span class="ml-1 px-1.5 py-0.5 text-xs bg-blue-700 text-blue-100 rounded">← Current (unconfirmed)</span>`;
                    } else if (s.carries_item === true) {
                        badge = `<span class="ml-1 px-1.5 py-0.5 text-xs bg-green-700 text-green-100 rounded">✓ Carries item</span>`;
                    } else if (desc) {
                        badge = `<span class="ml-1 px-1.5 py-0.5 text-xs bg-gray-700 text-gray-300 rounded">? Unconfirmed</span>`;
                    }
                    return `
                    <div class="px-3 py-2 hover:bg-gray-700 cursor-pointer border-b border-gray-700 supplier-option"
                         data-id="${s.id}"
                         data-name="${s.supplier_name}"
                         data-code="${s.supplier_code || ''}"
                         data-address="${(s.address || '').replace(/"/g, '&quot;')}">
                        <div class="flex items-center gap-1 text-sm font-semibold text-white flex-wrap">
                            ${s.supplier_name} ${badge}
                        </div>
                        <div class="text-xs text-gray-300">${s.supplier_code || ''}</div>
                    </div>`;
                }).join('');

                dropdown.innerHTML = headerHtml + rowsHtml;
                positionFixedDropdown(input, dropdown);
                dropdown.classList.remove('hidden');

                dropdown.querySelectorAll('.supplier-option').forEach(opt => {
                    opt.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value     = this.dataset.name;
                        idInput.value   = this.dataset.id;
                        nameInput.value = this.dataset.name;
                        dropdown.classList.add('hidden');
                    });
                });

            } catch (err) {
                console.error('Supplier search error:', err);
                dropdown.classList.add('hidden');
            }
        }, 250);
    }

    input.addEventListener('input', function () {
        nameInput.value = this.value;
        idInput.value   = '';
        doSearch();
    });
    // Re-fetch when user focuses into the supplier field (description may have been set)
    input.addEventListener('focus',  doSearch);
    input.addEventListener('blur',   function() {
        nameInput.value = this.value;
        setTimeout(() => dropdown.classList.add('hidden'), 200);
    });
}

// ======================== DESCRIPTION AUTOCOMPLETE ========================
let descTimeout;

// ======================== LAST SUPPLIER AUTO-FILL ========================
async function autoFillLastSupplier(row, description) {
    const si = row.querySelector('.supplier-id-input');
    const sn = row.querySelector('.supplier-name-input');
    const ss = row.querySelector('.supplier-search-input');
    // Only auto-fill if supplier is not already chosen
    if (si && si.value) return;
    if (!description || description.length < 2) return;
    try {
        const res  = await fetch(`${LAST_SUPPLIER_URL}?description=${encodeURIComponent(description)}`);
        const data = await res.json();
        if (data && data.supplier_id) {
            if (si) si.value = data.supplier_id;
            if (sn) sn.value = data.supplier_name || '';
            if (ss) ss.value = data.supplier_name || '';
        }
    } catch (e) { /* silent fail */ }
}

function attachDescAutocomplete(input) {
    const dropdown = input.nextElementSibling;

    async function fetchSuggestions() {
        const q          = input.value.trim();
        const row        = input.closest('tr');
        const supplierId = row?.querySelector('.supplier-id-input')?.value || '';
        clearTimeout(descTimeout);
        if (!supplierId && q.length < 2) { dropdown.classList.add('hidden'); return; }
        descTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q });
                if (supplierId) params.append('supplier_id', supplierId);
                const res   = await fetch(`${SEARCH_URL}?${params}`);
                const items = await res.json();
                if (!items.length) { dropdown.classList.add('hidden'); return; }
                dropdown.innerHTML = items.map(item =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 desc-option"
                          data-name="${(item.item_description || '').replace(/"/g, '&quot;')}"
                          data-item-code="${(item.item_code || '').replace(/"/g, '&quot;')}"
                          data-brand="${(item.brand || '').replace(/"/g, '&quot;')}">
                        <div class="font-semibold">${item.item_description || ''}</div>
                        <div class="text-xs text-gray-400">${item.item_code || ''} ${item.brand ? '· '+item.brand : ''} <span class="text-yellow-400">${item.type === 'non_trade' ? 'Non-Trade' : 'Trade'}</span></div>
                    </div>`
                ).join('');
                positionFixedDropdown(input, dropdown);
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.desc-option').forEach(opt => {
                    opt.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value = this.dataset.name || this.textContent;
                        dropdown.classList.add('hidden');
                        const row = input.closest('tr');
                        if (!row) return;
                        const itemCodeInput = row.querySelector('.item-code-input');
                        if (this.dataset.itemCode && itemCodeInput) {
                            itemCodeInput.value = this.dataset.itemCode;
                        } else if (itemCodeInput) {
                            itemCodeInput.value = '';
                            autoGenerateItemCode(row);
                        }
                        const brandInput = row.querySelector('.brand-input');
                        if (brandInput && this.dataset.brand) brandInput.value = this.dataset.brand;
                        if (this.dataset.supplierId) {
                            const ss = row.querySelector('.supplier-search-input');
                            const si = row.querySelector('.supplier-id-input');
                            const sn = row.querySelector('.supplier-name-input');
                            if (ss) ss.value = this.dataset.supplierName || '';
                            if (si) si.value = this.dataset.supplierId;
                            if (sn) sn.value = this.dataset.supplierName || '';
                        } else {
                            // No supplier in item library — try last used supplier from PO history
                            autoFillLastSupplier(row, input.value.trim());
                        }
                    });
                });
            } catch (e) { dropdown.classList.add('hidden'); }
        }, 250);
    }

    input.addEventListener('input', function() {
        const row = input.closest('tr');
        if (row && !input.value.trim()) {
            const ic = row.querySelector('.item-code-input');
            const ss = row.querySelector('.supplier-search-input');
            const si = row.querySelector('.supplier-id-input');
            const sn = row.querySelector('.supplier-name-input');
            if (ic) ic.value = '';
            if (ss) ss.value = '';
            if (si) si.value = '';
            if (sn) sn.value = '';
        }
        fetchSuggestions();
    });
    input.addEventListener('focus', fetchSuggestions);
    input.addEventListener('blur', function () {
        setTimeout(() => dropdown.classList.add('hidden'), 200);
        const row           = input.closest('tr');
        const itemCodeInput = row?.querySelector('.item-code-input');
        if (row && itemCodeInput && !itemCodeInput.value.trim()) autoGenerateItemCode(row);
        // Auto-fill supplier from last PO if not already set
        if (row && input.value.trim()) autoFillLastSupplier(row, input.value.trim());
    });
}

// ======================== ITEM CODE AUTO-FILL ========================
let itemCodeTimeout;

function attachItemCodeListeners() {
    document.querySelectorAll('.item-code-input').forEach(input => {
        if (input.dataset.itemCodeBound) return;
        input.dataset.itemCodeBound = '1';

        input.addEventListener('input', function () {
            clearTimeout(itemCodeTimeout);
            const code = this.value.trim();
            if (!code) return;
            const row = this.closest('tr');
            itemCodeTimeout = setTimeout(() => fetchAndFillByItemCode(code, row), 400);
        });

        input.addEventListener('blur', function () {
            clearTimeout(itemCodeTimeout);
            const code = this.value.trim();
            if (!code) return;
            fetchAndFillByItemCode(code, this.closest('tr'));
        });
    });
}

async function fetchAndFillByItemCode(itemCode, row) {
    if (!row) return;
    try {
        const res  = await fetch(`${ITEM_CODE_SEARCH_URL}?item_code=${encodeURIComponent(itemCode)}`);
        const data = await res.json();
        if (!data || !data.description) return;

        const descInput = row.querySelector('.desc-input');
        if (descInput) descInput.value = data.description;

        if (data.supplier_id) {
            const ss   = row.querySelector('.supplier-search-input');
            const si   = row.querySelector('.supplier-id-input');
            const sn   = row.querySelector('.supplier-name-input');
            if (ss) ss.value = data.supplier_name || '';
            if (si) si.value = data.supplier_id;
            if (sn) sn.value = data.supplier_name || '';
        }
        if (data.uom) {
            const u = row.querySelector('input[name*="[uom]"]');
            if (u) u.value = data.uom;
        }
        if (data.unit_price) {
            const p = row.querySelector('.item-price');
            if (p) { p.value = data.unit_price; p.dispatchEvent(new Event('input')); }
        }
    } catch (e) { console.error('Error fetching item by code:', e); }
}

async function autoGenerateItemCode(row) {
    const descInput     = row.querySelector('.desc-input');
    const itemCodeInput = row.querySelector('.item-code-input');
    if (!descInput || !itemCodeInput || !descInput.value.trim()) return;
    const supplierId = row.querySelector('.supplier-id-input')?.value || '';
    try {
        const params = new URLSearchParams({ description: descInput.value.trim() });
        if (supplierId) params.append('supplier_id', supplierId);
        const res  = await fetch(`${GENERATE_ITEM_CODE_URL}?${params}`);
        const data = await res.json();
        if (data.item_code) itemCodeInput.value = data.item_code;
    } catch (e) { console.error('Error generating item code:', e); }
}

// ======================== TOP-LEVEL SUPPLIER SEARCH ========================
let topSupplierTimeout;

function initTopSupplierSearch() {
    const input    = document.getElementById('topSupplierSearch');
    const dropdown = document.getElementById('topSupplierDropdown');
    const idHidden = document.getElementById('supplier_id');
    const nameHidden = document.getElementById('supplier_text');
    const addrHidden = document.getElementById('supplier_address_hidden');
    const infoLine = document.getElementById('topSupplierInfo');
    const codeLine = document.getElementById('topSupplierCode');

    async function doSearch() {
        const q = input.value.trim();
        clearTimeout(topSupplierTimeout);
        if (q.length < 1) { dropdown.classList.add('hidden'); return; }

        topSupplierTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`${SUPPLIER_SEARCH_URL}?q=${encodeURIComponent(q)}`);
                const suppliers = await res.json();
                if (!suppliers.length) {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-gray-400 text-sm">No suppliers found</div>';
                    dropdown.classList.remove('hidden');
                    return;
                }
                dropdown.innerHTML = suppliers.map(s => `
                    <div class="px-3 py-2 hover:bg-gray-700 cursor-pointer border-b border-gray-700 top-supplier-option"
                         data-id="${s.id}"
                         data-name="${s.supplier_name}"
                         data-code="${s.supplier_code || ''}"
                         data-address="${(s.address || '').replace(/"/g, '&quot;')}"
                         data-terms="${(s.terms || '').replace(/"/g, '&quot;')}"
                         data-contact="${(s.contact_person || '').replace(/"/g, '&quot;')}"
                         data-tin="${(s.tin || '').replace(/"/g, '&quot;')}"
                         data-name2307="${(s.name_2307 || '').replace(/"/g, '&quot;')}"
                         data-shipping="${(s.shipping_address || '').replace(/"/g, '&quot;')}">
                        <div class="text-sm font-semibold text-white">${s.supplier_name}</div>
                        <div class="text-xs text-gray-400">${s.supplier_code || ''}${s.tin ? ' · TIN: ' + s.tin : ''}</div>
                    </div>
                `).join('');
                dropdown.classList.remove('hidden');

                dropdown.querySelectorAll('.top-supplier-option').forEach(opt => {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        input.value      = this.dataset.name;
                        idHidden.value   = this.dataset.id;
                        nameHidden.value = this.dataset.name;
                        addrHidden.value = this.dataset.address;
                        codeLine.textContent = '';
                        infoLine.classList.remove('hidden');
                        dropdown.classList.add('hidden');
                        const pt = document.getElementById('payment_terms');
                        if (pt) pt.value = this.dataset.terms || '';
                        const cn = document.getElementById('consignee');
                        if (cn && !cn.value.trim()) cn.value = this.dataset.contact || '';
                        const tinH = document.getElementById('supplier_tin_hidden');
                        if (tinH) tinH.value = this.dataset.tin || '';
                        const caEl = document.getElementById('consignee_address');
                        if (caEl && !caEl.value.trim()) caEl.value = this.dataset.address || '';
                        const daEl = document.getElementById('delivery_address');
                        if (daEl && !daEl.value.trim()) daEl.value = this.dataset.shipping || '';
                    });
                });
            } catch (err) {
                console.error('Top supplier search error:', err);
                dropdown.classList.add('hidden');
            }
        }, 250);
    }

    input.addEventListener('input', function() {
        nameHidden.value = this.value;
        idHidden.value   = '';
        addrHidden.value = '';
        infoLine.classList.add('hidden');
        doSearch();
    });
    input.addEventListener('focus', doSearch);
    input.addEventListener('blur', function() {
        nameHidden.value = this.value;
        setTimeout(() => dropdown.classList.add('hidden'), 200);
    });
}

// On form submit, copy top-level supplier to all item rows
function syncSupplierToItems() {
    const supplierId   = document.getElementById('supplier_id').value;
    const supplierName = document.getElementById('supplier_text').value;
    document.querySelectorAll('.supplier-id-input').forEach(el => el.value = supplierId);
    document.querySelectorAll('.supplier-name-input').forEach(el => el.value = supplierName);
}

// ======================== INIT ========================
document.addEventListener('DOMContentLoaded', function () {
    attachCalculationListeners();
    attachItemCodeListeners();
    recalculateAllTotals();
    document.querySelectorAll('.desc-input').forEach(attachDescAutocomplete);
    initTopSupplierSearch();

    // Sync supplier to items before form submit
    document.getElementById('poForm').addEventListener('submit', function() {
        syncSupplierToItems();
    });
});

function setPOType(type) {
    document.getElementById('poTypeInput').value = type;
    const itemsSection = document.getElementById('itemsTableSection');
    const serviceSection = document.getElementById('serviceDescSection');
    const serviceDesc = document.getElementById('serviceDescription');
    const btnItems = document.getElementById('btnTypeItems');
    const btnService = document.getElementById('btnTypeService');
    const brandWrapper = document.getElementById('brandFieldWrapper');

    if (type === 'service') {
        itemsSection.classList.add('hidden');
        serviceSection.classList.remove('hidden');
        serviceDesc.required = true;
        if (brandWrapper) brandWrapper.classList.add('hidden');
        itemsSection.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        btnService.className = 'px-5 py-2 rounded font-semibold transition bg-purple-600 text-white';
        btnItems.className = 'px-5 py-2 rounded font-semibold transition bg-gray-700 text-gray-300 hover:bg-gray-600';
    } else {
        itemsSection.classList.remove('hidden');
        serviceSection.classList.add('hidden');
        serviceDesc.required = false;
        if (brandWrapper) brandWrapper.classList.remove('hidden');
        itemsSection.querySelectorAll('.item-qty, .desc-input').forEach(el => el.setAttribute('required', ''));
        btnItems.className = 'px-5 py-2 rounded font-semibold transition bg-purple-600 text-white';
        btnService.className = 'px-5 py-2 rounded font-semibold transition bg-gray-700 text-gray-300 hover:bg-gray-600';
    }
}
</script>
@endsection