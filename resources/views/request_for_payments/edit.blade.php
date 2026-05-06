@extends('layouts.app')

@section('title', 'Edit Request for Payment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT REQUEST FOR PAYMENT</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">RFP NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $rfp->rfp_no }}</span>
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

        <form action="{{ route('request_for_payments.update', $rfp->id) }}" method="POST" id="rfpForm">
            @csrf
            @method('PUT')

            <!-- Company (hardcoded as Meatplus) -->
            <input type="hidden" name="company" value="MeatPlus">

            <!-- Payment Methods & Dates Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column - Payment Methods -->
                <div class="bg-gray-900 border border-gray-700 rounded p-4">
                    <label class="block font-semibold text-gray-300 mb-3">PAYMENT METHODS:</label>
                    @php
                        $currentMethods = is_array($rfp->payment_methods) ? $rfp->payment_methods : json_decode($rfp->payment_methods ?? '[]', true);
                    @endphp
                    <div class="space-y-2">
                        @foreach([
                            'managers_check' => "Manager's Check",
                            'regular_check' => 'Regular Check',
                            'wire_transfer' => 'Wire Transfer',
                            'fund_transfer' => 'Fund Transfer',
                            'pdc' => 'PDC (Post-Dated Check)',
                            'cash' => 'Cash',
                            'auto_debit' => 'Auto Debit',
                            'others' => 'Others',
                        ] as $value => $label)
                            <label class="flex items-center p-2 hover:bg-gray-800 rounded cursor-pointer">
                                <input type="checkbox" name="payment_methods[]" value="{{ $value }}" class="w-4 h-4 text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 rounded" {{ in_array($value, old('payment_methods', $currentMethods ?? [])) ? 'checked' : '' }}>
                                <span class="ml-3 text-gray-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Right Column - Dates and Reference Numbers -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date', $rfp->date->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">DUE DATE:</label>
                        <input type="date" name="due_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('due_date', $rfp->due_date ? $rfp->due_date->format('Y-m-d') : '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">RFP#:</label>
                        <input type="text" readonly class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-gray-300" value="{{ $rfp->rfp_no }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">LINKED PO:</label>
                        <select name="purchase_order_id" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- No PO Linked --</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}" {{ old('purchase_order_id', $rfp->purchase_order_id) == $po->id ? 'selected' : '' }}>
                                    {{ $po->po_no }} - {{ $po->supplier ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Main Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYEE (Vendor/Supplier): <span class="text-red-700">*</span></label>
                    <input type="text" name="payee" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payee', $rfp->payee) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">AMOUNT: <span class="text-red-700">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-300">&#8369;</span>
                        <input type="number" step="0.01" name="amount" class="w-full bg-gray-900 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount', $rfp->amount) }}" required>
                    </div>
                </div>
            </div>

            <!-- Particulars -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PARTICULARS:</label>
                <textarea name="particulars" rows="5" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter payment particulars...">{{ old('particulars', $rfp->particulars) }}</textarea>
            </div>

            <!-- Bank -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">BANK/S:</label>
                <input type="text" name="bank" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank', $rfp->bank) }}" placeholder="Bank name and account details">
            </div>

            <!-- APV and CV Numbers (For Finance Use — filled later) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">APV NO. (Account Payable Voucher):</label>
                    <input type="text" name="apv_no" disabled class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-gray-500 cursor-not-allowed" value="{{ old('apv_no', $rfp->apv_no) }}" placeholder="To be assigned by Finance">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">CV NO. (Check Voucher):</label>
                    <input type="text" name="cv_no" disabled class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-gray-500 cursor-not-allowed" value="{{ old('cv_no', $rfp->cv_no) }}" placeholder="To be assigned by Finance">
                </div>
            </div>

            <!-- PO Items Table -->
            @if($rfp->purchaseOrder && $rfp->purchaseOrder->items->count())
            <div class="mb-6">
                <div class="bg-gray-900 border border-gray-700 rounded overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-700 flex justify-between items-center">
                        <h3 class="font-semibold text-white"><i class="fas fa-boxes mr-2"></i>Purchase Order Items ({{ $rfp->purchaseOrder->po_no }})</h3>
                        <span class="text-sm text-gray-400">{{ $rfp->purchaseOrder->items->count() }} item(s)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-700 text-gray-300">
                                    <th class="px-4 py-2 text-left">#</th>
                                    <th class="px-4 py-2 text-left">Item Code</th>
                                    <th class="px-4 py-2 text-left">Description</th>
                                    <th class="px-4 py-2 text-center">Qty</th>
                                    <th class="px-4 py-2 text-center">UOM</th>
                                    <th class="px-4 py-2 text-right">Unit Price</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rfp->purchaseOrder->items as $idx => $item)
                                <tr class="{{ $idx % 2 === 0 ? 'bg-gray-800' : '' }} border-b border-gray-700">
                                    <td class="px-4 py-2 text-gray-400">{{ $item->item_no }}</td>
                                    <td class="px-4 py-2 text-white font-medium">{{ $item->item_code }}</td>
                                    <td class="px-4 py-2 text-gray-200">{{ $item->description }}</td>
                                    <td class="px-4 py-2 text-center text-gray-200">{{ number_format($item->qty, 2) }}</td>
                                    <td class="px-4 py-2 text-center text-gray-200">{{ $item->uom }}</td>
                                    <td class="px-4 py-2 text-right text-gray-200">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-white font-semibold">{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-700 font-bold text-white">
                                    <td colspan="6" class="px-4 py-2 text-right">Grand Total:</td>
                                    <td class="px-4 py-2 text-right">₱{{ number_format($rfp->purchaseOrder->items->sum('total'), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Requestor and Checker -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REQUESTED BY (Requestor):</label>
                    <input type="text" name="requested_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requested_by', $rfp->requested_by) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CHECKED BY (Department Head):</label>
                    <input type="text" name="checked_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('checked_by', $rfp->checked_by) }}">
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('request_for_payments.show', $rfp->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    Update Request for Payment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection