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

    @if($salesOrder->status !== 'Pending')
        <a href="{{ route('sales_orders.print', $salesOrder->id) }}" 
        target="_blank"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded mb-4 inline-block">
            🖨️ Print Form
        </a>
    @else
        <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-300 px-4 py-2 rounded mb-4 inline-block">
            ⚠️ Cannot print: Sales order is pending for approval
        </div>
    @endif

    @if($salesOrder->status === 'Approved')
        <a href="{{ route('sales_orders.addItemsForm', $salesOrder->id) }}" 
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-4 inline-block">
            ➕ Add Items
        </a>
        
        <a href="{{ route('sales_orders.deliveryBatches', $salesOrder->id) }}" 
        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded mb-4 inline-block">
            📦 View Delivery Batches
        </a>
    @endif

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
    <div class="bg-gray-800/80 p-6 rounded-xl shadow-lg mb-6 border border-gray-700">
        <h2 class="text-lg font-semibold mb-3 border-b border-gray-700 pb-2">Order Items</h2>
        <table class="w-full border border-gray-700 rounded-lg overflow-hidden text-gray-200">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-3 py-2 text-left">Description</th>
                    <th class="px-3 py-2 text-left">Category</th>
                    <th class="px-3 py-2 text-left">Brand</th>
                    <th class="px-3 py-2 text-right">Quantity</th>
                    <th class="px-3 py-2 text-right">Unit Price</th>
                    <th class="px-3 py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesOrder->items as $item)
                    <tr class="border-b border-gray-700 hover:bg-gray-700/40">
                        <td class="px-3 py-2">{{ $item->item_description ?: ($item->item->item_description ?? '') }}</td>
                        <td class="px-3 py-2">{{ $item->item_category ?: ($item->item->item_category ?? '') }}</td>
                        <td class="px-3 py-2">{{ $item->brand ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $item->quantity }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-3 text-gray-400">No items found for this order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Total -->
    <div class="bg-gray-800/80 p-6 rounded-xl shadow-lg border border-gray-700">
        <div class="text-right">
            <p class="text-lg font-semibold">
                Total Amount: 
                <span class="text-green-400">₱{{ number_format($salesOrder->total_amount, 2) }}</span>
            </p>
        </div>
    </div>

    {{-- 🌙 Status Update Section --}}
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
