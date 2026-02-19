@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-gray-800 p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-yellow text-2xl font-bold">Dashboard</h1>
    </div>

    {{-- ========================================================= --}}
    {{--    SALES ORDER STATS + MONTH-TO-DATE SALES               --}}
    {{-- ========================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">

        <div class="bg-gray-700 p-6 rounded shadow">
            <h3 class="text-white text-lg font-semibold">Sales Order</h3>
            <p class="text-3xl text-white font-bold mt-2">{{ $totalSoThisMonth }}</p>
        </div>

        <div class="bg-gray-700 p-6 rounded shadow">
            <h3 class="text-white text-lg font-semibold">Delivered</h3>
            <p class="text-3xl text-white font-bold mt-2">{{ $totalDelivered }}</p>
        </div>

        <div class="bg-gray-700 p-6 rounded shadow">
            <h3 class="text-white text-lg font-semibold">Pending SO</h3>
            <p class="text-3xl text-white font-bold mt-2">{{ $totalPending }}</p>
        </div>

        <div class="bg-gray-700 p-6 rounded shadow">
            <h3 class="text-white text-lg font-semibold">Declined SO</h3>
            <p class="text-3xl text-white font-bold mt-2">{{ $totalDeclined }}</p>
        </div>

        <button onclick="openApprovedPOModal()" class="bg-blue-700 p-6 rounded shadow hover:bg-blue-600 transition cursor-pointer w-full text-left">
            <h3 class="text-white text-lg font-semibold">
                Approved PO
            </h3>
            <p class="text-3xl text-white font-bold mt-2">{{ $approvedPendingPOs }}</p>
            <p class="text-gray-200 text-xs mt-2">Pending delivery</p>
        </button>

    </div>

    {{-- ========================================================= --}}
    {{--   EXISTING SECTION: SALES ORDERS / CUSTOMERS / ITEMS /    --}}
    {{--                     DELIVERIES MODULE LINKS               --}}
    {{-- ========================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <div class="bg-gray-700 p-6 shadow rounded">
            <a href="{{ route('sales_orders.index') }}" class="text-white text-xl font-semibold mb-2 block">
                Sales Orders
            </a>
            <p class="text-white mb-4">Quick stats about your orders...</p>
            <a href="{{ route('sales_orders.index') }}" class="text-gray-50 text-sm hover:underline">View all</a>
        </div>

        <div class="bg-gray-700 p-6 shadow rounded">
            <a href="{{ route('customers.index') }}" class="text-white text-xl font-semibold mb-2 block">
                Customers
            </a>
            <p class="text-white mb-4">Track your customer base...</p>
            <a href="{{ route('customers.index') }}" class="text-gray-50 text-sm hover:underline">View all</a>
        </div>

        <div class="bg-gray-700 p-6 shadow rounded">
            <a href="{{ route('items.index') }}" class="text-white text-xl font-semibold mb-2 block">
                Items
            </a>
            <p class="text-white mb-4">Manage your inventory...</p>
            <a href="{{ route('items.index') }}" class="text-gray-50 text-sm hover:underline">View all</a>
        </div>

        <div class="bg-gray-700 p-6 shadow rounded">
            <a href="{{ route('deliveries.index') }}" class="text-white text-xl font-semibold mb-2 block">
                Deliveries
            </a>
            <p class="text-white mb-4">Track and manage deliveries...</p>
            <a href="{{ route('deliveries.index') }}" class="text-gray-50 text-sm hover:underline">View all</a>
        </div>

    </div>

    {{-- ========================================================= --}}
    {{--                     RECENT ACTIVITIES                    --}}
    {{-- ========================================================= --}}
    @php
        $recentActivities = $recentActivities ?? collect();
    @endphp

    <div class="bg-gray-800 shadow rounded p-6">
        <h2 class="text-white font-semibold mb-4">Recent Activity</h2>

        @if($recentActivities->isEmpty())
            <p class="text-white text-sm">No recent activities yet 💤</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-white">
                    <thead class="text-xs uppercase bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Activity</th>
                            <th class="px-4 py-2">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivities as $activity)
                            <tr class="border-b hover:bg-gray-600">
                                <td class="px-4 py-2">{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $activity->message }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs
                                        {{ $activity->type === 'Customer' ? 'bg-blue-100 text-blue-600' :
                                           ($activity->type === 'Item' ? 'bg-green-100 text-green-600' :
                                           ($activity->type === 'Sales Order' ? 'bg-yellow-100 text-yellow-600' :
                                           ($activity->type === 'Delivery' ? 'bg-purple-100 text-purple-600' : 'bg-red-100 text-red-600'))) }}">
                                        {{ $activity->type }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="text-center mt-4">
            <a href="{{ route('recent_activities.index') }}" 
               class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded">
                View All
            </a>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{--         APPROVED PO MODAL                               --}}
    {{-- ========================================================= --}}
    <div id="approvedPOModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-lg shadow-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-gray-800 border-b border-gray-700 p-6 flex justify-between items-center">
                <h2 class="text-white text-2xl font-bold">Approved Purchase Orders</h2>
                <button onclick="closeApprovedPOModal()" class="text-gray-400 hover:text-white text-2xl">×</button>
            </div>

            <div class="p-6">
                @if($approvedPendingPOsList->isEmpty())
                    <p class="text-gray-300 text-center py-8">No approved purchase orders pending delivery</p>
                @else
                    <div class="space-y-3">
                        @foreach($approvedPendingPOsList as $po)
                            <a href="{{ route('purchase_orders.show', $po->id) }}"
                               class="block bg-gray-700 hover:bg-gray-600 transition p-4 rounded border border-gray-600 hover:border-blue-500">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="text-white font-semibold text-lg">
                                            {{ $po->po_no }}
                                        </h3>
                                        <p class="text-gray-300 text-sm mt-1">
                                            <span class="font-semibold">Supplier:</span> {{ $po->supplier }}
                                        </p>
                                        <p class="text-gray-400 text-xs mt-1">
                                            Created: {{ $po->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block bg-blue-600 text-white px-3 py-1 rounded text-xs font-semibold">
                                            {{ ucfirst($po->status) }}
                                        </span>
                                        <p class="text-gray-300 font-semibold text-sm mt-2">
                                            ₱{{ number_format($po->items->sum(function($item) { return $item->total; }), 2) }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="sticky bottom-0 bg-gray-800 border-t border-gray-700 p-6 flex justify-end gap-3">
                <button onclick="closeApprovedPOModal()" class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2 rounded transition">
                    Close
                </button>
                <a href="{{ route('purchase_orders.index') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded transition inline-block">
                    View All POs
                </a>
            </div>
        </div>
    </div>

    <script>
        function openApprovedPOModal() {
            document.getElementById('approvedPOModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeApprovedPOModal() {
            document.getElementById('approvedPOModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('approvedPOModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeApprovedPOModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApprovedPOModal();
            }
        });
    </script>
</div>
@endsection