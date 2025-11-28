@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl text-white font-bold">Items List</h1>
        
        @if(auth()->user()->canApproveItems())
            <a href="{{ route('items.pending') }}" 
               class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded transition">
                View Pending Approvals
            </a>
        @endif
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

    <!-- Search + Add Button -->
    <div class="flex items-center justify-between gap-4 mb-4">
        <input id="itemSearchInput" 
            type="text" 
            placeholder="Search item code / name / brand / category"
            class="w-80 bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:ring focus:ring-purple-500">

        @if(auth()->user()->canManageItems())
            <a href="{{ route('items.create') }}" 
            class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition">
                Add New Item
            </a>
        @endif
    </div>

    <!-- Items Table -->
    <div class="bg-gray-800 rounded-xl shadow-md overflow-x-auto">
        <table id="itemsTable" class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Approval Status</th>
                    <th class="px-4 py-3 text-left">Visibility Status</th>
                    <th class="px-4 py-3 text-left">Item Code</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-left">Brand</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-gray-700 hover:bg-gray-700 transition item-row" data-status="{{ $item->approval_status }}">
                    <td class="px-4 py-3">
                        @if($item->approval_status === 'approved')
                            <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">Approved</span>
                        @elseif($item->approval_status === 'pending')
                            <span class="bg-yellow-600 text-white px-2 py-1 rounded text-xs">Pending</span>
                        @else
                            <span class="bg-red-600 text-white px-2 py-1 rounded text-xs">Rejected</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-medium {{ $item->is_enabled ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
                            {{ $item->is_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $item->item_code }}</td>
                    <td class="px-4 py-3">{{ Str::limit($item->item_description, 50) }}</td>
                    <td class="px-4 py-3">{{ $item->brand ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $item->item_category ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center items-center gap-2 flex-wrap">
                            {{-- View --}}
                            <a href="{{ route('items.show', $item->id) }}" 
                               class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs">
                                View
                            </a>

                            {{-- Approval Actions (for users who can approve) --}}
                            @if(auth()->user()->canApproveItems() && $item->approval_status === 'pending')
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
                            @endif

                            {{-- Edit (only for Admin & IT) --}}
                            @if(auth()->user()->canEditItems())
                                <a href="{{ route('items.edit', $item->id) }}" 
                                   class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded text-xs">
                                    Edit
                                </a>
                            @endif

                            {{-- Enable/Disable Toggle (only for Admin & IT) --}}
                            @if(auth()->user()->canEditItems())
                                <form action="{{ route('items.toggle', $item->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="text-white text-xs font-medium px-3 py-1 rounded transition
                                               {{ $item->is_enabled 
                                                  ? 'bg-gray-600 hover:bg-gray-700' 
                                                  : 'bg-green-600 hover:bg-green-700' }}">
                                        {{ $item->is_enabled ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            @endif

                            {{-- Delete (only for Admin, IT, and Accounting_Approver) --}}
                            @if(auth()->user()->canDeleteItems())
                                <form action="{{ route('items.destroy', $item->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs"
                                        onclick="return confirm('Are you sure you want to delete this item?')">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                        No items found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Item Count at the Bottom -->
    <div class="mt-4 text-sm text-gray-400 text-left" id="itemCount"></div>
</div>

<!-- Individual Reject Modal -->
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

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('itemSearchInput');
    const rows = document.querySelectorAll('#itemsTable tbody tr.item-row');
    const countDisplay = document.getElementById('itemCount');

    function updateVisibleCount() {
        const visible = Array.from(rows).filter(r => r.style.display !== 'none');
        const pending = visible.filter(r => r.dataset.status === 'pending').length;
        countDisplay.innerHTML = `Showing ${visible.length} item${visible.length !== 1 ? 's' : ''} ${pending > 0 ? `<span class="text-yellow-400">(${pending} pending)</span>` : ''}`;
    }

    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        rows.forEach(row => {
            const txt = row.innerText.toLowerCase();
            row.style.display = txt.includes(q) ? '' : 'none';
        });
        updateVisibleCount();
    });

    updateVisibleCount();
});

// Individual reject modal
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