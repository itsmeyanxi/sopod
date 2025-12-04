@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">
                📦 Delivery Batches
            </h1>
            <p class="text-gray-400">Sales Order: <span class="text-blue-400 font-semibold">{{ $salesOrder->sales_order_number }}</span></p>
        </div>
        <a href="{{ route('sales_orders.show', $salesOrder->id) }}" 
           class="bg-gray-700 hover:bg-gray-600 px-5 py-2.5 rounded-lg text-sm transition-all duration-150 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Sales Order
        </a>
    </div>

    <!-- Sales Order Summary Card -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-xl shadow-xl mb-6 border border-gray-700">
        <h2 class="text-lg font-semibold mb-4 text-gray-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Sales Order Summary
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
                <p class="text-xs text-gray-400 mb-1">Customer</p>
                <p class="text-white font-semibold">{{ $salesOrder->customer->customer_name ?? 'N/A' }}</p>
            </div>
            <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
                <p class="text-xs text-gray-400 mb-1">Total Deliveries</p>
                <p class="text-white font-semibold text-2xl">{{ count($deliveries) }}</p>
            </div>
            <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
                <p class="text-xs text-gray-400 mb-1">SO Total Amount</p>
                <p class="text-green-400 font-semibold text-xl">₱{{ number_format($salesOrder->items->where('batch_status', 'Active')->sum(fn($i) => $i->quantity * $i->unit_price), 2) }}</p>
            </div>
            <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
                <p class="text-xs text-gray-400 mb-1">Status</p>
                @if($salesOrder->is_closed)
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-600 text-white">
                        ✅ Closed - Fully Delivered
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        {{ $salesOrder->status === 'Approved' ? 'bg-green-600 text-white' : 'bg-yellow-500 text-black' }}">
                        {{ ucfirst($salesOrder->status) }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Delivery Batches -->
    <div class="space-y-6">
        @forelse($deliveries as $index => $delivery)
            @php
                $isDelivered = $delivery->status === 'Delivered';
                $isCancelled = $delivery->status === 'Cancelled';
                $deliveryColor = $isDelivered ? 'blue' : ($isCancelled ? 'red' : 'yellow');
                $batchNumber = $index + 1;
                $batchTotal = $delivery->items->sum('total_amount');
            @endphp

            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl shadow-2xl overflow-hidden border-2 
                {{ $isDelivered ? 'border-blue-500/30' : ($isCancelled ? 'border-red-500/30' : 'border-yellow-500/30') }} 
                {{ $isCancelled ? 'opacity-60' : '' }}">
                
                <!-- Delivery Header -->
                <div class="bg-gradient-to-r {{ $isDelivered ? 'from-blue-600 to-blue-700' : ($isCancelled ? 'from-red-600 to-red-700' : 'from-yellow-600 to-yellow-700') }} px-6 py-5">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Left Side: Batch Info -->
                        <div class="flex items-center gap-4 flex-wrap">
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 shadow-lg">
                                <h3 class="text-xl font-bold text-white">{{ $delivery->delivery_batch ?? 'Batch ' . $batchNumber }}</h3>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs text-blue-100 font-medium uppercase tracking-wide">DR Number</span>
                                <span class="text-base text-white font-semibold">{{ $delivery->dr_no ?? 'N/A' }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs text-blue-100 font-medium uppercase tracking-wide">Delivery Date</span>
                                <span class="text-base text-white font-semibold">
                                    {{ $delivery->request_delivery_date ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('M d, Y') : 'Not set' }}
                                </span>
                            </div>
                            @if($delivery->plate_no)
                                <div class="flex flex-col">
                                    <span class="text-xs text-blue-100 font-medium uppercase tracking-wide">Plate No</span>
                                    <span class="text-base text-white font-semibold">{{ $delivery->plate_no }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Right Side: Stats & Status -->
                        <div class="flex items-center gap-4">
                            <!-- Item Count -->
                            <div class="bg-white/10 rounded-lg px-4 py-2 backdrop-blur-sm">
                                <div class="text-xs text-blue-100 font-medium">Items</div>
                                <div class="text-lg font-bold text-white">{{ $delivery->items->count() }}</div>
                            </div>

                            <!-- Batch Total -->
                            <div class="bg-white/10 rounded-lg px-4 py-2 backdrop-blur-sm text-right">
                                <div class="text-xs text-blue-100 font-medium">Total Amount</div>
                                <div class="text-xl font-bold text-white">₱{{ number_format($batchTotal, 2) }}</div>
                            </div>

                            <!-- Status Badge -->
                            <span class="px-5 py-2.5 rounded-xl text-sm font-bold shadow-xl
                                {{ $isDelivered ? 'bg-green-500 text-white' : ($isCancelled ? 'bg-red-900 text-red-200' : 'bg-yellow-500 text-black') }}">
                                {{ $isDelivered ? '✅ Delivered' : ($isCancelled ? '❌ Cancelled' : '⏳ ' . $delivery->status) }}
                            </span>

                            <!-- View Details Button -->
                            <a href="{{ route('deliveries.show', $delivery->id) }}" 
                               class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Delivery Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700/50 border-b-2 border-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Item Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Brand</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Quantity</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            @foreach($delivery->items as $item)
                                <tr class="hover:bg-gray-700/30 transition-colors {{ $isCancelled ? 'text-gray-500' : '' }}">
                                    <td class="px-4 py-3 text-sm font-medium {{ $isCancelled ? 'line-through text-gray-500' : 'text-blue-400' }}">
                                        {{ $item->item_code ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 {{ $isCancelled ? 'line-through text-gray-500' : 'text-gray-200' }}">
                                        {{ $item->item_description ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm {{ $isCancelled ? 'line-through text-gray-500' : 'text-gray-300' }}">
                                        {{ $item->item_category ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm {{ $isCancelled ? 'line-through text-gray-500' : 'text-gray-300' }}">
                                        {{ $item->brand ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium {{ $isCancelled ? 'line-through text-gray-500' : 'text-gray-200' }}">
                                        {{ number_format($item->quantity, 2) }} {{ $item->uom ?? 'Kgs' }}
                                    </td>
                                    <td class="px-4 py-3 text-right {{ $isCancelled ? 'line-through text-gray-500' : 'text-gray-300' }}">
                                        ₱{{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $isCancelled ? 'line-through text-gray-500' : 'text-green-400' }}">
                                        ₱{{ number_format($item->total_amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Delivery Footer with Additional Info -->
                <div class="bg-gray-800/50 border-t border-gray-700 px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                    @if($delivery->sales_invoice_no)
                        <div>
                            <p class="text-xs text-gray-400">Sales Invoice No</p>
                            <p class="text-sm text-white font-semibold">{{ $delivery->sales_invoice_no }}</p>
                        </div>
                    @endif
                    @if($delivery->approved_by)
                        <div>
                            <p class="text-xs text-gray-400">Approved By</p>
                            <p class="text-sm text-white font-semibold">{{ $delivery->approved_by }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-gray-400">Created At</p>
                        <p class="text-sm text-white font-semibold">{{ $delivery->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @if($delivery->additional_instructions)
                        <div class="col-span-2 md:col-span-1">
                            <p class="text-xs text-gray-400">Instructions</p>
                            <p class="text-sm text-gray-300">{{ $delivery->additional_instructions }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-gray-800/50 rounded-xl p-12 text-center border border-gray-700">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-gray-400 text-lg">No deliveries found for this sales order.</p>
            </div>
        @endforelse
    </div>

    <!-- Summary Footer -->
    <div class="mt-6 bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-xl shadow-xl border border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gray-800/50 rounded-lg p-4 border border-blue-500/30">
                <p class="text-xs text-gray-400 mb-1">Total Deliveries</p>
                <p class="text-white font-bold text-2xl">{{ $deliveries->count() }}</p>
            </div>
            <div class="bg-gray-800/50 rounded-lg p-4 border border-green-500/30">
                <p class="text-xs text-gray-400 mb-1">Delivered</p>
                <p class="text-green-400 font-bold text-2xl">{{ $deliveries->where('status', 'Delivered')->count() }}</p>
            </div>
            @if($deliveries->where('status', 'Cancelled')->count() > 0)
                <div class="bg-gray-800/50 rounded-lg p-4 border border-red-500/30">
                    <p class="text-xs text-gray-400 mb-1">Cancelled</p>
                    <p class="text-red-400 font-bold text-2xl">{{ $deliveries->where('status', 'Cancelled')->count() }}</p>
                </div>
            @endif
            <div class="bg-gray-800/50 rounded-lg p-4 border border-green-500/30">
                <p class="text-xs text-gray-400 mb-1">Total Delivered Amount</p>
                <p class="text-green-400 font-bold text-2xl">₱{{ number_format($deliveries->where('status', 'Delivered')->sum(fn($d) => $d->items->sum('total_amount')), 2) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection