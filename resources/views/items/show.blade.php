@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">Item Details</h1>
            <a href="{{ route('items.index') }}" 
               class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded transition">
                Back to Items
            </a>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-green-600 text-white p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white p-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Approval Status Badge -->
        <div class="mb-6">
            @if($item->approval_status === 'approved')
                <span class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">✓ Approved</span>
            @elseif($item->approval_status === 'pending')
                <span class="bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">⏳ Pending Approval</span>
            @else
                <span class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">✗ Rejected</span>
            @endif
        </div>

        <!-- Item Details Card -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-gray-400 text-sm">Item Code</label>
                    <p class="text-white text-lg font-semibold">{{ $item->item_code }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="text-gray-400 text-sm"> Item Description</label>
                    <p class="text-white">{{ $item->item_description }}</p>
                </div>

                <div>
                    <label class="text-gray-400 text-sm">Brand</label>
                    <p class="text-white">{{ $item->brand ?? 'N/A' }}</p>
                </div>

                <div>
                    <label class="text-gray-400 text-sm">Category</label>
                    <p class="text-white">{{ $item->item_category ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Approval Actions (Only for pending items and authorized users) -->
        @if($item->approval_status === 'pending' && auth()->check() && auth()->user()->canApproveItems())
            <div class="bg-yellow-900/30 border border-yellow-600 rounded-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-yellow-400 mb-4">⚠️ Approval Required</h3>
                <p class="text-gray-300 mb-4">This item is pending approval. Please review and take action:</p>
                
                <div class="flex gap-4">
                    <form action="{{ route('items.approve', $item->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition"
                            onclick="return confirm('Are you sure you want to approve this item?')">
                            ✓ Approve Item
                        </button>
                    </form>

                    <form action="{{ route('items.reject', $item->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition"
                            onclick="return confirm('Are you sure you want to reject this item?')">
                            ✗ Reject Item
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Rejection Info (if rejected) -->
        @if($item->approval_status === 'rejected' && $item->rejection_reason)
            <div class="bg-red-900/30 border border-red-600 rounded-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-red-400 mb-2">Rejection Reason</h3>
                <p class="text-gray-300">{{ $item->rejection_reason }}</p>
            </div>
        @endif

        <!-- Approval Info (if approved) -->
        @if($item->approval_status === 'approved' && $item->approver)
            <div class="bg-gray-800 rounded-xl p-4 text-sm text-gray-400">
                <p>Approved by <span class="text-green-400 font-semibold">{{ $item->approver->name }}</span> 
                   on {{ $item->approved_at->format('M d, Y h:i A') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection