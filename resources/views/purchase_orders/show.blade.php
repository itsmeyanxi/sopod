@extends('layouts.app')

@section('title', 'View Purchase Order')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">PURCHASE ORDER</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-300">PO NO:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $purchaseOrder->po_no }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($purchaseOrder->status === 'pending') bg-yellow-600 text-white
                    @elseif($purchaseOrder->status === 'approved') bg-green-600 text-white
                    @elseif($purchaseOrder->status === 'rejected') bg-red-600 text-white
                    @else bg-blue-600 text-white
                    @endif">
                    {{ ucfirst($purchaseOrder->status) }}
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Approval Actions -->
        @if(auth()->user()->canApprovePurchaseOrders() && $purchaseOrder->status === 'pending')
            <div class="flex gap-3 mb-4">
                <form action="{{ route('purchase_orders.approve', $purchaseOrder->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        Approve
                    </button>
                </form>
                <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                    Reject
                </button>
            </div>
        @endif

        @if($purchaseOrder->status === 'approved' && $purchaseOrder->approver)
            <div class="p-4 bg-green-900/20 border border-green-700 rounded mb-4">
                <p class="text-green-400">
                    Approved by {{ $purchaseOrder->approver->name }}
                    on {{ $purchaseOrder->approved_at->format('M d, Y h:i A') }}
                </p>
            </div>
        @endif

        @if($purchaseOrder->status === 'rejected' && $purchaseOrder->approver)
            <div class="p-4 bg-red-900/20 border border-red-700 rounded mb-4">
                <p class="text-red-400">
                    Rejected by {{ $purchaseOrder->approver->name }}
                    on {{ $purchaseOrder->approved_at->format('M d, Y h:i A') }}
                </p>
                @if($purchaseOrder->rejection_reason)
                    <p class="text-gray-300 mt-2">
                        <strong>Reason:</strong> {{ $purchaseOrder->rejection_reason }}
                    </p>
                @endif
            </div>
        @endif

        <!-- Company -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-2">COMPANY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->company }}</p>
        </div>

        <!-- Form Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">SUPPLIER:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->supplier ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">SUPPLIER ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[60px]">{{ $purchaseOrder->supplier_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->consignee ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[60px]">{{ $purchaseOrder->consignee_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[60px]">{{ $purchaseOrder->delivery_address ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">ORDER DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->order_date->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">EXPECTED DELIVERY DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('F d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">PAYMENT TERMS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->payment_terms ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">LOCATION:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->location ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">HOUSE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->house ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">PR#:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">
                        @if($purchaseOrder->purchaseRequest)
                            <a href="{{ route('purchase_requests.show', $purchaseOrder->purchaseRequest->id) }}" class="text-purple-400 hover:text-purple-300">
                                {{ $purchaseOrder->pr_no }}
                            </a>
                        @else
                            {{ $purchaseOrder->pr_no ?? 'N/A' }}
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">LC PRICE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->lc_price ? '₱' . number_format($purchaseOrder->lc_price, 2) : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CREATED BY:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->creator->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white mb-2">Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-700">
                    <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="border border-gray-700 px-4 py-3">NO.</th>
                            <th class="border border-gray-700 px-4 py-3">ITEM CODE</th>
                            <th class="border border-gray-700 px-4 py-3">QTY</th>
                            <th class="border border-gray-700 px-4 py-3">UOM</th>
                            <th class="border border-gray-700 px-4 py-3">DESCRIPTION</th>
                            <th class="border border-gray-700 px-4 py-3">UNIT PRICE</th>
                            <th class="border border-gray-700 px-4 py-3">TAX</th>
                            <th class="border border-gray-700 px-4 py-3">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-300 divide-y divide-gray-700">
                        @foreach($purchaseOrder->items as $item)
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-4 py-3 text-center">{{ $item->item_no }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->item_code ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ number_format($item->qty, 2) }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->uom }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->description }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->unit_price ? '₱' . number_format($item->unit_price, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->tax ? '₱' . number_format($item->tax, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->total ? '₱' . number_format($item->total, 2) : 'N/A' }}</td>
                            </tr>
                        @endforeach
                        @if($purchaseOrder->items->count() > 0)
                            <tr class="font-semibold bg-gray-700">
                                <td colspan="7" class="border border-gray-700 px-4 py-3 text-right">GRAND TOTAL:</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">₱{{ number_format($purchaseOrder->items->sum('total'), 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Remarks -->
        <div class="mb-6">
            <label class="block font-semibold text-white mb-2">REMARKS:</label>
            <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[100px]">
                {{ $purchaseOrder->remarks ?? 'No remarks provided' }}
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
        <div class="flex justify-between items-center">
            <a href="{{ route('purchase_orders.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-4">
                <a href="{{ route('purchase_orders.edit', $purchaseOrder->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                @if($purchaseOrder->status === 'approved')
                    <a href="{{ route('purchase_orders.print', $purchaseOrder->id) }}" target="_blank" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-print mr-1"></i> Print
                    </a>
                @else
                    <span class="bg-gray-600 text-gray-400 px-6 py-2 rounded cursor-not-allowed" title="Must be approved before printing">
                        <i class="fas fa-print mr-1"></i> Print
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Purchase Order</h3>
        <form action="{{ route('purchase_orders.reject', $purchaseOrder->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" rows="4"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Confirm Reject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<style>
@media print {
    .bg-gray-800, .bg-gray-700, .bg-blue-600, .bg-green-600, button, a {
        display: none !important;
    }
    .container {
        max-width: 100%;
    }
}
</style>
@endsection
