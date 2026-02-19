@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">PURCHASE ORDERS</h1>
            <div class="flex items-center gap-3">
                @if(auth()->user()->canApprovePurchaseOrders())
                    <button type="button" id="bulkApproveBtn"
                        onclick="submitBulkApprove()"
                        class="hidden bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                        <i class="fas fa-check-double mr-1"></i> Approve Selected (<span id="selectedCount">0</span>)
                    </button>
                @endif
                <a href="{{ route('purchase_orders.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                    <i class="fas fa-plus mr-1"></i> Create New PO
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Bulk Approve Form (hidden) -->
        @if(auth()->user()->canApprovePurchaseOrders())
        <form id="bulkApproveForm" action="{{ route('purchase_orders.bulk_approve') }}" method="POST" class="hidden">
            @csrf
        </form>
        @endif

        <!-- Search PR Section -->
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-search text-purple-400 text-lg"></i>
                <h3 class="font-semibold text-white">Create PO from Approved Purchase Request</h3>
            </div>
            <p class="text-gray-400 text-sm mb-3">Search by PR Number, Requisitioner, or Company to create a new Purchase Order</p>

            <div class="relative">
                <input
                    type="text"
                    id="prSearchInput"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 pr-10"
                    placeholder="Type to search approved PRs..."
                    autocomplete="off">
                <span class="absolute right-4 top-3.5 text-gray-500">
                    <i class="fas fa-search"></i>
                </span>
            </div>

            <!-- Search Results Dropdown -->
            <div id="prSearchResults" class="hidden mt-2 bg-gray-800 border border-gray-700 rounded max-h-80 overflow-y-auto shadow-lg">
                <!-- Results will be populated here by JavaScript -->
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-700">
                <thead class="bg-gray-700 text-gray-300 uppercase text-sm">
                    <tr>
                        @if(auth()->user()->canApprovePurchaseOrders())
                        <th class="border border-gray-700 px-3 py-3 w-10">
                            <input type="checkbox" id="selectAll" class="cursor-pointer" title="Select all pending">
                        </th>
                        @endif
                        <th class="border border-gray-700 px-4 py-3">PO NO</th>
                        <th class="border border-gray-700 px-4 py-3">PR NO</th>
                        <th class="border border-gray-700 px-4 py-3">COMPANY</th>
                        <th class="border border-gray-700 px-4 py-3">SUPPLIER</th>
                        <th class="border border-gray-700 px-4 py-3">ORDER DATE</th>
                        <th class="border border-gray-700 px-4 py-3">STATUS</th>
                        <th class="border border-gray-700 px-4 py-3">CREATED BY</th>
                        <th class="border border-gray-700 px-4 py-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="text-gray-300">
                    @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-gray-700/40">
                            @if(auth()->user()->canApprovePurchaseOrders())
                            <td class="border border-gray-700 px-3 py-3 text-center">
                                @if($po->status === 'pending')
                                    <input type="checkbox" name="ids[]" value="{{ $po->id }}"
                                        class="po-checkbox cursor-pointer" form="bulkApproveForm">
                                @endif
                            </td>
                            @endif
                            <td class="border border-gray-700 px-4 py-3">{{ $po->po_no }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $po->pr_no ?? 'N/A' }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $po->company }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $po->supplier ?? 'N/A' }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <span class="px-3 py-1 rounded text-xs font-semibold
                                    @if($po->status === 'pending') bg-yellow-600 text-white
                                    @elseif($po->status === 'approved') bg-green-600 text-white
                                    @elseif($po->status === 'rejected') bg-red-600 text-white
                                    @else bg-blue-600 text-white
                                    @endif">
                                    {{ ucfirst($po->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-700 px-4 py-3">{{ $po->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('purchase_orders.show', $po->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    <a href="{{ route('purchase_orders.edit', $po->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('purchase_orders.destroy', $po->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this purchase order?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canApprovePurchaseOrders() ? 9 : 8 }}" class="border border-gray-700 px-4 py-8 text-center text-gray-400">
                                No purchase orders found. <a href="{{ route('purchase_orders.create') }}" class="text-purple-400 hover:text-purple-300">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $purchaseOrders->links() }}
        </div>
    </div>
</div>

<script>
const prSearchInput = document.getElementById('prSearchInput');
const prSearchResults = document.getElementById('prSearchResults');
let searchTimeout;

prSearchInput.addEventListener('input', function() {
    const searchTerm = this.value.trim();

    // Clear previous timeout
    clearTimeout(searchTimeout);

    if (searchTerm.length < 2) {
        prSearchResults.classList.add('hidden');
        return;
    }

    // Show loading state
    prSearchResults.innerHTML = '<div class="p-3 text-gray-400 text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>';
    prSearchResults.classList.remove('hidden');

    // Debounce search
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`{{ route('purchase_orders.search_prs') }}?search=${encodeURIComponent(searchTerm)}`);
            const prs = await response.json();

            if (prs.length === 0) {
                prSearchResults.innerHTML = `
                    <div class="p-4 text-center">
                        <div class="text-gray-400 mb-2">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p>No approved PRs found matching "${searchTerm}"</p>
                        </div>
                    </div>
                `;
                return;
            }

            let resultsHTML = '<div class="divide-y divide-gray-700">';
            prs.forEach(pr => {
                const dateFormatted = new Date(pr.date_of_request).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                resultsHTML += `
                    <a href="{{ route('purchase_orders.create') }}?pr_id=${pr.id}"
                       class="block p-4 hover:bg-gray-700 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="font-semibold text-white mb-1">
                                    <i class="fas fa-file-alt mr-2 text-purple-400"></i>${pr.pr_no}
                                </div>
                                <div class="text-sm text-gray-400">${pr.requisitioner} • ${pr.company}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="far fa-calendar mr-1"></i>${dateFormatted}
                                </div>
                            </div>
                            <div class="ml-4">
                                <span class="px-3 py-1 bg-purple-900/30 border border-purple-700 text-purple-300 rounded text-sm">
                                    Create PO <i class="fas fa-arrow-right ml-1"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                `;
            });
            resultsHTML += '</div>';

            prSearchResults.innerHTML = resultsHTML;

        } catch (error) {
            console.error('Search error:', error);
            prSearchResults.innerHTML = '<div class="p-3 text-red-400 text-center"><i class="fas fa-exclamation-triangle mr-2"></i>Error loading results</div>';
        }
    }, 300);
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    if (!prSearchInput.contains(e.target) && !prSearchResults.contains(e.target)) {
        prSearchResults.classList.add('hidden');
    }
});

// Show results when clicking input
prSearchInput.addEventListener('focus', function() {
    if (this.value.trim().length >= 2 && prSearchResults.innerHTML.trim() !== '') {
        prSearchResults.classList.remove('hidden');
    }
});

@if(auth()->user()->canApprovePurchaseOrders())
const selectAll = document.getElementById('selectAll');
const bulkApproveBtn = document.getElementById('bulkApproveBtn');
const selectedCount = document.getElementById('selectedCount');

function updateBulkBtn() {
    const checked = document.querySelectorAll('.po-checkbox:checked').length;
    selectedCount.textContent = checked;
    if (checked > 0) {
        bulkApproveBtn.classList.remove('hidden');
    } else {
        bulkApproveBtn.classList.add('hidden');
    }
}

selectAll.addEventListener('change', function () {
    document.querySelectorAll('.po-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkBtn();
});

document.querySelectorAll('.po-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkBtn);
});

function submitBulkApprove() {
    const count = document.querySelectorAll('.po-checkbox:checked').length;
    if (confirm(`Approve ${count} selected Purchase Order(s)?`)) {
        document.getElementById('bulkApproveForm').submit();
    }
}
@endif
</script>
@endsection
