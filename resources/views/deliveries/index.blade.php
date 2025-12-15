@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">Deliveries List</h1>
        
        {{-- 🆕 Create Delivery --}}
        @if(auth()->user()->canManageDeliveries())
            <a href="{{ route('deliveries.deliveries') }}" 
               class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition"
               title="Create Delivery"> Create delivery
                <i class="fas fa-plus text-lg"></i>
            </a>
        @endif
    </div>

    {{-- ✅ Alerts --}}
    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @elseif(session('pullout'))
        <div class="bg-orange-600 text-white p-3 rounded mb-4 flex items-center gap-2">
            <span class="text-xl">🔒</span>
            <span>{{ session('pullout') }}</span>
        </div>
    @endif

    {{-- 📅 Enhanced Filter Form --}}
    <div class="bg-gradient-to-br from-gray-800 to-gray-850 rounded-2xl shadow-2xl p-6 mb-6 border border-gray-700/50">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <h2 class="text-lg font-semibold text-gray-200">Filter Options</h2>
        </div>

        <form method="GET" action="{{ route('deliveries.index') }}">
            {{-- Main Filter Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                {{-- Date Range Section --}}
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-300 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Date From
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                            class="w-full bg-gray-700/50 text-white px-4 py-2.5 rounded-lg border border-gray-600 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all outline-none">
                    </div>

                    {{-- Date To --}}
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-300 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Date To
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                            class="w-full bg-gray-700/50 text-white px-4 py-2.5 rounded-lg border border-gray-600 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all outline-none">
                    </div>
                </div>

                {{-- Search Box --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Search
                    </label>
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search DR No, SO, Customer..." value="{{ request('search') }}"
                            class="w-full bg-gray-700/50 text-white px-4 py-2.5 pl-10 rounded-lg border border-gray-600 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all outline-none">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Status Filters Row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 p-4 bg-gray-900/30 rounded-xl border border-gray-700/30">
                {{-- Delivery Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">📦 Delivery Status</label>
                    <select name="status" class="w-full bg-gray-700/50 text-white px-4 py-2.5 rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>⏳ Pending</option>
                        <option value="Delivered" {{ request('status') == 'Delivered' ? 'selected' : '' }}>✓ Delivered</option>
                        <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>✗ Cancelled</option>
                    </select>
                </div>

                {{-- Approval Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">✅ Approval Status</label>
                    <select name="approval_status" class="w-full bg-gray-700/50 text-white px-4 py-2.5 rounded-lg border border-gray-600 focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all outline-none cursor-pointer">
                        <option value="">All Approval Status</option>
                        <option value="Pending" {{ request('approval_status') == 'Pending' ? 'selected' : '' }}>⏳ Pending Approval</option>
                        <option value="Approved" {{ request('approval_status') == 'Approved' ? 'selected' : '' }}>✓ Approved</option>
                        <option value="Rejected" {{ request('approval_status') == 'Rejected' ? 'selected' : '' }}>✗ Rejected</option>
                    </select>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-3 items-center">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-2.5 rounded-lg transition-all shadow-lg hover:shadow-blue-500/30 flex items-center gap-2 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Apply Filters
                </button>

                <a href="{{ route('deliveries.index') }}" class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2.5 rounded-lg transition-all flex items-center gap-2 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Clear All
                </a>

                @if(request('date_from') || request('date_to') || request('search') || request('status') || request('approval_status'))
                    <div class="h-8 w-px bg-gray-600"></div>
                    
                    <button type="button" onclick="printList()" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-2.5 rounded-lg transition-all shadow-lg hover:shadow-green-500/30 flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print List
                    </button>

                    <button type="button" onclick="exportExcel()" class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-6 py-2.5 rounded-lg transition-all shadow-lg hover:shadow-emerald-500/30 flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Excel
                    </button>
                @endif
            </div>

            {{-- Active Filters Display --}}
            @if(request('status') || request('approval_status') || request('date_from') || request('date_to'))
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-medium text-gray-400 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Active Filters:
                        </span>
                        
                        @if(request('date_from'))
                            <span class="bg-purple-600/20 border border-purple-600/30 text-purple-300 px-3 py-1.5 rounded-full text-sm flex items-center gap-2 hover:bg-purple-600/30 transition-all">
                                📅 From: {{ request('date_from') }}
                                <a href="{{ route('deliveries.index', request()->except('date_from')) }}" class="hover:text-white">×</a>
                            </span>
                        @endif

                        @if(request('date_to'))
                            <span class="bg-purple-600/20 border border-purple-600/30 text-purple-300 px-3 py-1.5 rounded-full text-sm flex items-center gap-2 hover:bg-purple-600/30 transition-all">
                                📅 To: {{ request('date_to') }}
                                <a href="{{ route('deliveries.index', request()->except('date_to')) }}" class="hover:text-white">×</a>
                            </span>
                        @endif

                        @if(request('status'))
                            <span class="bg-blue-600/20 border border-blue-600/30 text-blue-300 px-3 py-1.5 rounded-full text-sm flex items-center gap-2 hover:bg-blue-600/30 transition-all">
                                📦 Status: {{ request('status') }}
                                <a href="{{ route('deliveries.index', request()->except('status')) }}" class="hover:text-white">×</a>
                            </span>
                        @endif

                        @if(request('approval_status'))
                            <span class="bg-green-600/20 border border-green-600/30 text-green-300 px-3 py-1.5 rounded-full text-sm flex items-center gap-2 hover:bg-green-600/30 transition-all">
                                ✅ Approval: {{ request('approval_status') }}
                                <a href="{{ route('deliveries.index', request()->except('approval_status')) }}" class="hover:text-white">×</a>
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </form>
    </div>

    {{-- 📋 Deliveries Table --}}
    <div class="bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-gray-800">
            <table id="deliveriesTable" class="min-w-full text-sm border-collapse">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">DR No</th>
                        <th class="px-4 py-3 text-left">Sales Order</th>
                        <th class="px-4 py-3 text-left">Batch</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Quantity</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Approval</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition-colors {{ $delivery->is_pulled_out ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3">
                            {{ $delivery->dr_no }}
                            @if($delivery->is_pulled_out)
                                <span class="ml-2 text-xs text-orange-400">🔒</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $delivery->sales_order_number }}</td>
                        <td class="px-4 py-3">
                            @if($delivery->delivery_batch)
                                @php
                                    $parts = explode('-', $delivery->delivery_batch);
                                    $dateStr = end($parts);
                                    try {
                                        $batchDate = \Carbon\Carbon::parse($dateStr)->format('M d');
                                    } catch (\Exception $e) {
                                        $batchDate = $delivery->delivery_batch;
                                    }
                                @endphp
                                <span class="bg-purple-600/30 text-purple-300 px-2 py-1 rounded text-xs">
                                    📦 {{ $batchDate }}
                                </span>
                            @else
                                <span class="text-gray-500 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{ $delivery->customer_name 
                               ?? $delivery->salesOrder?->customer?->customer_name 
                               ?? $delivery->salesOrder?->client_name 
                               ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">{{ number_format($delivery->quantity ?? 0, 2) }}</td>
                        <td class="px-4 py-3">₱{{ number_format($delivery->total_amount ?? 0, 2) }}</td>  
                        
                        {{-- Approval Status --}}
                        <td class="px-4 py-3">
                            @if($delivery->approval_status === 'Pending')
                                <span class="bg-yellow-600/30 text-yellow-300 px-2 py-1 rounded text-xs">⏳ Pending</span>
                            @elseif($delivery->approval_status === 'Approved')
                                <span class="bg-green-600/30 text-green-300 px-2 py-1 rounded text-xs">✓ Approved</span>
                            @elseif($delivery->approval_status === 'Rejected')
                                <span class="bg-red-600/30 text-red-300 px-2 py-1 rounded text-xs">✗ Rejected</span>
                            @endif
                        </td>
                        
                        {{-- Delivery Status --}}
                        <td class="px-4 py-3">
                            @if($delivery->is_pulled_out)
                                <span class="bg-orange-600 text-white px-2 py-1 rounded text-xs">🔒 Pulled Out</span>
                            @elseif($delivery->status === 'Cancelled')
                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs">Cancelled</span>
                            @elseif($delivery->status === 'Delivered')
                                <span class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Delivered</span>
                            @else
                                <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs">{{ $delivery->status }}</span>
                            @endif
                        </td>
                        
                            <td class="px-4 py-3 text-center">
                                <div class="flex gap-2 justify-center items-center flex-wrap">
                                    {{-- View Button --}}
                                    <a href="{{ route('deliveries.show', $delivery->id) }}" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-xs">
                                        View
                                    </a>
                                    
                                    {{-- ✅ DELIVERY APPROVER: Can only edit PENDING deliveries --}}
                                    @if(\App\Helpers\RoleHelper::canApproveDeliveries() && 
                                        $delivery->approval_status === 'Pending' &&
                                        !$delivery->is_pulled_out)
                                        <button onclick="openEditModal({{ $delivery->id }})"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-xs">
                                            ✎ Edit
                                        </button>
                                    @endif
                                    
                                    {{-- ✅ DELIVERY CREATOR: Request Edit for PENDING deliveries only --}}
                                    @if(\App\Helpers\RoleHelper::canManageDeliveries() && 
                                        !\App\Helpers\RoleHelper::canApproveDeliveries() &&
                                        !$delivery->is_pulled_out && 
                                        $delivery->approval_status === 'Pending' && 
                                        !$delivery->edit_requested && 
                                        !$delivery->edit_approved)
                                        <button onclick="requestEditPermission({{ $delivery->id }})"
                                                class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded-md text-xs">
                                            📝 Request Edit
                                        </button>
                                    @endif
                                    
                                    {{-- ✅ EDIT PENDING INDICATOR (When edit requested but not yet approved) --}}
                                    @if($delivery->edit_requested && 
                                        !$delivery->edit_approved && 
                                        \App\Helpers\RoleHelper::canManageDeliveries() && 
                                        !\App\Helpers\RoleHelper::canApproveDeliveries())
                                        <span class="bg-yellow-600/30 text-yellow-300 px-3 py-1 rounded-md text-xs">
                                            ⏳ Edit Pending
                                        </span>
                                    @endif
                                    
                                    {{-- ✅ EDIT BUTTON FOR CREATOR (when edit approved by approver) --}}
                                    @if(\App\Helpers\RoleHelper::canManageDeliveries() && 
                                        !\App\Helpers\RoleHelper::canApproveDeliveries() &&
                                        $delivery->edit_approved && 
                                        $delivery->approval_status === 'Pending' &&
                                        !$delivery->is_pulled_out)
                                        <button onclick="openEditModal({{ $delivery->id }})"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-xs animate-pulse">
                                            ✎ Edit Now
                                        </button>
                                    @endif
                                    
                                    {{-- ✅ APPROVE/REJECT EDIT BUTTONS (Delivery Approver only, for pending deliveries) --}}
                                    @if(\App\Helpers\RoleHelper::canApproveDeliveries() && 
                                        $delivery->edit_requested && 
                                        !$delivery->edit_approved && 
                                        $delivery->approval_status === 'Pending' &&
                                        !$delivery->is_pulled_out)
                                        <button onclick="approveEditRequest({{ $delivery->id }})"
                                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-xs">
                                            ✓ Approve Edit
                                        </button>
                                        <button onclick="showRejectEditModal({{ $delivery->id }})"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-xs">
                                            ✗ Reject Edit
                                        </button>
                                    @endif
                                    
                                    {{-- Approve/Reject Delivery Buttons (only for pending deliveries) --}}
                                    @if(\App\Helpers\RoleHelper::canApproveDeliveries() && 
                                        $delivery->approval_status === 'Pending' && 
                                        !$delivery->is_pulled_out)
                                        <button onclick="approveDelivery({{ $delivery->id }})"
                                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-xs">
                                            ✓ Approve
                                        </button>
                                        <button onclick="showRejectModal({{ $delivery->id }})"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-xs">
                                            ✗ Reject
                                        </button>
                                    @endif
                                    
                                    {{-- Pullout Button (only for approvers and approved deliveries) --}}
                                    @if(\App\Helpers\RoleHelper::canApproveDeliveries() && 
                                        $delivery->approval_status === 'Approved' &&
                                        !$delivery->is_pulled_out)
                                        <button onclick="showPulloutModal({{ $delivery->id }})"
                                                class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded-md text-xs">
                                            🔒 Pullout
                                        </button>
                                    @endif
                                </div>
                            </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-gray-400 py-4">No deliveries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 text-sm text-gray-300">
        Showing {{ $deliveries->count() }} delivery record(s)
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4 text-white">Reject Delivery</h3>
        <form id="rejectForm">
            <input type="hidden" id="rejectDeliveryId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Rejection Reason</label>
                <textarea id="rejectionReason" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white"
                          rows="4" 
                          required
                          placeholder="Please provide a reason for rejection..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" 
                        onclick="closeRejectModal()"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                    Reject Delivery
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Pullout Modal --}}
<div id="pulloutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4 text-white">Pullout Delivery</h3>
        <div class="mb-4 bg-orange-900/20 border border-orange-700 p-3 rounded">
            <p class="text-orange-300 text-sm">
                ⚠️ Warning: Pulling out this delivery will cancel it and lock it from further editing.
            </p>
        </div>
        <form id="pulloutForm">
            <input type="hidden" id="pulloutDeliveryId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Pullout Reason</label>
                <textarea id="pulloutReason" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white"
                          rows="4" 
                          required
                          placeholder="Please provide a reason for pulling out this delivery..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" 
                        onclick="closePulloutModal()"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded">
                    Confirm Pullout
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ✅ Edit Delivery Modal - WITH PO IMAGE --}}
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-gray-800 rounded-lg p-6 max-w-5xl w-full mx-4 my-8 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-white">Edit Delivery</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-white text-2xl">×</button>
        </div>
        
        <form id="editForm">
            <input type="hidden" id="editDeliveryId">
            
            {{-- Read-only Information --}}
            <div class="mb-6 bg-gray-700/50 p-4 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-300 mb-3">Delivery Information (Read-Only)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <label class="block text-gray-400 mb-1">Sales Order</label>
                        <input type="text" id="edit_sales_order" readonly 
                               class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded text-gray-300 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1">Customer</label>
                        <input type="text" id="edit_customer" readonly 
                               class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded text-gray-300 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1">Batch</label>
                        <input type="text" id="edit_batch" readonly 
                               class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded text-gray-300 cursor-not-allowed">
                    </div>
                </div>
            </div>

            {{-- ✅ PO IMAGE SECTION --}}
            <div id="poImageSection" class="mb-6 bg-blue-900/20 border border-blue-700 p-4 rounded-lg" style="display: none;">
                <h4 class="text-sm font-semibold text-blue-300 mb-3">📎 Purchase Order Image</h4>
                <div class="flex items-center gap-4">
                    <img id="poImagePreview" class="max-w-xs max-h-48 rounded border border-gray-600 cursor-pointer" 
                         onclick="openPOImageFullscreen()" 
                         title="Click to view full size">
                    <div>
                        <p class="text-sm text-gray-300 mb-2">File: <span id="poImageFileName" class="text-blue-300"></span></p>
                        <button type="button" 
                                onclick="openPOImageFullscreen()"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                            🔍 View Full Size
                        </button>
                    </div>
                </div>
            </div>

            {{-- Editable Fields --}}
            <div class="mb-6 bg-gray-900/50 p-4 rounded-lg border border-green-700/30">
                <h4 class="text-sm font-semibold text-green-400 mb-3">✎ Editable Fields</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">DR No. <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_dr_no" required
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">SI Invoice No.</label>
                        <input type="text" id="edit_si_no"
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">PO Number</label>
                        <input type="text" id="edit_po_number"
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Plate No.</label>
                        <input type="text" id="edit_plate_no"
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-300 mb-3">Delivery Items - Edit Quantities</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                            <tr>
                                <th class="px-3 py-2 text-left">Item Code</th>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-left">SO Qty</th>
                                <th class="px-3 py-2 text-left">Already Delivered</th>
                                <th class="px-3 py-2 text-left">DR Qty <span class="text-green-400">*</span></th>
                                <th class="px-3 py-2 text-left">Remaining</th>
                                <th class="px-3 py-2 text-right">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody id="editItemsTableBody" class="text-white">
                            <!-- Items will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-3 justify-end pt-4 border-t border-gray-700">
                <button type="button" 
                        onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition">
                    💾 Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ✅ PO Image Fullscreen Modal --}}
<div id="poImageFullscreenModal" class="hidden fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[100]">
    <div class="relative max-w-7xl w-full h-full p-4 flex items-center justify-center">
        <button onclick="closePOImageFullscreen()" 
                class="absolute top-4 right-4 text-white bg-red-600 hover:bg-red-700 rounded-full w-10 h-10 flex items-center justify-center text-2xl z-10">
            ×
        </button>
        <img id="poImageFullscreen" class="max-w-full max-h-full object-contain">
    </div>
</div>

{{-- ============================================ --}}
{{-- COMPLETE SCRIPT SECTION - Replace entire <script> section in deliveries/index.blade.php --}}
{{-- Place this BEFORE @endsection --}}
{{-- ============================================ --}}

{{-- ✅ ADD REJECT EDIT MODAL (if not already added) --}}
{{-- Place this after pulloutModal --}}
<div id="rejectEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4 text-white">Reject Edit Request</h3>
        <form id="rejectEditForm">
            <input type="hidden" id="rejectEditDeliveryId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Rejection Reason</label>
                <textarea id="editRejectionReason" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white"
                          rows="4" 
                          required
                          placeholder="Please provide a reason for rejecting the edit request..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" 
                        onclick="closeRejectEditModal()"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                    Reject Edit Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ========================================
// GLOBAL VARIABLES
// ========================================
let currentPOImageUrl = null;
let editDeliveryData = null;

// DELIVERY APPROVAL
function approveDelivery(id) {
    if (!confirm('Are you sure you want to approve this delivery?')) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('input[name="_token"]')?.value;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        console.error('CSRF token not found');
        return;
    }
    
    fetch(`/deliveries/${id}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to approve delivery');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while approving the delivery: ' + error.message);
    });
}

function showRejectModal(id) {
    document.getElementById('rejectDeliveryId').value = id;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectionReason').value = '';
}

document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('rejectDeliveryId').value;
    const reason = document.getElementById('rejectionReason').value;
    
    if (!reason.trim()) {
        alert('Please provide a rejection reason');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('input[name="_token"]')?.value;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        console.error('CSRF token not found');
        return;
    }
    
    fetch(`/deliveries/${id}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ rejection_reason: reason })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeRejectModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to reject delivery');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while rejecting the delivery: ' + error.message);
    });
});

// ========================================
// PULLOUT FUNCTIONS
// ========================================

function showPulloutModal(id) {
    document.getElementById('pulloutDeliveryId').value = id;
    document.getElementById('pulloutModal').classList.remove('hidden');
}

function closePulloutModal() {
    document.getElementById('pulloutModal').classList.add('hidden');
    document.getElementById('pulloutReason').value = '';
}

document.getElementById('pulloutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('pulloutDeliveryId').value;
    const reason = document.getElementById('pulloutReason').value;
    
    if (!reason.trim()) {
        alert('Please provide a pullout reason');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('input[name="_token"]')?.value;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        return;
    }
    
    fetch(`/deliveries/${id}/pullout`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ pullout_reason: reason })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            sessionStorage.setItem('pulloutMessage', data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to pullout delivery');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred: ' + error.message);
    });
});

// ========================================
// ✅ EDIT APPROVAL FUNCTIONS (NEW)
// ========================================

// ✅ Request Edit Permission (Delivery Creator)
function requestEditPermission(id) {
    console.log('🔵 Request edit clicked for delivery ID:', id);
    
    if (!confirm('Request permission to edit this approved delivery?\n\nThis will notify the approver for their decision.')) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        console.error('❌ CSRF token not found');
        return;
    }
    
    console.log('📤 Sending request to /deliveries/' + id + '/request-edit');
    
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Requesting...';
    
    fetch(`/deliveries/${id}/request-edit`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('📥 Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Response data:', data);
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + (data.message || 'Failed to request edit permission'));
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert('An error occurred: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// ✅ Approve Edit Request (Delivery Approver)
function approveEditRequest(id) {
    console.log('🔵 Approve edit clicked for delivery ID:', id);
    
    if (!confirm('Approve this edit request?\n\nThe creator will be able to edit the delivery once.')) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        console.error('❌ CSRF token not found');
        return;
    }
    
    console.log('📤 Sending request to /deliveries/' + id + '/approve-edit');
    
    fetch(`/deliveries/${id}/approve-edit`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('📥 Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Response data:', data);
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + (data.message || 'Failed to approve edit request'));
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert('An error occurred: ' + error.message);
    });
}

// ✅ Show Reject Edit Modal
function showRejectEditModal(id) {
    console.log('🔵 Reject edit modal opened for delivery ID:', id);
    document.getElementById('rejectEditDeliveryId').value = id;
    document.getElementById('rejectEditModal').classList.remove('hidden');
}

// ✅ Close Reject Edit Modal
function closeRejectEditModal() {
    document.getElementById('rejectEditModal').classList.add('hidden');
    document.getElementById('editRejectionReason').value = '';
}

// ✅ Reject Edit Request Form Submit
document.getElementById('rejectEditForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('rejectEditDeliveryId').value;
    const reason = document.getElementById('editRejectionReason').value;
    
    console.log('🔵 Rejecting edit for delivery ID:', id, 'Reason:', reason);
    
    if (!reason.trim()) {
        alert('Please provide a rejection reason');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        console.error('❌ CSRF token not found');
        return;
    }
    
    console.log('📤 Sending reject request to /deliveries/' + id + '/reject-edit');
    
    fetch(`/deliveries/${id}/reject-edit`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ rejection_reason: reason })
    })
    .then(response => {
        console.log('📥 Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Response data:', data);
        if (data.success) {
            alert('✅ ' + data.message);
            closeRejectEditModal();
            location.reload();
        } else {
            alert('❌ ' + (data.message || 'Failed to reject edit request'));
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert('An error occurred: ' + error.message);
    });
});

// ========================================
// EDIT MODAL FUNCTIONS
// ========================================

function openEditModal(id) {
    console.log('🔵 Opening edit modal for delivery ID:', id);
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        return;
    }
    
    console.log('📤 Fetching edit data from /deliveries/' + id + '/edit-data');
    
    fetch(`/deliveries/${id}/edit-data`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => {
        console.log('📥 Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Edit data received:', data);
        if (data.success) {
            editDeliveryData = data;
            populateEditModal(data);
            document.getElementById('editModal').classList.remove('hidden');
        } else {
            alert(data.message || 'Failed to load delivery data');
        }
    })
    .catch(error => {
        console.error('❌ Error loading edit data:', error);
        alert('An error occurred while loading delivery data: ' + error.message);
    });
}

function populateEditModal(data) {
    document.getElementById('editDeliveryId').value = data.id;
    document.getElementById('edit_sales_order').value = data.sales_order_number || '';
    document.getElementById('edit_customer').value = data.customer_name || '';
    document.getElementById('edit_batch').value = data.delivery_batch || '';
    document.getElementById('edit_dr_no').value = data.dr_no || '';
    document.getElementById('edit_si_no').value = data.sales_invoice_no || '';
    document.getElementById('edit_po_number').value = data.po_number || '';
    document.getElementById('edit_plate_no').value = data.plate_no || '';
    
    const poImageSection = document.getElementById('poImageSection');
    const poImagePreview = document.getElementById('poImagePreview');
    const poImageFileName = document.getElementById('poImageFileName');
    
    if (data.po_image_url && data.po_image_name) {
        poImageSection.style.display = 'block';
        poImagePreview.src = data.po_image_url;
        poImageFileName.textContent = data.po_image_name;
        currentPOImageUrl = data.po_image_url;
        console.log('✅ PO Image loaded:', data.po_image_name);
    } else {
        poImageSection.style.display = 'none';
        currentPOImageUrl = null;
        console.log('ℹ️ No PO image available');
    }
    
    const tbody = document.getElementById('editItemsTableBody');
    tbody.innerHTML = '';
    
    data.items.forEach((item, index) => {
        const row = document.createElement('tr');
        row.className = 'border-b border-gray-700';
        
        const remaining = Math.max(0, item.original_quantity - item.already_delivered - item.quantity);
        
        row.innerHTML = `
            <td class="px-3 py-2">${item.item_code || '—'}</td>
            <td class="px-3 py-2">${item.item_description || '—'}</td>
            <td class="px-3 py-2">${parseFloat(item.original_quantity || 0).toFixed(2)}</td>
            <td class="px-3 py-2 text-gray-400">${parseFloat(item.already_delivered || 0).toFixed(2)}</td>
            <td class="px-3 py-2">
                <input type="number" 
                       step="0.01" 
                       min="0"
                       value="${parseFloat(item.quantity || 0).toFixed(2)}"
                       data-index="${index}"
                       data-item-code="${item.item_code}"
                       data-unit-price="${item.unit_price || 0}"
                       data-original-qty="${item.original_quantity || 0}"
                       data-already-delivered="${item.already_delivered || 0}"
                       onchange="updateItemTotal(this)"
                       class="edit-item-qty w-24 px-2 py-1 bg-gray-700 border border-green-600 rounded text-white focus:ring-2 focus:ring-green-500">
            </td>
            <td class="px-3 py-2 remaining-qty-${index}">${remaining.toFixed(2)}</td>
            <td class="px-3 py-2 text-right item-total-${index}">₱${(parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)).toFixed(2)}</td>
        `;
        
        tbody.appendChild(row);
    });
}

function updateItemTotal(input) {
    const index = input.dataset.index;
    const unitPrice = parseFloat(input.dataset.unitPrice || 0);
    const quantity = parseFloat(input.value || 0);
    const originalQty = parseFloat(input.dataset.originalQty || 0);
    const alreadyDelivered = parseFloat(input.dataset.alreadyDelivered || 0);
    
    // ✅ UPDATED: Calculate remaining (can be negative if over-delivery)
    const remaining = originalQty - alreadyDelivered - quantity;
    
    const remainingCell = document.querySelector(`.remaining-qty-${index}`);
    if (remainingCell) {
        remainingCell.textContent = remaining.toFixed(2);
        
        // ✅ Optional: Show warning for over-delivery (but don't block it)
        if (remaining < 0) {
            remainingCell.classList.add('text-orange-400');
            remainingCell.title = 'Over-delivery: Exceeds SO quantity';
        } else {
            remainingCell.classList.remove('text-orange-400');
            remainingCell.title = '';
        }
    }
    
    const total = quantity * unitPrice;
    const totalCell = document.querySelector(`.item-total-${index}`);
    if (totalCell) {
        totalCell.textContent = '₱' + total.toFixed(2);
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editForm').reset();
    editDeliveryData = null;
    currentPOImageUrl = null;
}

// ========================================
// PO IMAGE FULLSCREEN FUNCTIONS
// ========================================

function openPOImageFullscreen() {
    if (!currentPOImageUrl) {
        alert('No PO image available to view.');
        return;
    }
    
    const fullscreenImg = document.getElementById('poImageFullscreen');
    const fullscreenModal = document.getElementById('poImageFullscreenModal');
    
    fullscreenImg.src = currentPOImageUrl;
    fullscreenModal.classList.remove('hidden');
    
    console.log('🔍 Opening PO image in fullscreen');
}

function closePOImageFullscreen() {
    const fullscreenModal = document.getElementById('poImageFullscreenModal');
    fullscreenModal.classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const fullscreenModal = document.getElementById('poImageFullscreenModal');
        if (!fullscreenModal.classList.contains('hidden')) {
            closePOImageFullscreen();
        }
    }
});

// ========================================
// EDIT FORM SUBMIT
// ========================================

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const id = document.getElementById('editDeliveryId').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        return;
    }
    
    const items = [];
    document.querySelectorAll('.edit-item-qty').forEach(input => {
        const index = input.dataset.index;
        const originalItem = editDeliveryData.items[index];
        
        items.push({
            item_code: input.dataset.itemCode,
            item_description: originalItem.item_description,
            quantity: parseFloat(input.value || 0),
            original_quantity: parseFloat(input.dataset.originalQty || 0),
            already_delivered: parseFloat(input.dataset.alreadyDelivered || 0),
            uom: originalItem.uom,
            unit_price: parseFloat(input.dataset.unitPrice || 0),
            total_amount: parseFloat(input.value || 0) * parseFloat(input.dataset.unitPrice || 0),
            notes: originalItem.notes
        });
    });
    
    const updateData = {
        dr_no: document.getElementById('edit_dr_no').value,
        sales_invoice_no: document.getElementById('edit_si_no').value || null,
        po_number: document.getElementById('edit_po_number').value || null,
        plate_no: document.getElementById('edit_plate_no').value || null,
        items: items
    };
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Saving...';
    
    fetch(`/deliveries/${id}/quick-update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(updateData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeEditModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to update delivery');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// ========================================
// UTILITY FUNCTIONS FOR PRINT/EXPORT
// ========================================

function printList() {
    const dateFrom = document.querySelector('input[name="date_from"]')?.value || '';
    const dateTo = document.querySelector('input[name="date_to"]')?.value || '';
    const search = document.querySelector('input[name="search"]')?.value || '';
    const status = document.querySelector('select[name="status"]')?.value || ''; // ✅ NEW
    const approvalStatus = document.querySelector('select[name="approval_status"]')?.value || ''; // ✅ NEW
    
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (search) params.append('search', search);
    if (status) params.append('status', status); // ✅ NEW
    if (approvalStatus) params.append('approval_status', approvalStatus); // ✅ NEW
    
    window.open(`/deliveries/print-list?${params.toString()}`, '_blank');
}

function exportExcel() {
    const dateFrom = document.querySelector('input[name="date_from"]')?.value || '';
    const dateTo = document.querySelector('input[name="date_to"]')?.value || '';
    const search = document.querySelector('input[name="search"]')?.value || '';
    const status = document.querySelector('select[name="status"]')?.value || ''; // ✅ NEW
    const approvalStatus = document.querySelector('select[name="approval_status"]')?.value || ''; // ✅ NEW
    
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (search) params.append('search', search);
    if (status) params.append('status', status); // ✅ NEW
    if (approvalStatus) params.append('approval_status', approvalStatus); // ✅ NEW
    
    window.location.href = `/deliveries/export-excel?${params.toString()}`;
}
</script>
@endsection