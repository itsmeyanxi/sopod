@extends('layouts.app')
@section('title', $pageTitle ?? 'All Recent Activities')
@section('content')
<div class="max-w-7xl mx-auto bg-gray-800 p-8 rounded-lg shadow-md mt-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-white">{{ $pageTitle ?? 'All Recent Activities' }}</h1>
        <a href="{{ $backRoute ?? route('dashboard') }}"
           class="bg-gray-200 hover:bg-gray-300 text-white text-sm px-4 py-2 rounded-lg">
            ← Back to Dashboard
        </a>
    </div>
    @if($recentActivities->isEmpty())
        <p class="text-gray-500">No recent activities found 💤</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-700 text-sm text-left text-white">
                <thead class="bg-gray-700 text-xs uppercase text-gray-200">
                    <tr>
                        <th class="px-4 py-3 border-b border-gray-600">Date</th>
                        <th class="px-4 py-3 border-b border-gray-600">Activity</th>
                        <th class="px-4 py-3 border-b border-gray-600">Type</th>
                        <th class="px-4 py-3 border-b border-gray-600">Item</th>
                        <th class="px-4 py-3 border-b border-gray-600">User</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentActivities as $activity)
                        <tr class="hover:bg-gray-700 border-b border-gray-700">
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($activity->created_at)->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-2">{{ $activity->message ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap
                                    {{ $activity->type === 'Customer' ? 'bg-blue-100 text-blue-700' :
                                       ($activity->type === 'Item' ? 'bg-green-100 text-green-700' :
                                       ($activity->type === 'Sales Order' ? 'bg-yellow-100 text-yellow-700' :
                                       ($activity->type === 'Delivery' ? 'bg-purple-100 text-purple-700' :
                                       ($activity->type === 'Purchase Order' ? 'bg-orange-100 text-orange-700' :
                                       ($activity->type === 'Supplier' ? 'bg-teal-100 text-teal-700' :
                                       ($activity->type === 'Purchase Request' ? 'bg-blue-100 text-blue-700' :
                                       ($activity->type === 'Request For Payment' ? 'bg-purple-100 text-purple-700' :
                                       ($activity->type === 'Accounts Payable Invoice' ? 'bg-orange-100 text-orange-700' :
                                       ($activity->type === 'Check Voucher' ? 'bg-green-100 text-green-700' :
                                       'bg-red-100 text-red-700'))))))))) }}">
                                    {{ $activity->type ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ $activity->item ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $activity->user_name ?? 'System' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-8 flex justify-center pr-2">
            {{ $recentActivities->onEachSide(1)->links('vendor.pagination.elegant') }}
        </div>
    @endif
</div>
@endsection