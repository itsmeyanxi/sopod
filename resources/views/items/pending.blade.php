@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl text-white font-bold">Pending Items for Approval</h1>
        <a href="{{ route('items.index') }}" 
           class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded transition">
            Back to All Items
        </a>
    </div>

    <!-- Flash Message -->
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

    <!-- Pending Items Table -->
    <div class="bg-gray-800 rounded-xl shadow-md overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Item Code</th>
                    <th class="px-4 py-3 text-left">Item Name</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-left">Brand</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                    <td class="px-4 py-3">{{ $item->item_code }}</td>
                    <td class="px-4 py-3">{{ Str::limit($item->item_description, 50) }}</td>
                    <td class="px-4 py-3">{{ $item->brand ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $item->item_category ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('items.show', $item->id) }}" 
                               class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs">
                                View
                            </a>

                            <form action="{{ route('items.approve', $item->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs"
                                    onclick="return confirm('Approve this item?')">
                                    Approve
                                </button>
                            </form>

                            <button 
                                onclick="openRejectModal({{ $item->id }})"
                                class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs">
                                Reject
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                        No pending items for approval.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Item Count -->
    <div class="mt-4 text-sm text-gray-400 text-left">
        Showing {{ $items->count() }} pending item{{ $items->count() !== 1 ? 's' : '' }}
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h2 class="text-xl font-bold text-white mb-4">Reject Item</h2>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Reason for Rejection</label>
                <textarea 
                    name="rejection_reason" 
                    rows="4" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="Enter reason for rejection..."
                    required></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button 
                    type="button" 
                    onclick="closeRejectModal()"
                    class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded-md transition">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition">
                    Reject Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(itemId) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = `/items/${itemId}/reject`;
    modal.classList.remove('hidden');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    document.getElementById('rejectForm').reset();
}
</script>
@endsection