@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">
            View Sales Order — {{ $salesOrder->sales_order_number }}
        </h1>
        <a href="{{ route('sales_orders.index') }}" 
           class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-sm transition-all duration-150">
            ← Back to List
        </a>
    </div>

    <!-- Action Buttons -->
    <div class="mb-6 flex flex-wrap gap-3">
        @if($salesOrder->status !== 'Pending')
            <a href="{{ route('sales_orders.print', $salesOrder->id) }}" 
            target="_blank"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-block transition">
                🖨️ Print Form
            </a>
        @else
            <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-300 px-4 py-2 rounded inline-block">
                ⚠️ Cannot print: Sales order is pending for approval
            </div>
        @endif

        {{-- Show View Delivery Batches ONLY if Approved AND Partial Delivery --}}
        @if($salesOrder->status === 'Approved' && $salesOrder->delivery_type === 'Partial')
            <a href="{{ route('sales_orders.deliveryBatches', $salesOrder->id) }}" 
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded inline-block transition">
                📦 View Delivery Batches
            </a>
        @endif

        {{-- Message for Full Delivery Type --}}
        @if($salesOrder->status === 'Approved' && $salesOrder->delivery_type === 'Full')
            <div class="bg-blue-600/20 border border-blue-600 text-blue-300 px-4 py-2 rounded inline-block">
                ℹ️ Full delivery - Single batch
            </div>
        @endif
    </div>

    <!-- Sales Order Info -->
    <div class="bg-gray-800/80 p-6 rounded-xl shadow-lg mb-6 border border-gray-700">
        <h2 class="text-lg font-semibold mb-3 border-b border-gray-700 pb-2">Sales Order Information</h2>
        <div class="grid grid-cols-2 gap-6">
            <div class="space-y-1">
                <p><span class="font-semibold text-gray-300">Sales Order #:</span> {{ $salesOrder->sales_order_number }}</p>
                <p><span class="font-semibold text-gray-300">Customer:</span> {{ $salesOrder->customer->customer_name ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-300">PO Number:</span> {{ $salesOrder->po_number ?? '—' }}</p>
                <p><span class="font-semibold text-gray-300">TIN:</span> {{ $salesOrder->customer->tin_no ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-300">Request Delivery Date:</span> {{ $salesOrder->request_delivery_date ?? '—' }}</p>
                
                {{-- ✅ Delivery Type Display --}}
                <p><span class="font-semibold text-gray-300">Delivery Type:</span> 
                    @php
                        $deliveryType = trim($salesOrder->delivery_type ?? '');
                        $typeColors = [
                            'Partial' => 'bg-blue-600 text-white',
                            'Full' => 'bg-green-600 text-white',
                        ];
                        $typeClass = $typeColors[$deliveryType] ?? 'bg-gray-600 text-white';
                        $displayText = $deliveryType ?: 'Not Set';
                    @endphp
                    <span class="px-2 py-1 rounded text-xs {{ $typeClass }}">
                        {{ ucfirst($displayText) }}
                    </span>
                </p>
            </div>
            <div class="space-y-1">
                <p><span class="font-semibold text-gray-300">Sales Representative:</span> {{ $salesOrder->sales_rep ?? '—' }}</p>
                <p>
                    <span class="font-semibold text-gray-300">Sales Executive:</span> 
                    {{ $salesOrder->customer->sales_executive ?? $salesOrder->sales_executive ?? '—' }}
                </p>
                <p><span class="font-semibold text-gray-300">Branch:</span> {{ $salesOrder->customer->branch ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-300">Status:</span>
                    @php
                        $statusColors = [
                            'Pending' => 'bg-yellow-500 text-black',
                            'Approved' => 'bg-green-600 text-white',
                            'Declined' => 'bg-red-600 text-white',
                            'Cancelled' => 'bg-gray-600 text-white',
                            'New' => 'bg-purple-600 text-white'
                        ];
                        $statusClass = $statusColors[$salesOrder->status] ?? 'bg-gray-700 text-white';
                    @endphp
                    <span class="px-2 py-1 rounded text-xs {{ $statusClass }}">
                        {{ ucfirst($salesOrder->status) }}
                    </span>
                </p>
                <p><span class="font-semibold text-gray-300">Created At:</span> {{ $salesOrder->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    @php
        $itemsByBatch = $salesOrder->items->groupBy('delivery_batch');
        $isPartial = $salesOrder->delivery_type === 'Partial';
    @endphp

    @if($isPartial && $itemsByBatch->count() > 1)
        {{-- PARTIAL DELIVERY: Card-based batch display --}}
        <div class="space-y-6 mb-6">
            <h2 class="text-xl font-bold text-gray-100 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                Delivery Batches ({{ $itemsByBatch->count() }})
            </h2>

            @foreach($itemsByBatch as $batchName => $batchItems)
                @php
                    $batchStatus = $batchItems->first()->batch_status ?? 'Active';
                    $isActive = $batchStatus === 'Active';
                    $batchDate = $batchItems->first()->request_delivery_date;
                    $batchTotal = $batchItems->sum(fn($i) => $i->quantity * $i->unit_price);
                @endphp

                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl shadow-2xl overflow-hidden border {{ $isActive ? 'border-blue-500/30' : 'border-red-500/30' }} {{ $isActive ? '' : 'opacity-60' }}">
                    {{-- Batch Header --}}
                    <div class="bg-gradient-to-r {{ $isActive ? 'from-blue-600 to-blue-700' : 'from-red-600 to-red-700' }} px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                    <h3 class="text-lg font-bold text-white">{{ $batchName }}</h3>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs text-blue-100 font-medium">Delivery Date</span>
                                    <span class="text-sm text-white font-semibold">
                                        {{ $batchDate ? \Carbon\Carbon::parse($batchDate)->format('M d, Y') : 'Not set' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div class="text-xs text-blue-100 font-medium">Batch Total</div>
                                    <div class="text-xl font-bold text-white">₱{{ number_format($batchTotal, 2) }}</div>
                                </div>
                                <span class="px-4 py-2 rounded-lg text-sm font-bold {{ $isActive ? 'bg-green-500 text-white' : 'bg-red-900 text-red-200' }} shadow-lg">
                                    {{ $isActive ? '✅ Active' : '❌ Cancelled' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Batch Items Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700/50 border-b border-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Brand</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Quantity</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/50">
                                @foreach($batchItems as $item)
                                    <tr class="hover:bg-gray-700/30 transition-colors {{ $isActive ? '' : 'text-gray-500' }}">
                                        <td class="px-4 py-3 {{ $isActive ? 'text-gray-200' : 'line-through' }}">
                                            {{ $item->item_description ?: ($item->item->item_description ?? '—') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $isActive ? 'text-gray-300' : 'line-through' }}">
                                            {{ $item->item_code ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $isActive ? 'text-gray-300' : 'line-through' }}">
                                            {{ $item->item_category ?: ($item->item->item_category ?? '—') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $isActive ? 'text-gray-300' : 'line-through' }}">
                                            {{ $item->brand ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium {{ $isActive ? 'text-gray-200' : 'line-through' }}">
                                            {{ number_format($item->quantity, 2) }} {{ $item->unit ?? 'Kgs' }}
                                        </td>
                                        <td class="px-4 py-3 text-right {{ $isActive ? 'text-gray-300' : 'line-through' }}">
                                            ₱{{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold {{ $isActive ? 'text-blue-400' : 'line-through text-gray-500' }}">
                                            ₱{{ number_format($item->quantity * $item->unit_price, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-400 italic">
                                            {{ $item->note ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- FULL DELIVERY: Traditional table display --}}
        <div class="bg-gray-800/80 p-6 rounded-xl shadow-lg mb-6 border border-gray-700">
            <h2 class="text-lg font-semibold mb-3 border-b border-gray-700 pb-2">Order Items</h2>
            
            <table class="w-full border border-gray-700 rounded-lg overflow-hidden text-gray-200">
                <thead class="bg-gray-700 text-gray-300">
                    <tr>
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-left">Code</th>
                        <th class="px-3 py-2 text-left">Category</th>
                        <th class="px-3 py-2 text-left">Brand</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                        <th class="px-3 py-2 text-right">Unit Price</th>
                        <th class="px-3 py-2 text-right">Amount</th>
                        <th class="px-3 py-2 text-left">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesOrder->items as $item)
                        <tr class="border-b border-gray-700 hover:bg-gray-700/40">
                            <td class="px-3 py-2">
                                {{ $item->item_description ?: ($item->item->item_description ?? '') }}
                            </td>
                            <td class="px-3 py-2">{{ $item->item_code ?? '—' }}</td>
                            <td class="px-3 py-2">
                                {{ $item->item_category ?: ($item->item->item_category ?? '') }}
                            </td>
                            <td class="px-3 py-2">{{ $item->brand ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->quantity }}</td>
                            <td class="px-3 py-2 text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-2 text-right">
                                ₱{{ number_format($item->quantity * $item->unit_price, 2) }}
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-400">{{ $item->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($salesOrder->items->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    No items found for this order.
                </div>
            @endif
        </div>
    @endif

    <!-- Total (Only Active Items) -->
    <div class="bg-gray-800/80 p-6 rounded-xl shadow-lg border border-gray-700">
        <div class="text-right">
            @php
                // Calculate total from active items only
                $activeTotal = $salesOrder->items->where('batch_status', 'Active')->sum(fn($i) => $i->quantity * $i->unit_price);
                $cancelledTotal = $salesOrder->items->where('batch_status', 'Cancelled')->sum(fn($i) => $i->quantity * $i->unit_price);
            @endphp
            
            @if($cancelledTotal > 0)
                <p class="text-sm text-gray-400 mb-2">
                    <span class="line-through">Cancelled Items: ₱{{ number_format($cancelledTotal, 2) }}</span>
                </p>
            @endif
            
            <p class="text-lg font-semibold">
                Total Amount (Active Items): 
                <span class="text-green-400">₱{{ number_format($activeTotal, 2) }}</span>
            </p>
        </div>
    </div>

    {{-- Status Update Section --}}
    @if(in_array($salesOrder->status, ['Pending', 'New']) && \App\Helpers\RoleHelper::canUpdateSalesOrderStatus())
    <div class="mt-8 bg-gray-800/70 border border-gray-700 rounded-lg p-5 shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Update Sales Order Status
            </h3>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-yellow-600/20 text-yellow-400 border border-yellow-700/40">
                {{ ucfirst($salesOrder->status) }}
            </span>
        </div>

        <form action="{{ route('sales_orders.updateStatus', $salesOrder->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-2">Select New Status</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @php
                        $statuses = [
                            'Approved' => ['icon' => '✅', 'color' => 'bg-green-600/20 text-green-400 border-green-700/40 hover:bg-green-600/30'],
                            'Declined' => ['icon' => '❌', 'color' => 'bg-red-600/20 text-red-400 border-red-700/40 hover:bg-red-600/30'],
                            'Cancelled' => ['icon' => '🚫', 'color' => 'bg-gray-600/20 text-gray-400 border-gray-700/40 hover:bg-gray-600/30'],
                        ];
                    @endphp

                    @foreach ($statuses as $value => $style)
                        <label class="relative flex items-center justify-center gap-1 text-sm font-medium 
                                    border rounded-md py-1.5 px-3 cursor-pointer transition-all duration-150 
                                    {{ $style['color'] }}">
                            <input type="radio" name="status" value="{{ $value }}" class="peer absolute opacity-0" required>
                            <span class="peer-checked:font-semibold peer-checked:scale-105 transition-transform duration-150 flex items-center gap-1">
                                <span>{{ $style['icon'] }}</span> {{ $value }}
                            </span>
                            <span class="absolute inset-0 rounded-md border border-transparent peer-checked:border-blue-500/60"></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-5 py-1.5 rounded-md 
                           transition-all duration-150 shadow-sm hover:shadow-blue-500/20 active:scale-[0.98]"
                    onclick="return confirm('Are you sure you want to update this status?')">
                    Update Status
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection