@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">Sales Order List</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- ⚠️ DELIVERY ALERTS --}}
    @php
        $overdueOrders = [];
        $cancelledOrders = [];
        $today = now();

        // Check ALL sales orders, not just filtered ones
        $allOrders = \App\Models\SalesOrder::with(['customer', 'deliveries'])->get();

        foreach($allOrders as $order) {
            // Check for cancelled deliveries
            if($order->deliveries && $order->deliveries->status === 'Cancelled') {
                $cancelledOrders[] = $order;
            }
            
            // Check for overdue deliveries
            if($order->deliveries && $order->deliveries->request_delivery_date) {
                $requestedDate = \Carbon\Carbon::parse($order->deliveries->request_delivery_date);
                $isOverdue = $today->gt($requestedDate) && 
                            $order->deliveries->status !== 'Delivered' && 
                            $order->deliveries->status !== 'Cancelled';
                
                if($isOverdue) {
                    $overdueOrders[] = $order;
                }
            }
        }
    @endphp

    @if(count($cancelledOrders) > 0)
        <div id="cancelledAlert" class="bg-red-600 text-white p-4 rounded-lg mb-4 shadow-lg" style="display: none;">
            <div class="flex items-start justify-between">
                <div class="flex items-start flex-1">
                    <span class="text-2xl mr-3">🚫</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-2">Cancelled Deliveries</h3>
                        <p class="mb-2">The following sales orders have cancelled deliveries:</p>
                        <ul class="list-disc list-inside space-y-1" id="cancelledList">
                            @foreach($cancelledOrders as $order)
                                <li data-order-id="{{ $order->id }}">
                                    <a href="{{ route('sales_orders.show', $order->id) }}" class="font-bold underline hover:text-gray-200">
                                        {{ $order->sales_order_number }}
                                    </a> - {{ $order->customer->customer_name ?? 'N/A' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button onclick="closeAlert('cancelledAlert')" class="text-white hover:text-gray-200 text-2xl font-bold ml-4 leading-none">
                    ×
                </button>
            </div>
        </div>
    @endif

    @if(count($overdueOrders) > 0)
        <div id="overdueAlert" class="bg-orange-600 text-white p-4 rounded-lg mb-4 shadow-lg" style="display: none;">
            <div class="flex items-start justify-between">
                <div class="flex items-start flex-1">
                    <span class="text-2xl mr-3">⏰</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-2">Overdue Deliveries</h3>
                        <p class="mb-2">The following sales orders have not been delivered by the requested delivery date:</p>
                        <ul class="list-disc list-inside space-y-1" id="overdueList">
                            @foreach($overdueOrders as $order)
                                <li data-order-id="{{ $order->id }}">
                                    <a href="{{ route('sales_orders.show', $order->id) }}" class="font-bold underline hover:text-gray-200">
                                        {{ $order->sales_order_number }}
                                    </a> - {{ $order->customer->customer_name ?? 'N/A' }}
                                    <span class="text-sm opacity-90">
                                        (Expected: {{ \Carbon\Carbon::parse($order->deliveries->request_delivery_date)->format('M d, Y') }})
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button onclick="closeAlert('overdueAlert')" class="text-white hover:text-gray-200 text-2xl font-bold ml-4 leading-none">
                    ×
                </button>
            </div>
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg p-4 mb-4 flex items-end justify-between">
        <!-- FILTER FORM (LEFT) -->
        <form action="{{ route('sales_orders.index') }}" method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm text-gray-300 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Search</label>
                <input type="text" name="search" placeholder="Search..."
                    value="{{ request('search') }}"
                    class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 w-full">
            </div>

            <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                Filter
            </button>

            <a href="{{ route('sales_orders.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
                Clear
            </a>

           @if(request('date_from') || request('date_to') || request('search'))
                @php
                    // Check if any filtered SO is NOT pending
                    $hasNonPendingSO = $salesOrders->contains(function($order) {
                        return $order->status !== 'Pending';
                    });
                @endphp

                @if($hasNonPendingSO)
                    <button type="button" onclick="printList()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
                        🖨️ Print List
                    </button>
                    
                    <button type="button" onclick="exportExcel()"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded transition">
                        📥 Export Excel
                    </button>
                @else
                    <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-300 px-4 py-2 rounded text-sm">
                        ⚠️ Cannot print/export: All filtered sales orders are pending approval
                    </div>
                @endif
            @endif
        </form>

        <!-- CREATE BUTTON (RIGHT) -->
        @if(auth()->user()->canCreateSalesOrders())
            <a href="{{ route('sales_orders.create') }}" 
               class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition whitespace-nowrap">
                Create Sales Order
            </a>
        @endif
    </div>

    <!-- Sales Order Table -->
    <div class="bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">SO Number</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Date Created</th>
                    <th class="px-4 py-3 text-left">Total Amount</th>
                    <th class="px-4 py-3 text-left">SO Status</th>
                    <th class="px-4 py-3 text-left">Requested Date</th>
                    <th class="px-4 py-3 text-left">DR Status</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesOrders as $order)
                <tr class="border-b border-gray-700 hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $order->sales_order_number }}</td>
                    <td class="px-4 py-3">{{ $order->customer->customer_name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $order->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @if($order->status === 'Pending')
                            <span class="bg-gray-500 text-black px-2 py-1 rounded text-xs">Pending</span>
                        @elseif($order->status === 'Approved')
                            <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">Approved</span>
                        @elseif($order->status === 'Declined')
                            <span class="bg-red-600 text-white px-2 py-1 rounded text-xs">Declined</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($order->deliveries && $order->deliveries->request_delivery_date)
                             {{ \Carbon\Carbon::parse($order->deliveries->request_delivery_date)->format('Y-m-d') }}
                        @else
                            <span class="text-gray-500">N/A</span>
                         @endif
                    </td>                   
                     <td class="px-4 py-3">
                        @php
                            $delivery = $order->deliveries;
                            $drStatus = 'Not Delivered';
                            $statusClass = 'bg-gray-600';
                            
                            if ($delivery) {
                                $drStatus = $delivery->status ?? 'Pending';
                                
                                switch($drStatus) {
                                    case 'Pending':
                                        $statusClass = 'bg-gray-500 text-black';
                                        break;
                                    case 'In Transit':
                                    case 'In-Transit':
                                        $statusClass = 'bg-blue-500';
                                        break;
                                    case 'Delivered':
                                        $statusClass = 'bg-green-600';
                                        break;
                                    case 'Cancelled':
                                    case 'Failed':
                                        $statusClass = 'bg-red-600';
                                        break;
                                    default:
                                        $statusClass = 'bg-gray-600';
                                }
                            }
                        @endphp
                        <span class="{{ $statusClass }} text-white px-2 py-1 rounded text-xs">{{ $drStatus }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('sales_orders.show', $order->id) }}" 
                           class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs inline-block">
                            View
                        </a>
                        
                        @if(auth()->user()->canManageSalesOrders())
                            <a href="{{ route('sales_orders.edit', $order->id) }}" 
                               class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded text-xs inline-block ml-2">
                                Edit
                            </a>
                            <form action="{{ route('sales_orders.destroy', $order->id) }}" method="POST" class="inline ml-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs"
                                        onclick="return confirm('Are you sure you want to delete this sales order?')">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-sm text-gray-300">
        Showing {{ $salesOrders->count() }} sales order(s)
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get dismissed order IDs from localStorage
        const dismissedCancelled = JSON.parse(localStorage.getItem('dismissedCancelledOrders') || '[]');
        const dismissedOverdue = JSON.parse(localStorage.getItem('dismissedOverdueOrders') || '[]');
        
        // Handle Cancelled Alert
        const cancelledAlert = document.getElementById('cancelledAlert');
        if (cancelledAlert) {
            const cancelledList = document.getElementById('cancelledList');
            const allCancelledItems = cancelledList.querySelectorAll('li[data-order-id]');
            
            // Filter out dismissed orders
            let visibleCount = 0;
            allCancelledItems.forEach(item => {
                const orderId = parseInt(item.dataset.orderId);
                if (dismissedCancelled.includes(orderId)) {
                    item.remove(); // Remove already-dismissed orders from display
                } else {
                    visibleCount++;
                }
            });
            
            // Only show alert if there are NEW orders
            if (visibleCount > 0) {
                cancelledAlert.style.display = 'block';
            }
        }
        
        // Handle Overdue Alert
        const overdueAlert = document.getElementById('overdueAlert');
        if (overdueAlert) {
            const overdueList = document.getElementById('overdueList');
            const allOverdueItems = overdueList.querySelectorAll('li[data-order-id]');
            
            // Filter out dismissed orders
            let visibleCount = 0;
            allOverdueItems.forEach(item => {
                const orderId = parseInt(item.dataset.orderId);
                if (dismissedOverdue.includes(orderId)) {
                    item.remove(); // Remove already-dismissed orders from display
                } else {
                    visibleCount++;
                }
            });
            
            // Only show alert if there are NEW orders
            if (visibleCount > 0) {
                overdueAlert.style.display = 'block';
            }
        }
    });

    function printList() {
        const dateFrom = document.querySelector('[name="date_from"]').value;
        const dateTo = document.querySelector('[name="date_to"]').value;
        const search = document.querySelector('[name="search"]').value;
        let url = '{{ route("sales_orders.printList") }}?';

        if (dateFrom) url += 'date_from=' + encodeURIComponent(dateFrom) + '&';
        if (dateTo) url += 'date_to=' + encodeURIComponent(dateTo) + '&';
        if (search) url += 'search=' + encodeURIComponent(search);

        window.open(url, '_blank');
    }

    function exportExcel() {
        const dateFrom = document.querySelector('[name="date_from"]').value;
        const dateTo = document.querySelector('[name="date_to"]').value;
        const search = document.querySelector('[name="search"]').value;

        let url = '{{ route("sales_orders.exportExcel") }}?';
        if (dateFrom) url += 'date_from=' + encodeURIComponent(dateFrom) + '&';
        if (dateTo) url += 'date_to=' + encodeURIComponent(dateTo) + '&';
        if (search) url += 'search=' + encodeURIComponent(search);

        window.location.href = url;
    }

    // ✅ SMART DISMISSAL: Remembers specific order IDs
    function closeAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (!alert) return;
        
        // Get all order IDs currently in this alert
        const listId = alertId === 'cancelledAlert' ? 'cancelledList' : 'overdueList';
        const list = document.getElementById(listId);
        const orderIds = Array.from(list.querySelectorAll('li[data-order-id]')).map(item => 
            parseInt(item.dataset.orderId)
        );
        
        // Get existing dismissed orders
        const storageKey = alertId === 'cancelledAlert' ? 'dismissedCancelledOrders' : 'dismissedOverdueOrders';
        const dismissed = JSON.parse(localStorage.getItem(storageKey) || '[]');
        
        // Add current orders to dismissed list
        const updated = [...new Set([...dismissed, ...orderIds])]; // Remove duplicates
        localStorage.setItem(storageKey, JSON.stringify(updated));
        
        // Fade out animation
        alert.style.transition = 'opacity 0.3s ease';
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 300);
    }

    // ✅ OPTIONAL: Clear dismissed history (for testing or manual reset)
    function resetAlerts() {
        localStorage.removeItem('dismissedCancelledOrders');
        localStorage.removeItem('dismissedOverdueOrders');
        location.reload();
    }
</script>

@endsection