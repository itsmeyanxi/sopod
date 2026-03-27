@extends('layouts.app')

@section('title', 'Request for Payments')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">REQUEST FOR PAYMENTS</h1>
            <a href="{{ route('request_for_payments.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                <i class="fas fa-plus mr-1"></i> Create New RFP
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Search PO Section -->
        <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
            <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-search text-purple-700 text-lg"></i>
                <h3 class="font-semibold text-gray-800">Create RFP from Approved Purchase Order</h3>
            </div>
            <p class="text-gray-500 text-sm mb-3">Search by PO Number, Supplier, or Company to create a new Request for Payment</p>

            <div class="relative">
                <input
                    type="text"
                    id="poSearchInput"
                    class="w-full bg-white border border-gray-200 rounded px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500 pr-10"
                    placeholder="Type to search approved POs..."
                    autocomplete="off">
                <span class="absolute right-4 top-3.5 text-gray-500">
                    <i class="fas fa-search"></i>
                </span>
            </div>

            <!-- Search Results Dropdown -->
            <div id="poSearchResults" class="hidden mt-2 bg-white border border-gray-200 rounded max-h-80 overflow-y-auto shadow-lg">
                <!-- Results will be populated here by JavaScript -->
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-200">
                <thead class="bg-gray-100 text-gray-500 uppercase text-sm">
                    <tr>
                        <th class="border border-gray-200 px-4 py-3">RFP NO</th>
                        <th class="border border-gray-200 px-4 py-3">PO NO</th>
                        <th class="border border-gray-200 px-4 py-3">COMPANY</th>
                        <th class="border border-gray-200 px-4 py-3">PAYEE</th>
                        <th class="border border-gray-200 px-4 py-3">AMOUNT</th>
                        <th class="border border-gray-200 px-4 py-3">DATE</th>
                        <th class="border border-gray-200 px-4 py-3">STATUS</th>
                        <th class="border border-gray-200 px-4 py-3">CREATED BY</th>
                        <th class="border border-gray-200 px-4 py-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="text-gray-500">
                    @forelse($rfps as $rfp)
                        <tr class="hover:bg-gray-100/40">
                            <td class="border border-gray-200 px-4 py-3">{{ $rfp->rfp_no }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $rfp->purchaseOrder->po_no ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $rfp->company }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $rfp->payee }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-right">₱{{ number_format($rfp->amount, 2) }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $rfp->date->format('M d, Y') }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <span class="px-3 py-1 rounded text-xs font-semibold
                                    @if($rfp->status === 'pending') bg-yellow-600 text-white
                                    @elseif($rfp->status === 'approved') bg-green-600 text-white
                                    @elseif($rfp->status === 'rejected') bg-red-600 text-white
                                    @else bg-blue-600 text-white
                                    @endif">
                                    {{ ucfirst($rfp->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-3">{{ $rfp->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('request_for_payments.show', $rfp->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    <a href="{{ route('request_for_payments.edit', $rfp->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('request_for_payments.destroy', $rfp->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this RFP?');">
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
                            <td colspan="9" class="border border-gray-200 px-4 py-8 text-center text-gray-500">
                                No request for payments found. <a href="{{ route('request_for_payments.create') }}" class="text-purple-700 hover:text-purple-700">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $rfps->links() }}
        </div>
    </div>
</div>

<script>
const poSearchInput = document.getElementById('poSearchInput');
const poSearchResults = document.getElementById('poSearchResults');
let searchTimeout;

poSearchInput.addEventListener('input', function() {
    const searchTerm = this.value.trim();

    clearTimeout(searchTimeout);

    if (searchTerm.length < 2) {
        poSearchResults.classList.add('hidden');
        return;
    }

    poSearchResults.innerHTML = '<div class="p-3 text-gray-500 text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>';
    poSearchResults.classList.remove('hidden');

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`{{ route('request_for_payments.search_pos') }}?search=${encodeURIComponent(searchTerm)}`);
            const pos = await response.json();

            if (pos.length === 0) {
                poSearchResults.innerHTML = `
                    <div class="p-4 text-center">
                        <div class="text-gray-500 mb-2">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p>No approved POs found matching "${searchTerm}"</p>
                        </div>
                    </div>
                `;
                return;
            }

            let resultsHTML = '<div class="divide-y divide-gray-700">';
            pos.forEach(po => {
                const dateFormatted = new Date(po.order_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                resultsHTML += `
                    <a href="{{ route('request_for_payments.create') }}?po_id=${po.id}"
                       class="block p-4 hover:bg-gray-100 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-800 mb-1">
                                    <i class="fas fa-file-invoice mr-2 text-purple-700"></i>${po.po_no}
                                </div>
                                <div class="text-sm text-gray-500">${po.supplier || 'N/A'} • ${po.company}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="far fa-calendar mr-1"></i>${dateFormatted}
                                </div>
                            </div>
                            <div class="ml-4">
                                <span class="px-3 py-1 bg-purple-100 border border-purple-700 text-purple-700 rounded text-sm">
                                    Create RFP <i class="fas fa-arrow-right ml-1"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                `;
            });
            resultsHTML += '</div>';

            poSearchResults.innerHTML = resultsHTML;

        } catch (error) {
            console.error('Search error:', error);
            poSearchResults.innerHTML = '<div class="p-3 text-red-700 text-center"><i class="fas fa-exclamation-triangle mr-2"></i>Error loading results</div>';
        }
    }, 300);
});

document.addEventListener('click', function(e) {
    if (!poSearchInput.contains(e.target) && !poSearchResults.contains(e.target)) {
        poSearchResults.classList.add('hidden');
    }
});

poSearchInput.addEventListener('focus', function() {
    if (this.value.trim().length >= 2 && poSearchResults.innerHTML.trim() !== '') {
        poSearchResults.classList.remove('hidden');
    }
});
</script>
@endsection
