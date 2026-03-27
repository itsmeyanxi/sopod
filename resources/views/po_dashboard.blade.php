@extends('layouts.app')

@section('title', 'PO Dashboard')

@section('content')
<div class="bg-white p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-yellow text-2xl font-bold">PO Module Dashboard</h1>
    </div>

    {{-- ========================================================= --}}
    {{--                     PENDING COUNTS                        --}}
    {{-- ========================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">

        <a href="{{ route('purchase_requests.index') }}" class="bg-blue-700 p-6 rounded shadow hover:bg-blue-600 transition w-full text-left block">
            <h3 class="text-gray-800 text-lg font-semibold">Pending PR</h3>
            <p class="text-4xl text-gray-800 font-bold mt-2">{{ $pendingPRs }}</p>
            <p class="text-blue-700 text-sm mt-1">Purchase Requests</p>
        </a>

        <a href="{{ route('purchase_orders.index') }}" class="bg-yellow-700 p-6 rounded shadow hover:bg-yellow-600 transition w-full text-left block">
            <h3 class="text-gray-800 text-lg font-semibold">Pending PO</h3>
            <p class="text-4xl text-gray-800 font-bold mt-2">{{ $pendingPOs }}</p>
            <p class="text-yellow-200 text-sm mt-1">Purchase Orders</p>
        </a>

        <a href="{{ route('request_for_payments.index') }}" class="bg-purple-700 p-6 rounded shadow hover:bg-purple-600 transition w-full text-left block">
            <h3 class="text-gray-800 text-lg font-semibold">Pending RFP</h3>
            <p class="text-4xl text-gray-800 font-bold mt-2">{{ $pendingRFPs }}</p>
            <p class="text-purple-700 text-sm mt-1">Request for Payments</p>
        </a>

        <a href="{{ route('accounts_payable_invoices.index') }}" class="bg-orange-700 p-6 rounded shadow hover:bg-orange-600 transition w-full text-left block">
            <h3 class="text-gray-800 text-lg font-semibold">Pending APV</h3>
            <p class="text-4xl text-gray-800 font-bold mt-2">{{ $pendingAPVs }}</p>
            <p class="text-orange-200 text-sm mt-1">Accounts Payable Invoices</p>
        </a>

        <a href="{{ route('check_vouchers.index') }}" class="bg-green-700 p-6 rounded shadow hover:bg-green-600 transition w-full text-left block">
            <h3 class="text-gray-800 text-lg font-semibold">Pending CV</h3>
            <p class="text-4xl text-gray-800 font-bold mt-2">{{ $pendingCVs }}</p>
            <p class="text-green-700 text-sm mt-1">Check Vouchers</p>
        </a>

    </div>

    {{-- ========================================================= --}}
    {{--                  MODULE QUICK LINKS                       --}}
    {{-- ========================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">

        <div class="bg-gray-100 p-6 shadow rounded">
            <a href="{{ route('purchase_requests.index') }}" class="text-gray-800 text-xl font-semibold mb-2 block">
                Purchase Requests
            </a>
            <p class="text-gray-500 mb-4 text-sm">Create and manage PRs...</p>
            <a href="{{ route('purchase_requests.index') }}" class="text-gray-500 text-sm hover:underline">View all</a>
        </div>

        <div class="bg-gray-100 p-6 shadow rounded">
            <a href="{{ route('purchase_orders.index') }}" class="text-gray-800 text-xl font-semibold mb-2 block">
                Purchase Orders
            </a>
            <p class="text-gray-500 mb-4 text-sm">Track purchase orders...</p>
            <a href="{{ route('purchase_orders.index') }}" class="text-gray-500 text-sm hover:underline">View all</a>
        </div>

        <div class="bg-gray-100 p-6 shadow rounded">
            <a href="{{ route('request_for_payments.index') }}" class="text-gray-800 text-xl font-semibold mb-2 block">
                Request for Payments
            </a>
            <p class="text-gray-500 mb-4 text-sm">Manage payment requests...</p>
            <a href="{{ route('request_for_payments.index') }}" class="text-gray-500 text-sm hover:underline">View all</a>
        </div>

        <div class="bg-gray-100 p-6 shadow rounded">
            <a href="{{ route('accounts_payable_invoices.index') }}" class="text-gray-800 text-xl font-semibold mb-2 block">
                AP Invoices
            </a>
            <p class="text-gray-500 mb-4 text-sm">Manage payable invoices...</p>
            <a href="{{ route('accounts_payable_invoices.index') }}" class="text-gray-500 text-sm hover:underline">View all</a>
        </div>

        <div class="bg-gray-100 p-6 shadow rounded">
            <a href="{{ route('check_vouchers.index') }}" class="text-gray-800 text-xl font-semibold mb-2 block">
                Check Vouchers
            </a>
            <p class="text-gray-500 mb-4 text-sm">Manage check vouchers...</p>
            <a href="{{ route('check_vouchers.index') }}" class="text-gray-500 text-sm hover:underline">View all</a>
        </div>

    </div>

    {{-- ========================================================= --}}
    {{--                     RECENT ACTIVITIES                    --}}
    {{-- ========================================================= --}}
    @php
        $recentActivities = $recentActivities ?? collect();
    @endphp

    <div class="bg-white shadow rounded p-6">
        <h2 class="text-gray-800 font-semibold mb-4">Recent Activity</h2>

        @if($recentActivities->isEmpty())
            <p class="text-gray-800 text-sm">No recent activities yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-gray-800">
                    <thead class="text-xs uppercase bg-white text-gray-800">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Activity</th>
                            <th class="px-4 py-2">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivities as $activity)
                            <tr class="border-b hover:bg-gray-100">
                                <td class="px-4 py-2">{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $activity->message }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs
                                        {{ $activity->type === 'Purchase Request' ? 'bg-blue-100 text-blue-600' :
                                           ($activity->type === 'Purchase Order' ? 'bg-yellow-100 text-yellow-600' :
                                           ($activity->type === 'Request For Payment' ? 'bg-purple-100 text-purple-600' :
                                           ($activity->type === 'Accounts Payable Invoice' ? 'bg-orange-100 text-orange-600' :
                                           ($activity->type === 'Check Voucher' ? 'bg-green-100 text-green-600' :
                                           ($activity->type === 'Supplier' ? 'bg-teal-100 text-teal-600' : 'bg-gray-100 text-gray-600'))))) }}">
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
            <a href="{{ route('recent_activities.index', ['module' => 'po']) }}"
               class="bg-gray-100 hover:bg-gray-100 text-gray-800 px-4 py-2 rounded">
                View All
            </a>
        </div>
    </div>

</div>
@endsection
