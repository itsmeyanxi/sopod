@extends('layouts.app')

@section('title', 'Create Request for Payment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">REQUEST FOR PAYMENT</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">RFP NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $rfpNo }}</span>
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

        <!-- Search SRR / PO Section -->
        @if(!$selectedPO)
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- SRR Search --}}
            <div class="bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-2"><i class="fas fa-receipt mr-2"></i>Search SRR <span class="text-xs text-gray-400">(auto-fills PO)</span></h3>
                <div class="relative">
                    <input type="text" id="srrSearchInput"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Search by SRR code, supplier, or PO no...">
                    <div id="srrSearchResults" class="hidden absolute z-20 w-full mt-1 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-64 overflow-y-auto"></div>
                </div>
                <div id="linkedSRRDisplay" class="mt-2 hidden p-2 bg-green-900/30 border border-green-700 rounded text-green-400 text-sm flex justify-between items-center">
                    <span id="linkedSRRText"></span>
                    <button type="button" id="unlinkSRR" class="text-red-400 hover:text-red-300 ml-2"><i class="fas fa-times"></i></button>
                </div>
                <input type="hidden" name="srr_id" id="srr_id" value="">
            </div>
            {{-- GRPO Direct Link --}}
            <div class="bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-2"><i class="fas fa-boxes mr-2"></i>Link GRPO <span class="text-xs text-gray-400">(search by GRPO#, PO#, supplier, brand)</span></h3>
                <div class="relative">
                    <input type="text" id="grpo_search_input" autocomplete="off" placeholder="Search GRPO#, PO#, supplier..."
                        class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <div id="grpo_search_results" class="hidden absolute z-30 w-full bg-gray-800 border border-gray-700 rounded mt-1 max-h-52 overflow-y-auto shadow-xl"></div>
                </div>
                <input type="hidden" name="live_chicken_id" id="live_chicken_id">
                <div id="grpo_linked_badge" class="hidden mt-2 p-2 bg-blue-900 border border-blue-700 rounded text-sm text-blue-200 flex justify-between items-center">
                    <span id="grpo_linked_text"></span>
                    <button type="button" onclick="clearGrpo()" class="text-red-400 hover:text-red-300 ml-2 text-xs">✕ Clear</button>
                </div>
            </div>
            {{-- PO Search --}}
            <div class="bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-2"><i class="fas fa-search mr-2"></i>Search PO directly</h3>
                <div class="relative">
                    <input type="text" id="poSearchInput"
                        class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Search by PO No, Supplier, or Company...">
                    <div id="poSearchResults" class="hidden absolute z-10 w-full mt-1 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-64 overflow-y-auto"></div>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('request_for_payments.store') }}" method="POST" id="rfpForm">
            @csrf
            <input type="hidden" id="maxRfpAmount" value="{{ $poAmount ? number_format($poAmount, 2, '.', '') : '' }}">

            <!-- Company (Hidden - MeatPlus Only) -->
            <input type="hidden" name="company" value="MeatPlus">

            <!-- Payment Methods & Dates Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column - Payment Methods -->
                <div class="bg-gray-900 border border-gray-700 rounded p-4">
                    <label class="block font-semibold text-gray-300 mb-3">PAYMENT METHODS:</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="managers_check" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Manager's Check</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="regular_check" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Regular Check</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="wire_transfer" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Wire Transfer</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="fund_transfer" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Fund Transfer</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="pdc" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">PDC (Post-Dated Check)</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="cash" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Cash</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="auto_debit" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Auto Debit</span>
                        </label>
                        <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                            <input type="checkbox" name="payment_methods[]" value="others" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded">
                            <span class="ml-3 text-gray-300">Others</span>
                        </label>
                    </div>
                </div>

                <!-- Right Column - Dates and Reference Numbers -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DUE DATE:</label>
                        <input type="date" name="due_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('due_date') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">RFP#:</label>
                        <input type="text" readonly class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-gray-300" value="{{ $rfpNo }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">LINKED PO:</label>
                        <input type="hidden" name="purchase_order_id" id="purchase_order_id" value="{{ old('purchase_order_id', $selectedPO->id ?? '') }}">
                        <div id="linkedPODisplay">
                            @if($selectedPO)
                                <div class="p-3 bg-green-50 border border-green-200 rounded text-green-700 flex justify-between items-center">
                                    <span><i class="fas fa-link mr-2"></i>{{ $selectedPO->po_no }}</span>
                                    <button type="button" id="unlinkPO" class="text-red-700 hover:text-red-700 text-sm"><i class="fas fa-times"></i></button>
                                </div>
                            @else
                                <div class="p-3 bg-gray-900 border border-gray-700 rounded text-gray-300">
                                    No PO linked — use search above
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 mb-1">PR NO.:</label>
                            <div id="rfpPrNoDisplay" class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-gray-300 text-sm">
                                {{ $selectedPO->pr_no ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 mb-1">PO NO.:</label>
                            <div id="rfpPoNoDisplay" class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-gray-300 text-sm">
                                {{ $selectedPO->po_no ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Type -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PAYMENT TYPE: <span class="text-red-700">*</span></label>
                <div class="flex gap-4">
                    @foreach(['full_payment' => 'Full Payment', 'downpayment' => 'Downpayment', 'cad' => 'Cash Against Documents (CAD)'] as $val => $label)
                    <label class="flex items-center gap-2 px-4 py-2 bg-gray-900 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                        <input type="radio" name="payment_type" value="{{ $val }}" class="text-purple-600" {{ old('payment_type') === $val ? 'checked' : '' }} required>
                        <span class="text-gray-300 text-sm">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Main Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 items-start">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYEE (Vendor/Supplier): <span class="text-red-700">*</span></label>
                    <div class="relative">
                        <input type="text" id="payeeSearch" autocomplete="off"
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Search vendor or supplier..."
                            value="{{ old('payee', $selectedPO ? (
                                $selectedPO->items->map(fn($item) => $item->supplierModel->supplier_name ?? $item->supplier_name ?? null)->filter()->unique()->implode(' / ')
                                ?: ($selectedPO->supplierModel->supplier_name ?? $selectedPO->supplier ?? '')
                            ) : '') }}">
                        <input type="hidden" name="payee" id="payeeHidden"
                            value="{{ old('payee', $selectedPO ? (
                                $selectedPO->items->map(fn($item) => $item->supplierModel->supplier_name ?? $item->supplier_name ?? null)->filter()->unique()->implode(' / ')
                                ?: ($selectedPO->supplierModel->supplier_name ?? $selectedPO->supplier ?? '')
                            ) : '') }}">
                        <div id="payeeDropdown" class="hidden absolute z-20 left-0 right-0 bg-gray-700 border border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto" style="top:100%"></div>
                    </div>
                    <input type="hidden" name="payment_terms" id="rfp_payment_terms" value="{{ old('payment_terms', $selectedPO->payment_terms ?? '') }}">
                    <div class="mt-1 text-sm text-gray-400">
                        Terms: <span id="termsText" class="text-yellow-400 font-semibold">{{ $selectedPO->payment_terms ?? '—' }}</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-4 mb-2">
                        <label class="font-semibold text-gray-300">AMOUNT: <span class="text-red-700">*</span></label>
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="radio" name="currency" value="PHP" id="currPHP" class="text-purple-600" {{ old('currency', 'PHP') === 'PHP' ? 'checked' : '' }}>
                            <span class="text-gray-300 text-sm">₱ PHP</span>
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="radio" name="currency" value="USD" id="currUSD" class="text-purple-600" {{ old('currency') === 'USD' ? 'checked' : '' }}>
                            <span class="text-gray-300 text-sm">$ USD</span>
                        </label>
                    </div>
                    <div class="relative">
                        <span id="currencySymbol" class="absolute left-3 top-2.5 text-gray-300">₱</span>
                        <input type="number" step="0.01" name="amount" class="w-full bg-gray-900 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount', $poAmount ? number_format($poAmount, 2, '.', '') : '') }}" required>
                    </div>
                </div>
            </div>

            <!-- Particulars -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PARTICULARS:</label>
                <textarea name="particulars" rows="5" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter payment particulars...">{{ old('particulars') }}</textarea>
            </div>

            <!-- Bank -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">BANK/S:</label>
                <input type="text" name="bank" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank') }}" placeholder="Bank name and account details">
            </div>

            <!-- APV and CV Numbers (For Finance Use — filled later) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">APV NO. (Account Payable Voucher):</label>
                    <input type="text" name="apv_no" disabled class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-gray-500 cursor-not-allowed" value="" placeholder="To be assigned by Finance">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">CV NO. (Check Voucher):</label>
                    <input type="text" name="cv_no" disabled class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-gray-500 cursor-not-allowed" value="" placeholder="To be assigned by Finance">
                </div>
            </div>

            <!-- PO Items / Service Description (shown when a PO is linked) -->
            @php $poIsService = $selectedPO && ($selectedPO->po_type ?? 'items') === 'service'; @endphp
            <div id="poItemsSection" class="{{ ($selectedPO && ($poIsService || $selectedPO->items->count())) ? '' : 'hidden' }} mb-6">
                <div class="bg-gray-900 border border-gray-700 rounded overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-700 flex justify-between items-center">
                        <h3 class="font-semibold text-white" id="poSectionTitle">
                            @if($poIsService)
                                <i class="fas fa-tools mr-2"></i>Service Description
                            @else
                                <i class="fas fa-boxes mr-2"></i>Purchase Order Items
                            @endif
                        </h3>
                        <span id="poItemsCount" class="text-sm text-gray-400">
                            @if($selectedPO && !$poIsService)
                                {{ $selectedPO->items->count() }} item(s)
                            @endif
                        </span>
                    </div>
                    <!-- Service description panel -->
                    <div id="poServiceDesc" class="{{ $poIsService ? '' : 'hidden' }} px-4 py-3 text-gray-200 whitespace-pre-line">
                        @if($poIsService) {{ $selectedPO->service_description }} @endif
                    </div>
                    <!-- Items table panel -->
                    <div id="poItemsTableWrap" class="{{ $poIsService ? 'hidden' : '' }} overflow-x-auto">
                        <table class="min-w-full text-sm" id="poItemsTable">
                            <thead>
                                <tr class="bg-gray-700 text-gray-300">
                                    <th class="px-4 py-2 text-left">#</th>
                                    <th class="px-4 py-2 text-left">Item Code</th>
                                    <th class="px-4 py-2 text-left">Description</th>
                                    <th class="px-4 py-2 text-left">Brand</th>
                                    <th class="px-4 py-2 text-center">Qty</th>
                                    <th class="px-4 py-2 text-center">UOM</th>
                                    <th class="px-4 py-2 text-right">Unit Price</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody id="poItemsBody">
                                @if($selectedPO && !$poIsService)
                                    @foreach($selectedPO->items as $idx => $item)
                                    <tr class="{{ $idx % 2 === 0 ? 'bg-gray-800' : '' }} border-b border-gray-700">
                                        <td class="px-4 py-2 text-gray-400">{{ $item->item_no }}</td>
                                        <td class="px-4 py-2 text-white font-medium">{{ $item->item_code }}</td>
                                        <td class="px-4 py-2 text-gray-200">{{ $item->description }}</td>
                                        <td class="px-4 py-2 text-gray-200">{{ $item->brand ?? '' }}</td>
                                        <td class="px-4 py-2 text-center text-gray-200">{{ number_format($item->qty, 2) }}</td>
                                        <td class="px-4 py-2 text-center text-gray-200">{{ $item->uom }}</td>
                                        <td class="px-4 py-2 text-right text-gray-200">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="px-4 py-2 text-right text-white font-semibold">{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-700 font-bold text-white">
                                    <td colspan="7" class="px-4 py-2 text-right">Grand Total:</td>
                                    <td class="px-4 py-2 text-right" id="poItemsGrandTotal">
                                        @if($selectedPO && !$poIsService)
                                            ₱{{ number_format($selectedPO->items->sum('total'), 2) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr id="phpEquivRow" class="hidden bg-gray-800 text-yellow-400 text-sm">
                                    <td colspan="6" class="px-4 py-2 text-right">
                                        PHP Equivalent (Rate: <input type="number" id="rfpExchangeRate" step="0.0001" min="0" value="{{ $currencyRates['USD'] ?? 57 }}" class="w-20 bg-gray-700 border border-gray-600 rounded px-1 py-0.5 text-white text-xs focus:outline-none" oninput="updatePhpEquiv()">):
                                    </td>
                                    <td colspan="2" class="px-4 py-2 text-right font-bold" id="phpEquivTotal">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Requestor and Checker -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REQUESTED BY (Requestor):</label>
                    <input type="text" name="requested_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requested_by') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CHECKED BY (Department Head):</label>
                    <input type="text" name="checked_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('checked_by') }}">
                </div>
            </div>

            <!-- Signature Section -->
            <div class="mb-6">
                <div class="border border-gray-700 rounded">
                    <div class="bg-gray-900 border-b border-gray-700 px-4 py-2 text-center font-semibold text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>FOR FINANCE USE ONLY
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By:</th>
                                <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By (Php 50,000 above):</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                                <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            </tr>
                            <tr class="bg-gray-700 text-gray-300 text-xs italic">
                                <td class="border border-gray-700 px-4 py-2 text-center">Finance Manager</td>
                                <td class="border border-gray-700 px-4 py-2 text-center">CFO / President</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('request_for_payments.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Request for Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Currency toggle
    document.querySelectorAll('input[name="currency"]').forEach(r => {
        r.addEventListener('change', function() {
            document.getElementById('currencySymbol').textContent = this.value === 'USD' ? '$' : '₱';
            updatePhpEquiv();
        });
    });

    // PO Search & Auto-fill
    const poSearchInput = document.getElementById('poSearchInput');
    const poSearchResults = document.getElementById('poSearchResults');

    if (poSearchInput) {
        let debounceTimer;

        poSearchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                poSearchResults.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('request_for_payments.search_pos') }}?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(pos => {
                        if (pos.length === 0) {
                            poSearchResults.innerHTML = '<div class="p-4 text-gray-300">No approved POs found</div>';
                            poSearchResults.classList.remove('hidden');
                            return;
                        }

                        let html = '<div class="divide-y divide-gray-700">';
                        pos.forEach(po => {
                            const fp = po.fully_paid;
                            const poAmt = parseFloat(po.amount||0);
                            const poPaid = parseFloat(po.paid_total||0);
                            const poRem = Math.max(0, poAmt - poPaid);
                            const poPaidPct = poAmt > 0 ? (poPaid/poAmt*100).toFixed(1) : null;
                            const poRemPct  = poAmt > 0 ? (poRem/poAmt*100).toFixed(1) : null;
                            html += `
                                <div class="po-result-item block p-3 hover:bg-gray-700 transition cursor-pointer${fp ? ' opacity-60' : ''}"
                                     data-id="${po.id}"
                                     data-po-no="${(po.po_no || '').replace(/"/g, '&quot;')}"
                                     data-supplier="${(po.supplier || '').replace(/"/g, '&quot;')}"
                                     data-company="${(po.company || '').replace(/"/g, '&quot;')}"
                                     data-amount="${po.amount || 0}"
                                     data-currency="${po.currency || 'PHP'}"
                                     data-terms="${(po.payment_terms || '').replace(/"/g, '&quot;')}"
                                     data-fully-paid="${fp ? '1' : '0'}"
                                     data-paid-total="${po.paid_total || 0}">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold text-purple-700">${po.po_no}${fp ? ' <span class="text-xs text-red-400 ml-1 font-normal">[FULLY PAID]</span>' : ''}</div>
                                            <div class="text-sm text-gray-300">${po.supplier}</div>
                                            <div class="text-xs text-gray-300">${po.company}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-300">${po.order_date || ''}</div>
                                            <div class="text-sm text-green-700">Total: ${po.currency || 'PHP'} ${parseFloat(po.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                            ${poPaid > 0 ? `<div class="text-xs text-yellow-400">Paid: ${poPaid.toLocaleString('en-US',{minimumFractionDigits:2})}${poPaidPct ? ` (${poPaidPct}%)` : ''}</div>` : ''}
                                            ${poPaid > 0 && !fp ? `<div class="text-xs text-blue-400">Remaining: ${poRem.toLocaleString('en-US',{minimumFractionDigits:2})}${poRemPct ? ` (${poRemPct}%)` : ''}</div>` : ''}
                                            ${fp ? `<div class="text-xs text-red-400">Fully Paid</div>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        poSearchResults.innerHTML = html;
                        poSearchResults.classList.remove('hidden');

                        // Attach click handlers for inline auto-fill
                        poSearchResults.querySelectorAll('.po-result-item').forEach(item => {
                            item.addEventListener('click', function() {
                                if (this.dataset.fullyPaid === '1') {
                                    alert('This PO has already been fully paid and cannot be used for a new RFP.');
                                    return;
                                }
                                const poId = this.dataset.id;
                                const poNo = this.dataset.poNo;
                                const supplier = this.dataset.supplier;
                                const company = this.dataset.company;
                                const amount = this.dataset.amount;
                                const paidTotal = parseFloat(this.dataset.paidTotal || 0);
                                const remaining = Math.max(0, parseFloat(amount) - paidTotal);
                                const poCurrency = this.dataset.currency || 'PHP';
                                const terms = this.dataset.terms || '';

                                // Set currency radio and symbol
                                const currRadio = document.getElementById(poCurrency === 'USD' ? 'currUSD' : 'currPHP');
                                if (currRadio) { currRadio.checked = true; document.getElementById('currencySymbol').textContent = poCurrency === 'USD' ? '$' : '₱'; }

                                // Fill hidden PO ID
                                document.getElementById('purchase_order_id').value = poId;

                                // Fill linked PO display
                                document.getElementById('linkedPODisplay').innerHTML = `
                                    <div class="p-3 bg-green-50 border border-green-200 rounded text-green-700 flex justify-between items-center">
                                        <span><i class="fas fa-link mr-2"></i>${poNo}</span>
                                        <button type="button" id="unlinkPO" class="text-red-700 hover:text-red-700 text-sm"><i class="fas fa-times"></i></button>
                                    </div>
                                `;
                                attachUnlinkHandler();

                                // Fill payee from supplier
                                const payeeSearchEl = document.getElementById('payeeSearch');
                                const payeeHiddenEl = document.getElementById('payeeHidden');
                                if (payeeSearchEl) payeeSearchEl.value = supplier;
                                if (payeeHiddenEl) payeeHiddenEl.value = supplier;

                                // Auto-fill due_date from payment_terms
                                const dueDateEl = document.querySelector('input[name="due_date"]');
                                if (dueDateEl && terms) {
                                    const days = parseInt((terms.match(/(\d+)/) || [])[1] || 0);
                                    if (days > 0) {
                                        const d = new Date(); d.setDate(d.getDate() + days);
                                        dueDateEl.value = d.toISOString().split('T')[0];
                                    }
                                }

                                // Fill amount with remaining balance (full amount if no prior payments)
                                const amountInput = document.querySelector('input[name="amount"]');
                                const maxAmt = parseFloat(amount).toFixed(2);
                                if (amountInput) { amountInput.value = remaining.toFixed(2); }
                                document.getElementById('maxRfpAmount').value = maxAmt;

                                // Fill company radio
                                if (company) {
                                    const radios = document.querySelectorAll('input[name="company"]');
                                    radios.forEach(radio => {
                                        if (radio.value === company) {
                                            radio.checked = true;
                                        }
                                    });
                                }

                                // Particulars will be auto-filled by loadPOItems from GRPO/PO remarks

                                // Fetch and display PO items
                                loadPOItems(poId);

                                // Hide search
                                poSearchResults.classList.add('hidden');
                                poSearchInput.value = poNo;
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        poSearchResults.innerHTML = '<div class="p-4 text-red-700">Error searching POs</div>';
                        poSearchResults.classList.remove('hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (poSearchInput && !poSearchInput.contains(e.target) && !poSearchResults.contains(e.target)) {
                poSearchResults.classList.add('hidden');
            }
        });
    }

    // GRPO search
    (function() {
        const inp = document.getElementById('grpo_search_input');
        const res = document.getElementById('grpo_search_results');
        const hid = document.getElementById('live_chicken_id');
        const badge = document.getElementById('grpo_linked_badge');
        const badgeTxt = document.getElementById('grpo_linked_text');
        if (!inp) return;
        let t;
        inp.addEventListener('input', function() {
            clearTimeout(t);
            const q = this.value.trim();
            if (q.length < 1) { res.classList.add('hidden'); return; }
            t = setTimeout(async () => {
                const data = await fetch(`{{ route('live_chickens.search') }}?q=${encodeURIComponent(q)}`).then(r=>r.json());
                if (!data.length) { res.classList.add('hidden'); return; }
                res.innerHTML = data.map(lc => {
                    const fp = lc.fully_paid;
                    const lcAmt = parseFloat(lc.amount||0);
                    const lcPaid = parseFloat(lc.paid_total||0);
                    const lcRemaining = Math.max(0, lcAmt - lcPaid);
                    const paidPct = lcAmt > 0 ? (lcPaid/lcAmt*100).toFixed(1) : null;
                    const remPct  = lcAmt > 0 ? (lcRemaining/lcAmt*100).toFixed(1) : null;
                    return `
                    <div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm border-b border-gray-700 grpo-opt${fp ? ' opacity-60' : ''}"
                         data-id="${lc.id}" data-grpo="${lc.grpo_no||''}" data-po="${lc.po_no||''}" data-ref="${lc.reference_number||''}" data-po-id="${lc.po_id||''}"
                         data-supplier="${lc.supplier||''}" data-delivery-date="${lc.delivery_date||''}"
                         data-amount="${lcAmt}" data-paid-total="${lcPaid}"
                         data-fully-paid="${fp ? '1' : '0'}">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-purple-300 font-mono font-bold">${lc.grpo_no||'—'}${fp ? ' <span class="text-red-400 text-xs font-normal">[FULLY PAID]</span>' : ''}</span>
                                <span class="text-gray-400 ml-2">PO: ${lc.po_no||''}</span>
                                <span class="text-yellow-400 ml-2">${lc.brand||''}</span>
                            </div>
                            <div class="text-right text-xs">
                                ${lcAmt > 0 ? `<div class="text-gray-400">Total: ${lcAmt.toLocaleString('en-US',{minimumFractionDigits:2})}</div>` : ''}
                                ${lcPaid > 0 ? `<div class="text-yellow-400">Paid: ${lcPaid.toLocaleString('en-US',{minimumFractionDigits:2})}${paidPct ? ` (${paidPct}%)` : ''}</div>` : ''}
                                ${lcPaid > 0 && !fp ? `<div class="text-blue-400">Remaining: ${lcRemaining.toLocaleString('en-US',{minimumFractionDigits:2})}${remPct ? ` (${remPct}%)` : ''}</div>` : ''}
                            </div>
                        </div>
                    </div>`;
                }).join('');
                res.classList.remove('hidden');
                res.querySelectorAll('.grpo-opt').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        if (this.dataset.fullyPaid === '1') {
                            alert('This GRPO has already been fully paid and cannot be used for a new RFP.');
                            return;
                        }
                        hid.value = this.dataset.id;
                        inp.value = this.dataset.grpo;
                        badgeTxt.textContent = `GRPO: ${this.dataset.grpo} | PO: ${this.dataset.po}`;
                        badge.classList.remove('hidden');
                        res.classList.add('hidden');
                        const poInput = document.getElementById('poSearchInput');
                        if (poInput && this.dataset.po) poInput.value = this.dataset.po;
                        // Auto-fill payee from GRPO supplier
                        if (this.dataset.supplier) {
                            const payeeEl = document.getElementById('payeeSearch');
                            const payeeHid = document.getElementById('payeeHidden');
                            if (payeeEl) payeeEl.value = this.dataset.supplier;
                            if (payeeHid) payeeHid.value = this.dataset.supplier;
                        }
                        // Auto-fill due_date from delivery_date
                        if (this.dataset.deliveryDate) {
                            const dueDateEl = document.querySelector('input[name="due_date"]');
                            if (dueDateEl) dueDateEl.value = this.dataset.deliveryDate;
                        }
                        // Auto-fill amount with remaining balance
                        if (this.dataset.amount) {
                            const grpoAmt = parseFloat(this.dataset.amount);
                            const grpoPaid = parseFloat(this.dataset.paidTotal || 0);
                            const grpoRemaining = Math.max(0, grpoAmt - grpoPaid);
                            const amtInput = document.querySelector('input[name="amount"]');
                            if (amtInput) amtInput.value = grpoRemaining.toFixed(2);
                            document.getElementById('maxRfpAmount').value = grpoAmt.toFixed(2);
                        }
                        // Auto-load PO items and update linked PO display
                        if (this.dataset.poId) {
                            document.getElementById('purchase_order_id').value = this.dataset.poId;
                            loadPOItems(this.dataset.poId);
                        }
                        if (this.dataset.po) {
                            document.getElementById('linkedPODisplay').innerHTML =
                                `<div class="p-3 bg-green-900 border border-green-700 rounded text-green-300 flex justify-between items-center">
                                    <span><i class="fas fa-link mr-2"></i>${this.dataset.po} <span class="text-xs text-blue-300">(via GRPO: ${this.dataset.grpo})</span></span>
                                    <button type="button" id="unlinkPO" class="text-red-400 text-sm"><i class="fas fa-times"></i></button>
                                </div>`;
                            document.getElementById('unlinkPO')?.addEventListener('click', () => {
                                document.getElementById('purchase_order_id').value = '';
                                document.getElementById('linkedPODisplay').innerHTML =
                                    '<div class="p-3 bg-gray-900 border border-gray-700 rounded text-gray-300">No PO linked — use search above</div>';
                            });
                        }
                    });
                });
            }, 250);
        });
        inp.addEventListener('blur', () => setTimeout(() => res.classList.add('hidden'), 200));
    })();

    window.clearGrpo = function() {
        document.getElementById('live_chicken_id').value = '';
        document.getElementById('grpo_search_input').value = '';
        document.getElementById('grpo_linked_badge').classList.add('hidden');
    };

    // Load PO items via AJAX
    function loadPOItems(poId) {
        const section    = document.getElementById('poItemsSection');
        const tbody      = document.getElementById('poItemsBody');
        const countEl    = document.getElementById('poItemsCount');
        const totalEl    = document.getElementById('poItemsGrandTotal');
        const titleEl    = document.getElementById('poSectionTitle');
        const serviceDiv = document.getElementById('poServiceDesc');
        const tableWrap  = document.getElementById('poItemsTableWrap');

        fetch(`/request_for_payments/po-items/${poId}`)
            .then(r => r.json())
            .then(data => {
                // Fill PR/PO display rows
                document.getElementById('rfpPrNoDisplay').textContent = data.pr_no || '—';
                document.getElementById('rfpPoNoDisplay').textContent = data.po_no || '—';

                // Set exchange rate if PO has one
                if (data.exchange_rate) document.getElementById('rfpExchangeRate').value = data.exchange_rate;

                // Fill terms
                const termsVal = data.terms || '';
                document.getElementById('rfp_payment_terms').value = termsVal;
                document.getElementById('termsText').textContent = termsVal || '—';

                // Auto-fill particulars with aggregated remarks if empty
                const particularsEl = document.querySelector('textarea[name="particulars"]');
                if (particularsEl && data.remarks_hint) {
                    particularsEl.value = data.remarks_hint;
                }

                if (data.po_type === 'service') {
                    titleEl.innerHTML  = '<i class="fas fa-tools mr-2"></i>Service Description';
                    serviceDiv.textContent = data.service_description || '';
                    serviceDiv.classList.remove('hidden');
                    tableWrap.classList.add('hidden');
                    countEl.textContent = '';
                    section.classList.remove('hidden');
                    return;
                }
                // items PO
                const items = data.items || [];
                if (!items.length) { section.classList.add('hidden'); return; }
                const sourceLabel = data.source === 'grpo' ? 'GRPO Items (Actual Received)' : 'Purchase Order Items';
                titleEl.innerHTML = `<i class="fas fa-boxes mr-2"></i>${sourceLabel}`;
                serviceDiv.classList.add('hidden');
                tableWrap.classList.remove('hidden');
                const currSym = document.querySelector('input[name="currency"]:checked')?.value === 'USD' ? '$' : '₱';
                let html = '';
                let grandTotal = 0;
                items.forEach((item, idx) => {
                    const total = parseFloat(item.total || 0);
                    grandTotal += total;
                    html += `<tr class="${idx % 2 === 0 ? 'bg-gray-800' : ''} border-b border-gray-700">
                        <td class="px-4 py-2 text-gray-400">${item.item_no}</td>
                        <td class="px-4 py-2 text-white font-medium">${item.item_code || ''}</td>
                        <td class="px-4 py-2 text-gray-200">${item.description || ''}</td>
                        <td class="px-4 py-2 text-gray-200">${item.brand || ''}</td>
                        <td class="px-4 py-2 text-center text-gray-200">${parseFloat(item.qty || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="px-4 py-2 text-center text-gray-200">${item.uom || ''}</td>
                        <td class="px-4 py-2 text-right text-gray-200">${parseFloat(item.unit_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="px-4 py-2 text-right text-white font-semibold">${total.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    </tr>`;
                });
                tbody.innerHTML = html;
                countEl.textContent = items.length + ' item(s)' + (data.source === 'grpo' ? ' from GRPO' : '');
                totalEl.textContent = currSym + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
                window._rfpGrandTotal = grandTotal;
                updatePhpEquiv();
                section.classList.remove('hidden');
            })
            .catch(err => {
                console.error('Error loading PO items:', err);
                section.classList.add('hidden');
            });
    }

    // Unlink PO handler
    function attachUnlinkHandler() {
        const unlinkBtn = document.getElementById('unlinkPO');
        if (unlinkBtn) {
            unlinkBtn.addEventListener('click', function() {
                document.getElementById('purchase_order_id').value = '';
                document.getElementById('maxRfpAmount').value = '';
                document.getElementById('linkedPODisplay').innerHTML = `
                    <div class="p-3 bg-gray-900 border border-gray-700 rounded text-gray-300">
                        No PO linked — use search above
                    </div>
                `;
                const searchInput = document.getElementById('poSearchInput');
                if (searchInput) searchInput.value = '';
                // Hide PO items
                document.getElementById('poItemsSection').classList.add('hidden');
            });
        }
    }
    attachUnlinkHandler();

    // Sync payee hidden on submit
    document.getElementById('rfpForm').addEventListener('submit', function() {
        const ph = document.getElementById('payeeHidden');
        const ps = document.getElementById('payeeSearch');
        if (ph && ps && !ph.value) ph.value = ps.value;
    });

    // Payee searchable dropdown
    const payeeOptions = @json($payeeOptions);
    const payeeSearch   = document.getElementById('payeeSearch');
    const payeeHidden   = document.getElementById('payeeHidden');
    const payeeDrop     = document.getElementById('payeeDropdown');

    payeeSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        payeeHidden.value = this.value;
        if (!q) { payeeDrop.classList.add('hidden'); return; }
        const hits = payeeOptions.filter(o => o.label.toLowerCase().includes(q)).slice(0, 15);
        if (!hits.length) { payeeDrop.classList.add('hidden'); return; }
        payeeDrop.innerHTML = hits.map(o =>
            `<div class="px-3 py-2 hover:bg-gray-600 cursor-pointer text-sm text-gray-200 flex justify-between" data-label="${o.label.replace(/"/g,'&quot;')}">
                <span>${o.label}</span><span class="text-xs text-gray-400 ml-2">${o.type}</span>
            </div>`
        ).join('');
        payeeDrop.classList.remove('hidden');
        payeeDrop.querySelectorAll('[data-label]').forEach(opt => {
            opt.addEventListener('mousedown', function(e) {
                e.preventDefault();
                payeeSearch.value = this.dataset.label;
                payeeHidden.value = this.dataset.label;
                payeeDrop.classList.add('hidden');
            });
        });
    });
    payeeSearch.addEventListener('blur', () => setTimeout(() => payeeDrop.classList.add('hidden'), 150));
    window.updatePhpEquiv = function() {
        const isUSD = document.querySelector('input[name="currency"]:checked')?.value === 'USD';
        const row = document.getElementById('phpEquivRow');
        if (!row) return;
        if (!isUSD) { row.classList.add('hidden'); return; }
        const total = window._rfpGrandTotal || 0;
        const rate = parseFloat(document.getElementById('rfpExchangeRate')?.value) || {{ $currencyRates['USD'] ?? 57 }};
        document.getElementById('phpEquivTotal').textContent = '₱' + (total * rate).toLocaleString('en-US', {minimumFractionDigits: 2});
        row.classList.remove('hidden');
    };

    const origPayeeObserver = new MutationObserver(() => {});

    // SRR Search
    const srrInput = document.getElementById('srrSearchInput');
    const srrResults = document.getElementById('srrSearchResults');
    const srrIdHidden = document.getElementById('srr_id');
    const linkedSRRDisplay = document.getElementById('linkedSRRDisplay');
    const linkedSRRText = document.getElementById('linkedSRRText');
    const unlinkSRR = document.getElementById('unlinkSRR');

    if (srrInput) {
        let srrTimer;
        srrInput.addEventListener('input', function () {
            clearTimeout(srrTimer);
            const q = this.value.trim();
            if (q.length < 2) { srrResults.classList.add('hidden'); return; }
            srrTimer = setTimeout(() => {
                fetch(`{{ route('request_for_payments.search_srrs') }}?search=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (!data.length) {
                            srrResults.innerHTML = '<div class="p-3 text-gray-400 text-sm">No SRRs found</div>';
                            srrResults.classList.remove('hidden'); return;
                        }
                        srrResults.innerHTML = data.map(s => `
                            <div class="srr-item p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700 text-sm${s.fully_paid ? ' opacity-60' : ''}"
                                 data-id="${s.id}" data-code="${s.srr_code}" data-po="${s.po_no || ''}" data-supplier="${s.supplier_name || ''}"
                                 data-fully-paid="${s.fully_paid ? '1' : '0'}">
                                <div class="font-semibold text-white">${s.srr_code}${s.fully_paid ? ' <span class="text-xs text-red-400 font-normal">[FULLY PAID]</span>' : ''}</div>
                                <div class="text-gray-400">${s.supplier_name || ''} ${s.po_no ? '· PO: '+s.po_no : ''}</div>
                            </div>`).join('');
                        srrResults.classList.remove('hidden');
                        srrResults.querySelectorAll('.srr-item').forEach(item => {
                            item.addEventListener('click', function () {
                                if (this.dataset.fullyPaid === '1') {
                                    alert('This SRR has already been fully paid and cannot be used for a new RFP.');
                                    return;
                                }
                                srrIdHidden.value = this.dataset.id;
                                srrInput.value = this.dataset.code;
                                linkedSRRText.textContent = `SRR: ${this.dataset.code}`;
                                linkedSRRDisplay.classList.remove('hidden');
                                srrResults.classList.add('hidden');
                                // Auto-fill payee from SRR supplier
                                const srrSupplier = this.dataset.supplier;
                                if (srrSupplier) {
                                    const payeeEl = document.getElementById('payeeSearch');
                                    const payeeHid = document.getElementById('payeeHidden');
                                    if (payeeEl) payeeEl.value = srrSupplier;
                                    if (payeeHid) payeeHid.value = srrSupplier;
                                }
                                // Auto-fill PO if SRR has a po_no
                                const poNo = this.dataset.po;
                                if (poNo) {
                                    fetch(`{{ route('request_for_payments.search_pos') }}?search=${encodeURIComponent(poNo)}`)
                                        .then(r => r.json())
                                        .then(pos => {
                                            if (pos.length) {
                                                const po = pos[0];
                                                document.getElementById('purchase_order_id').value = po.id;
                                                document.getElementById('linkedPODisplay').innerHTML =
                                                    `<div class="p-3 bg-green-50 border border-green-200 rounded text-green-700 flex justify-between items-center">
                                                        <span><i class="fas fa-link mr-2"></i>${po.po_no}</span>
                                                        <button type="button" id="unlinkPO" class="text-red-700 text-sm"><i class="fas fa-times"></i></button>
                                                    </div>`;
                                                document.getElementById('unlinkPO')?.addEventListener('click', () => {
                                                    document.getElementById('purchase_order_id').value = '';
                                                    document.getElementById('linkedPODisplay').innerHTML =
                                                        '<div class="p-3 bg-gray-900 border border-gray-700 rounded text-gray-300">No PO linked</div>';
                                                });
                                                // Also fill due_date from PO payment_terms
                                                const dueDateEl = document.querySelector('input[name="due_date"]');
                                                if (dueDateEl && po.payment_terms) {
                                                    const days = parseInt((po.payment_terms.match(/(\d+)/) || [])[1] || 0);
                                                    if (days > 0) {
                                                        const d = new Date(); d.setDate(d.getDate() + days);
                                                        dueDateEl.value = d.toISOString().split('T')[0];
                                                    }
                                                }
                                            }
                                        });
                                }
                            });
                        });
                    });
            }, 300);
        });

        unlinkSRR?.addEventListener('click', () => {
            srrIdHidden.value = '';
            srrInput.value = '';
            linkedSRRDisplay.classList.add('hidden');
        });
        srrInput.addEventListener('blur', () => setTimeout(() => srrResults.classList.add('hidden'), 150));
    }
});
</script>
@endsection
