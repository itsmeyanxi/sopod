@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">
            📦 Delivery Batches — {{ $salesOrder->sales_order_number }}
        </h1>
        <a href="{{ route('sales_orders.show', $salesOrder->id) }}" 
           class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-sm">
            ← Back
        </a>
    </div>

    <!-- SO Summary -->
    <div class="bg-gray-800 rounded-lg p-4 mb-6 border border-gray-700">
        <div class="grid md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-400">Customer:</span>
                <span class="font-semibold ml-2">{{ $salesOrder->customer->customer_name }}</span>
            </div>
            <div>
                <span class="text-gray-400">Total Amount:</span>
                <span class="font-semibold ml-2 text-green-400">₱{{ number_format($salesOrder->total_amount, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-400">Total Batches:</span>
                <span class="font-semibold ml-2">{{ $deliveryBatches->count() }}</span>
            </div>
            <div>
                <span class="text-gray-400">Total Deliveries:</span>
                <span class="font-semibold ml-2">{{ $deliveries->count() }}</span>
            </div>
        </div>
    </div>

    <!-- Delivery Batches -->
    @forelse($deliveryBatches as $batchName => $items)
        @php
            $batchDate = $items->first()->request_delivery_date;
            $batchTotal = $items->sum('total_amount');
            $batchQty = $items->sum('quantity');
            
            // Find corresponding delivery
            $delivery = $deliveries->firstWhere('request_delivery_date', $batchDate);
        @endphp

        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-6">
            <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-3">
                <div>
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        📦 Batch: {{ $batchName }}
                    </h2>
                    <p class="text-sm text-gray-400 mt-1">
                        Delivery Date: <span class="text-blue-400">{{ $batchDate->format('Y-m-d') }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-400">Batch Total</p>
                    <p class="text-xl font-bold text-green-400">₱{{ number_format($batchTotal, 2) }}</p>
                    <p class="text-xs text-gray-500">{{ $items->count() }} items ({{ $batchQty }} qty)</p>
                </div>
            </div>

            @if($delivery)
                <div class="bg-blue-900/20 border border-blue-700 rounded p-3 mb-4">
                    <p class="text-sm flex items-center gap-2">
                        <span class="text-blue-400">✓ Delivery Created:</span>
                        <span class="font-mono">DR #{{ $delivery->dr_no ?? 'Pending' }}</span>
                        <span class="px-2 py-0.5 rounded text-xs {{ $delivery->status === 'Completed' ? 'bg-green-600' : 'bg-yellow-600' }}">
                            {{ $delivery->status }}
                        </span>
                    </p>
                </div>
            @else
                <div class="bg-yellow-900/20 border border-yellow-700 rounded p-3 mb-4">
                    <p class="text-sm text-yellow-400">⚠️ Delivery not yet created for this batch</p>
                </div>
            @endif

            <!-- Items Table -->
            <table class="w-full border border-gray-700 rounded-lg overflow-hidden text-sm">
                <thead class="bg-gray-700 text-gray-300">
                    <tr>
                        <th class="px-3 py-2 text-left">Item Code</th>
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-left">Brand</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Unit Price</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="border-b border-gray-700 hover:bg-gray-700/40">
                            <td class="px-3 py-2">{{ $item->item_code }}</td>
                            <td class="px-3 py-2">{{ $item->item_description }}</td>
                            <td class="px-3 py-2">{{$item->brand }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->quantity }}</td>
                            <td class="px-3 py-2 text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-2 text-right">₱{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                     @endforeach
                </tbody>
                </table>
            </div>
          @empty
        <div class="bg-gray-800 rounded-lg p-6 text-center text-gray-400">No delivery batches found</div>
    @endforelse
</div>
@endsection
