@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-gray-800 p-6">
    <h1 class="text-white text-2xl font-bold mb-4">Dashboard</h1>

    {{-- ========================================================= --}}
    {{--    NEW SALES ORDER STAT CARDS (Monthly + Status Counts)   --}}
    {{-- ========================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

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
                            <tr class="border-b hover:bg-gray">
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
</div>
@endsection
