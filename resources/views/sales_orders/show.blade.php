@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 text-gray-100 p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-2">
        <h1 class="text-2xl font-bold">
            View Sales Order — {{ $salesOrder->sales_order_number }}
        </h1>
        <a href="{{ route('sales_orders.index') }}" 
           class="bg-gray-100 hover:bg-gray-100 px-4 py-2 rounded text-sm transition-all duration-150">
            ← Back to List
        </a>
    </div>

@if($salesOrder->is_closed)
    <div class="bg-green-100/40 border-2 border-green-300 text-green-700 p-4 rounded-lg mb-6 flex items-center gap-3">
        <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <strong class="text-lg">✅ Sales Order Closed</strong>
            <p class="text-sm mt-1">All items have been fully delivered. This Sales Order can no longer be edited.</p>
        </div>
    </div>
@endif

{{-- 🔥 ADD THIS: Show notes for Declined/Cancelled orders --}}
@if(in_array($salesOrder->status, ['Declined', 'Cancelled']) && $salesOrder->notes)
    <div class="bg-red-100/40 border-2 border-red-600 text-red-700 p-4 rounded-lg mb-6 flex items-start gap-3">
        <svg class="w-6 h-6 text-red-700 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div class="flex-1">
            <strong class="text-lg flex items-center gap-2">
                @if($salesOrder->status === 'Declined')
                    ❌ Sales Order Declined
                @else
                    🚫 Sales Order Cancelled
                @endif
            </strong>
            <p class="text-sm mt-2 font-semibold">Reason:</p>
            <p class="text-sm mt-1 bg-red-950/50 border border-red-200 rounded px-3 py-2">
                {{ $salesOrder->notes }}
            </p>
        </div>
    </div>
@endif

   <!-- Action Buttons -->
    <div class="mb-6 flex flex-wrap gap-3">
    @if($salesOrder->status !== 'Pending')
        {{-- Print Options Dropdown --}}
        <div class="relative inline-block">
            <button id="printDropdownBtn" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center gap-2 transition">
                🖨️ Print Form
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            {{-- Dropdown Menu --}}
            <div id="printDropdown" class="hidden absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                <div class="p-3">
                    <p class="text-sm font-semibold text-gray-500 mb-3 border-b border-gray-200 pb-2">Print Options</p>
                    
                    <a href="{{ route('sales_orders.print', ['id' => $salesOrder->id, 'hide_prices' => 0]) }}" 
                       target="_blank"
                       class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition mb-2">
                        ✅ Show All Prices
                    </a>
                    
                    <a href="{{ route('sales_orders.print', ['id' => $salesOrder->id, 'hide_prices' => 1]) }}" 
                       target="_blank"
                       class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition">
                        🚫 Hide Prices (Show as 0.00)
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-700 px-4 py-2 rounded inline-block">
            ⚠️ Cannot print: Sales order is pending for approval
        </div>
    @endif

        {{-- ✅ NEW: Manual Close Sales Order Button --}}
        @if(!$salesOrder->is_closed && auth()->user()->canPerformInModule('can_manage', 'sales_orders'))
            <button onclick="confirmCloseSO()"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded inline-block transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                🔒 Close Sales Order
            </button>
            
            {{-- Hidden form for closing SO --}}
            <form id="closeSoForm" action="{{ route('sales_orders.manualClose', $salesOrder->id) }}" method="POST" class="hidden">
                @csrf
                @method('PATCH')
            </form>
        @endif

        {{-- Show View Delivery Batches button if there are multiple deliveries --}}
        @php
            $deliveryCount = \App\Models\Deliveries::where('sales_order_number', $salesOrder->sales_order_number)
                ->whereHas('items')
                ->count();
        @endphp
        @if($deliveryCount >= 2)
            <a href="{{ route('sales_orders.delivery_batches', ['id' => $salesOrder->id]) }}"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded inline-block transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                📦 View Delivery Batches ({{ $deliveryCount }})
            </a>
        @endif

        {{-- Message for Single Delivery --}}
        @if($deliveryCount === 1)
            <div class="bg-blue-600/20 border border-blue-600 text-blue-700 px-4 py-2 rounded inline-block">
                ℹ️ Single delivery - No multiple batches
            </div>
        @endif
    </div>
    
         {{-- ✅ FLAGGED CUSTOMER WARNING BADGE --}}
        @if($salesOrder->customer && $salesOrder->customer->is_flagged)
            <div class="flex items-center gap-2 bg-red-100 border border-red-600 rounded-lg px-4 py-2">
                <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span class="text-red-700 font-semibold text-sm">⚠️ FLAGGED CUSTOMER</span>
            </div>
        @endif
    <!-- Sales Order Info -->
    <div class="bg-white/80 p-6 rounded-xl shadow-lg mb-6 border border-gray-200">
        <h2 class="text-lg font-semibold mb-3 border-b border-gray-200 pb-2">Sales Order Information</h2>
        <div class="grid grid-cols-2 gap-6">
            <div class="space-y-3">
                <p><span class="font-semibold text-gray-500">Sales Order #:</span> {{ $salesOrder->sales_order_number }}</p>
                <p><span class="font-semibold text-gray-500">Customer:</span> {{ $salesOrder->customer->customer_name ?? 'N/A' }}</p>
                
                {{-- PO Number --}}
                <div>
                    <label class="block text-sm mb-1 text-gray-500">PO Number</label>
                    <input type="text" value="{{ $salesOrder->po_number ?? 'N/A' }}" 
                        class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-500" readonly>
                </div>

                {{-- ✅ UPDATED: Show PO Image if available --}}
                @if($salesOrder->po_image)
                    <div>
                        <label class="block text-sm mb-2 text-gray-500 font-semibold">📸 PO Proof / Order Evidence</label>
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            @if(Str::endsWith($salesOrder->po_image, '.pdf'))
                                {{-- PDF File --}}
                                <a href="{{ asset('po_images/' . $salesOrder->po_image) }}" 
                                target="_blank" 
                                class="text-blue-700 hover:text-blue-700 flex items-center gap-2 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="underline">View PO Document (PDF)</span>
                                </a>
                            @else
                                {{-- Image File --}}
                                <a href="{{ asset('po_images/' . $salesOrder->po_image) }}" 
                                target="_blank"
                                class="block">
                                    <img src="{{ asset('po_images/' . $salesOrder->po_image) }}" 
                                        alt="PO Proof" 
                                        class="max-w-md w-full rounded border border-gray-300 hover:border-blue-500 transition-all hover:shadow-lg cursor-pointer">
                                </a>
                                <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Click to view full size
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

                <p><span class="font-semibold text-gray-500">TIN:</span> {{ $salesOrder->customer->tin_no ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-500">Request Delivery Date:</span> {{ $salesOrder->request_delivery_date ?? '—' }}</p>
                
                {{-- ✅ Delivery Type Display --}}
                <p><span class="font-semibold text-gray-500">Delivery Type:</span> 
                    @php
                        $deliveryType = trim($salesOrder->delivery_type ?? '');
                        $typeColors = [
                            'Partial' => 'bg-blue-600 text-white',
                            'Full' => 'bg-green-600 text-white',
                        ];
                        $typeClass = $typeColors[$deliveryType] ?? 'bg-gray-200 text-gray-800';
                        $displayText = $deliveryType ?: 'Not Set';
                    @endphp
                    <span class="px-2 py-1 rounded text-xs {{ $typeClass }}">
                        {{ ucfirst($displayText) }}
                    </span>
                </p>
            </div>
            <div class="space-y-3">
                <p><span class="font-semibold text-gray-500">Sales Representative:</span> {{ $salesOrder->sales_rep ?? '—' }}</p>
                <p>
                    <span class="font-semibold text-gray-500">Sales Executive:</span> 
                    {{ $salesOrder->customer->sales_executive ?? $salesOrder->sales_executive ?? '—' }}
                </p>
                <p><span class="font-semibold text-gray-500">Branch:</span> {{ $salesOrder->branch ?? $salesOrder->customer->branch ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-500">Status:</span>
                    @php
                        $statusColors = [
                            'Pending' => 'bg-yellow-500 text-black',
                            'Approved' => 'bg-green-600 text-white',
                            'Declined' => 'bg-red-600 text-white',
                            'Cancelled' => 'bg-gray-200 text-gray-800',
                            'New' => 'bg-purple-600 text-white'
                        ];
                        $statusClass = $statusColors[$salesOrder->status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="px-2 py-1 rounded text-xs {{ $statusClass }}">
                        {{ ucfirst($salesOrder->status) }}
                    </span>
                </p>
                <p><span class="font-semibold text-gray-500">Created At:</span> {{ $salesOrder->created_at->format('Y-m-d H:i') }}</p>
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
                <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <h3 class="text-lg font-bold text-gray-800">{{ $batchName }}</h3>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs text-blue-100 font-medium">Delivery Date</span>
                                    <span class="text-sm text-gray-800 font-semibold">
                                        {{ $batchDate ? \Carbon\Carbon::parse($batchDate)->format('M d, Y') : 'Not set' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div class="text-xs text-blue-100 font-medium">Batch Total</div>
                                    <div class="text-xl font-bold text-gray-800">₱{{ number_format($batchTotal, 2) }}</div>
                                </div>
                                <span class="px-4 py-2 rounded-lg text-sm font-bold {{ $isActive ? 'bg-green-500 text-white' : 'bg-red-100 text-red-700' }} shadow-lg">
                                    {{ $isActive ? '✅ Active' : '❌ Cancelled' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Batch Items Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100/50 border-b border-gray-300">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Brand</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/50">
                                @foreach($batchItems as $item)
                                    <tr class="hover:bg-gray-100/30 transition-colors {{ $isActive ? '' : 'text-gray-500' }}">
                                        <td class="px-4 py-3 {{ $isActive ? 'text-gray-700' : 'line-through' }}">
                                            {{ $item->item_description ?: ($item->item->item_description ?? '—') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $isActive ? 'text-gray-500' : 'line-through' }}">
                                            {{ $item->item_code ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $isActive ? 'text-gray-500' : 'line-through' }}">
                                            {{ $item->item_category ?: ($item->item->item_category ?? '—') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $isActive ? 'text-gray-500' : 'line-through' }}">
                                            {{ $item->brand ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium {{ $isActive ? 'text-gray-700' : 'line-through' }}">
                                            {{ number_format($item->quantity, 2) }} {{ $item->unit ?? 'Kgs' }}
                                        </td>
                                        <td class="px-4 py-3 text-right {{ $isActive ? 'text-gray-500' : 'line-through' }}">
                                            ₱{{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold {{ $isActive ? 'text-blue-700' : 'line-through text-gray-500' }}">
                                            ₱{{ number_format($item->quantity * $item->unit_price, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 italic">
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
        <div class="bg-white/80 p-6 rounded-xl shadow-lg mb-6 border border-gray-200">
            <h2 class="text-lg font-semibold mb-3 border-b border-gray-200 pb-2">Order Items</h2>
            
            <table class="w-full border border-gray-200 rounded-lg overflow-hidden text-gray-700">
                <thead class="bg-gray-100 text-gray-500">
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
                        <tr class="border-b border-gray-200 hover:bg-gray-100/40">
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
                            <td class="px-3 py-2 text-sm text-gray-500">{{ $item->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($salesOrder->items->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    No items found for this order.
                </div>
            @endif
        </div>
    @endif

    <!-- Total (Only Active Items) -->
    <div class="bg-white/80 p-6 rounded-xl shadow-lg border border-gray-200">
        <div class="text-right">
            @php
                // Calculate total from active items only
                $activeTotal = $salesOrder->items->where('batch_status', 'Active')->sum(fn($i) => $i->quantity * $i->unit_price);
                $cancelledTotal = $salesOrder->items->where('batch_status', 'Cancelled')->sum(fn($i) => $i->quantity * $i->unit_price);
            @endphp
            
            @if($cancelledTotal > 0)
                <p class="text-sm text-gray-500 mb-2">
                    <span class="line-through">Cancelled Items: ₱{{ number_format($cancelledTotal, 2) }}</span>
                </p>
            @endif
            
            <p class="text-lg font-semibold">
                Total Amount (Active Items): 
                <span class="text-green-700">₱{{ number_format($activeTotal, 2) }}</span>
            </p>
        </div>
    </div>

    {{-- Status Update Section --}}
    @if(in_array($salesOrder->status, ['Pending', 'New']) && \App\Helpers\RoleHelper::canUpdateSalesOrderStatus())
    <div class="mt-8 bg-white/70 border border-gray-200 rounded-lg p-5 shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Update Sales Order Status
            </h3>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-yellow-600/20 text-yellow-700 border border-yellow-700/40">
                {{ ucfirst($salesOrder->status) }}
            </span>
        </div>

        <form id="statusUpdateForm" action="{{ route('sales_orders.updateStatus', $salesOrder->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-2">Select New Status</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @php
                        $statuses = [
                            'Approved' => ['icon' => '✅', 'color' => 'bg-green-600/20 text-green-700 border-green-700/40 hover:bg-green-600/30'],
                            'Declined' => ['icon' => '❌', 'color' => 'bg-red-600/20 text-red-700 border-red-700/40 hover:bg-red-600/30'],
                            'Cancelled' => ['icon' => '🚫', 'color' => 'bg-gray-100 text-gray-500 border-gray-200/40 hover:bg-gray-100/30'],
                        ];
                    @endphp

                    @foreach ($statuses as $value => $style)
                        <label class="relative flex items-center justify-center gap-1 text-sm font-medium 
                                    border rounded-md py-1.5 px-3 cursor-pointer transition-all duration-150 
                                    {{ $style['color'] }}">
                            <input type="radio" name="status" value="{{ $value }}" class="peer absolute opacity-0 status-radio" required>
                            <span class="peer-checked:font-semibold peer-checked:scale-105 transition-transform duration-150 flex items-center gap-1">
                                <span>{{ $style['icon'] }}</span> {{ $value }}
                            </span>
                            <span class="absolute inset-0 rounded-md border border-transparent peer-checked:border-blue-500/60"></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Hidden textarea for notes (will be shown in modal) -->
            <input type="hidden" name="notes" id="hiddenNotes">

            <div class="flex justify-end">
                <button type="button" id="submitStatusBtn"
                    class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-5 py-1.5 rounded-md 
                           transition-all duration-150 shadow-sm hover:shadow-blue-500/20 active:scale-[0.98]">
                    Update Status
                </button>
            </div>
        </form>
    </div>
    @endif
</div>

<!-- Notes Modal -->
<div id="notesModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 max-w-md w-full transform transition-all">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Add Notes
            </h3>
            <button type="button" id="closeModalBtn" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="p-6">
            <label class="block text-sm font-medium text-gray-500 mb-2">
                Please provide a reason for <span id="modalStatusText" class="font-bold text-red-700"></span>:
            </label>
            <textarea id="modalNotesTextarea" 
                rows="5" 
                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-100 
                       focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none
                       placeholder-gray-500"
                placeholder="Enter reason or notes here..."></textarea>
            <p class="text-xs text-gray-500 mt-2">This field is required for Declined and Cancelled statuses.</p>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 rounded-b-xl flex justify-end gap-3">
            <button type="button" id="cancelModalBtn"
                class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 hover:bg-gray-100 rounded-lg transition-colors">
                Cancel
            </button>
            <button type="button" id="confirmModalBtn"
                class="px-4 py-2 text-sm font-medium text-gray-800 bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors shadow-sm">
                Confirm & Update
            </button>
        </div>
    </div>
</div>

<script>

     function confirmCloseSO() {
        if (confirm('⚠️ WARNING: Closing this Sales Order will:\n\n' +
                    '✓ Mark this SO as CLOSED\n' +
                    '✓ Mark ALL related deliveries as DELIVERED\n' +
                    '✓ Lock this SO from further editing\n\n' +
                    'This action cannot be undone. Are you sure?')) {
            document.getElementById('closeSoForm').submit();
        }
    }

    // Dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('printDropdownBtn');
    const dropdown = document.getElementById('printDropdown');
    
    console.log('Dropdown button:', dropdownBtn);
    console.log('Dropdown menu:', dropdown);
    
    if (dropdownBtn && dropdown) {
        dropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Dropdown clicked, toggling visibility');
            dropdown.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
            if (!dropdownBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('statusUpdateForm');
    const submitBtn = document.getElementById('submitStatusBtn');
    const modal = document.getElementById('notesModal');
    const modalNotesTextarea = document.getElementById('modalNotesTextarea');
    const modalStatusText = document.getElementById('modalStatusText');
    const hiddenNotes = document.getElementById('hiddenNotes');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const confirmModalBtn = document.getElementById('confirmModalBtn');
    const statusRadios = document.querySelectorAll('.status-radio');

    // ✅ Get customer flag status from backend
    const isCustomerFlagged = {{ $salesOrder->customer->is_flagged ? 'true' : 'false' }};
    const customerName = "{{ $salesOrder->customer->customer_name ?? 'this customer' }}";

    let selectedStatus = '';

    // Handle submit button click
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get selected status
        selectedStatus = document.querySelector('.status-radio:checked')?.value;
        
        if (!selectedStatus) {
            Swal.fire({
                icon: 'warning',
                title: 'No Status Selected',
                text: 'Please select a status first.',
                confirmButtonColor: '#3B82F6'
            });
            return;
        }

        // ✅ NEW: If customer is flagged and status is Approved, show warning
        if (isCustomerFlagged && selectedStatus === 'Approved') {
            Swal.fire({
                icon: 'warning',
                title: '⚠️ Flagged Customer',
                html: `
                    <p class="text-gray-700 mb-3">
                        <strong>${customerName}</strong> is currently <strong class="text-red-600">FLAGGED</strong>.
                    </p>
                    <p class="text-gray-600">
                        Are you sure you want to approve this sales order?
                    </p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'No, Keep Pending',
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-lg',
                    title: 'text-xl font-bold',
                    htmlContainer: 'text-left'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // User confirmed - proceed with approval
                    hiddenNotes.value = '';
                    form.submit();
                } else {
                    // User cancelled - do nothing (stay pending)
                    Swal.fire({
                        icon: 'info',
                        title: 'Approval Cancelled',
                        text: 'Sales order remains pending.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
            return;
        }

        // If Declined or Cancelled, show notes modal
        if (selectedStatus === 'Declined' || selectedStatus === 'Cancelled') {
            modalStatusText.textContent = selectedStatus.toLowerCase();
            modalNotesTextarea.value = '';
            modal.classList.remove('hidden');
            modalNotesTextarea.focus();
        } else if (selectedStatus === 'Approved') {
            // For unflagged customers - direct approval with confirmation
            Swal.fire({
                icon: 'question',
                title: 'Confirm Approval',
                text: 'Are you sure you want to approve this sales order?',
                showCancelButton: true,
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    hiddenNotes.value = '';
                    form.submit();
                }
            });
        }
    });

    // Close modal handlers
    closeModalBtn.addEventListener('click', closeModal);
    cancelModalBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    function closeModal() {
        modal.classList.add('hidden');
        modalNotesTextarea.value = '';
    }

    // Confirm button - validate and submit
    confirmModalBtn.addEventListener('click', function() {
        const notes = modalNotesTextarea.value.trim();
        
        if (!notes) {
            Swal.fire({
                icon: 'warning',
                title: 'Notes Required',
                text: 'Please provide a reason before confirming.',
                confirmButtonColor: '#3B82F6'
            });
            modalNotesTextarea.focus();
            return;
        }

        // Set the hidden notes field and submit
        hiddenNotes.value = notes;
        modal.classList.add('hidden');
        form.submit();
    });

    // Allow Ctrl+Enter in textarea to confirm
    modalNotesTextarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.ctrlKey) {
            confirmModalBtn.click();
        }
    });
});

// Manual Close SO function with SweetAlert
function confirmCloseSO() {
    Swal.fire({
        icon: 'warning',
        title: '⚠️ Close Sales Order',
        html: `
            <div class="text-left text-gray-700">
                <p class="mb-3">Closing this Sales Order will:</p>
                <ul class="list-disc list-inside space-y-2 mb-4">
                    <li>Mark this SO as <strong>CLOSED</strong></li>
                    <li>Mark ALL related deliveries as <strong>DELIVERED</strong></li>
                    <li>Lock this SO from further editing</li>
                </ul>
                <p class="text-red-600 font-semibold">⚠️ This action cannot be undone!</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, Close SO',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('closeSoForm').submit();
        }
    });
}

// // Dropdown toggle functionality
// document.addEventListener('DOMContentLoaded', function() {
//     const dropdownBtn = document.getElementById('printDropdownBtn');
//     const dropdown = document.getElementById('printDropdown');
    
//     if (dropdownBtn && dropdown) {
//         dropdownBtn.addEventListener('click', function(e) {
//             e.stopPropagation();
//             dropdown.classList.toggle('hidden');
//         });
        
//         // Close dropdown when clicking outside
//         document.addEventListener('click', function(e) {
//             if (!dropdownBtn.contains(e.target) && !dropdown.contains(e.target)) {
//                 dropdown.classList.add('hidden');
//             }
//         });
//     }
// });

</script>
@endsection