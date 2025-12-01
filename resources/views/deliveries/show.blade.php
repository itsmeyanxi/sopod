@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-10 bg-gray-900 min-h-screen text-gray-100">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">Delivery Details</h1>
        <div class="flex gap-3">
            <a href="{{ route('deliveries.print', $delivery->id) }}"
               target="_blank"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
               🖨️ Print Delivery
            </a>
            <button type="button"
                    onclick="exportExcel({{ $delivery->id }})"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded transition">
                     📥 Export Excel
            </button>
            <a href="{{ route('deliveries.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
               ← Back
            </a>
        </div>
    </div>

    <!-- Delivery Info -->
    <div class="bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- DR No -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">DR No</label>
                <input type="text" value="{{ $delivery->dr_no }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Order -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Order No</label>
                <input type="text" value="{{ $delivery->sales_order_number }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Delivery Batch -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Delivery Batch</label>
                @if($delivery->delivery_batch)
                    @php
                        $parts = explode('-', $delivery->delivery_batch);
                        $dateStr = end($parts);
                        try {
                            $batchDisplay = \Carbon\Carbon::parse($dateStr)->format('M d, Y');
                        } catch (\Exception $e) {
                            $batchDisplay = $delivery->delivery_batch;
                        }
                    @endphp
                    <div class="w-full px-4 py-2 rounded-lg bg-purple-900/30 border border-purple-700 text-purple-300 flex items-center gap-2">
                        <span class="text-lg">📦</span>
                        <span>{{ $batchDisplay }}</span>
                    </div>
                @else
                    <input type="text" value="Single Delivery" 
                           class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-400" readonly>
                @endif
            </div>

            <!-- Customer Code -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Customer Code</label>
                <input type="text" value="{{ $delivery->customer_code ?? $delivery->salesOrder?->customer_code ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Customer Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Customer Name</label>
                <input type="text" value="{{ $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? $delivery->salesOrder?->client_name ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- TIN  -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">TIN</label>
                <input type="text" value="{{ $delivery->tin_no ?? $delivery->salesOrder?->customer?->tin_no ?? '—' }}"  readonly
                    class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" />
            </div>

            <!-- Branch -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Branch</label>
                <input type="text" value="{{ $delivery->branch ?? $delivery->salesOrder?->branch ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Representative -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Representative</label>
                <input type="text" value="{{ $delivery->sales_rep ?? $delivery->salesOrder?->sales_rep ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Executive -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Executive</label>
                <input type="text" value="{{ $delivery->sales_executive ?? $delivery->salesOrder?->sales_executive ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Plate No -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Plate No</label>
                <input type="text" value="{{ $delivery->plate_no ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Invoice -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Invoice No</label>
                <input type="text" value="{{ $delivery->sales_invoice_no ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- PO Number -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">PO Number</label>
                <input type="text" value="{{ $delivery->po_number ?? $delivery->salesOrder?->po_number ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Request Delivery Date -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Request Delivery Date</label>
                <input type="text" 
                  value="{{ $delivery->request_delivery_date ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('m/d/Y') : ($delivery->salesOrder?->request_delivery_date ? \Carbon\Carbon::parse($delivery->salesOrder->request_delivery_date)->format('m/d/Y') : '—') }}"
                  class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Status</label>
                <input type="text" value="{{ $delivery->status ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Approved By -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Approved By</label>
                <input type="text" value="{{ $delivery->approved_by ?? $delivery->salesOrder?->approved_by ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Additional Instructions -->
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Additional Instructions</label>
                <textarea class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100"
                        rows="3" readonly>{{ $delivery->additional_instructions ?? $delivery->salesOrder?->additional_instructions ?? '—' }}</textarea>
            </div>

            <!-- 📎 Attachment Display -->
           @if($delivery->attachment)
                <div class="md:col-span-2">
                    <h3 class="font-semibold mb-2">Attached Image:</h3>
                    <img 
                        src="{{ asset('delivery_images/' . $delivery->attachment) }}" 
                        alt="Delivery Attachment" 
                        class="rounded-lg shadow-md cursor-pointer hover:opacity-90 transition" 
                        style="max-width: 400px;"
                        onclick="openImageModal('{{ asset('delivery_images/' . $delivery->attachment) }}')"
                    >
                </div>
            @endif

        </div>

        <h3 class="text-lg font-semibold text-white mt-10 mb-4 border-b border-gray-700 pb-1">Delivery Items</h3>

        @php
            $items = $delivery->items;
            $grandTotal = 0;
            foreach ($items as $item) {
                $grandTotal += $item->total_amount ?? 0;
            }
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full border border-gray-700 rounded-md overflow-hidden text-sm">
                <thead class="bg-gray-700 text-gray-300 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Item Code</th>
                        <th class="px-4 py-2 text-left">Description</th>
                        <th class="px-4 py-2 text-left">Brand</th>
                        <th class="px-4 py-2 text-left">Category</th>
                        <th class="px-4 py-2 text-right">Quantity</th>
                        <th class="px-4 py-2 text-center">UOM</th>
                        <th class="px-4 py-2 text-right">Unit Price</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-900">
                    @forelse($items as $item)
                        <tr class="border-b border-gray-800 hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $item->item_code ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $item->item_description ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $item->brand ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $item->item_category ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($item->quantity ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-center">{{ $item->uom ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($item->total_amount ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                No items found for this delivery
                            </td>
                        </tr>
                    @endforelse
                    
                    @if($items->count() > 0)
                        <!-- Grand Total Row -->
                        <tr class="bg-gray-800 font-semibold">
                            <td colspan="7" class="px-4 py-3 text-right">Grand Total:</td>
                            <td class="px-4 py-3 text-right text-green-400">₱{{ number_format($grandTotal, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Back Button -->
        <div class="flex justify-end mt-8">
            <a href="{{ route('deliveries.index') }}" 
               class="bg-gray-600 hover:bg-gray-500 text-white px-6 py-2 rounded-lg transition">
                Back to List
            </a>
        </div>
    </div>
</div>

<!-- 🖼️ Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-50" onclick="closeImageModal()">
    <div class="relative max-w-7xl max-h-screen p-4">
        <button onclick="closeImageModal()" 
                class="absolute top-4 right-4 bg-red-600 hover:bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center text-xl font-bold">
            ✕
        </button>
        <img id="modalImage" src="" alt="Full Size Image" class="max-w-full max-h-screen object-contain rounded-lg">
    </div>
</div>

<script>
function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModal').classList.remove('hidden');
    document.getElementById('imageModal').classList.add('flex');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
    document.getElementById('imageModal').classList.remove('flex');
}

function exportExcel(deliveryId) {
    if (!deliveryId) return;
    let url = '{{ route("deliveries.exportDeliveryItemsExcel") }}?delivery_id=' + deliveryId;
    window.location.href = url;
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>
@endsection