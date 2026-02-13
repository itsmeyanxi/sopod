@extends('layouts.app')

@section('title', 'Check Vouchers')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CHECK VOUCHERS</h1>
            <a href="{{ route('check_vouchers.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                <i class="fas fa-plus mr-1"></i> Create New Check Voucher
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

        <!-- Search APV Section -->
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-2">Create Check Voucher from Approved APV Invoice</h3>
            <div class="relative">
                <input
                    type="text"
                    id="apvSearchInput"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Search by APV No, Vendor Name, or Reference No..."
                />
                <div id="apvSearchResults" class="hidden absolute z-10 w-full mt-2 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-96 overflow-y-auto"></div>
            </div>
        </div>

        <!-- Check Vouchers Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-gray-900 border border-gray-700">
                <thead>
                    <tr class="bg-gray-700">
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">CV No</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">CV Date</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Check No</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">APV No</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Supplier Name</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Bank</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-right text-gray-300">Check Amount</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-300">Status</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Created By</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        <tr class="hover:bg-gray-700 transition">
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">{{ $voucher->cv_no }}</td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">{{ $voucher->cv_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">
                                @if($voucher->check_no)
                                    <span class="text-purple-400">{{ $voucher->check_no }}</span>
                                @else
                                    <span class="text-gray-500">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">
                                @if($voucher->apv_no)
                                    <span class="text-blue-400">{{ $voucher->apv_no }}</span>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">{{ $voucher->supplier_name }}</td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300 text-xs">{{ $voucher->bank ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b border-gray-700 text-right text-gray-300">
                                ₱ {{ number_format($voucher->check_amount, 2) }}
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-center">
                                @if($voucher->status === 'pending')
                                    <span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded text-xs">Pending</span>
                                @elseif($voucher->status === 'approved')
                                    <span class="px-2 py-1 bg-green-900 text-green-300 rounded text-xs">Approved</span>
                                @elseif($voucher->status === 'rejected')
                                    <span class="px-2 py-1 bg-red-900 text-red-300 rounded text-xs">Rejected</span>
                                @elseif($voucher->status === 'paid')
                                    <span class="px-2 py-1 bg-blue-900 text-blue-300 rounded text-xs">Paid</span>
                                @elseif($voucher->status === 'cancelled')
                                    <span class="px-2 py-1 bg-gray-700 text-gray-400 rounded text-xs">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">
                                {{ $voucher->creator->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('check_vouchers.show', $voucher->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @if($voucher->status === 'pending')
                                        <a href="{{ route('check_vouchers.edit', $voucher->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 text-xs">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                                No check vouchers found. Create your first check voucher using the button above or search for an approved APV.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $vouchers->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('apvSearchInput');
    const searchResults = document.getElementById('apvSearchResults');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const searchTerm = this.value.trim();

        if (searchTerm.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`{{ route('check_vouchers.search_apvs') }}?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(invoices => {
                    if (invoices.length === 0) {
                        searchResults.innerHTML = '<div class="p-4 text-gray-400">No approved APV invoices found</div>';
                        searchResults.classList.remove('hidden');
                        return;
                    }

                    let html = '<div class="divide-y divide-gray-700">';
                    invoices.forEach(invoice => {
                        html += `
                            <a href="{{ route('check_vouchers.create') }}?apv_id=${invoice.id}"
                               class="block p-3 hover:bg-gray-700 transition">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-purple-400">${invoice.apv_no}</div>
                                        <div class="text-sm text-gray-300">${invoice.vendor_name}</div>
                                        <div class="text-xs text-gray-400">${invoice.apv_date}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-green-400">${invoice.currency} ${parseFloat(invoice.grand_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                    searchResults.innerHTML = html;
                    searchResults.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="p-4 text-red-400">Error searching APV invoices</div>';
                    searchResults.classList.remove('hidden');
                });
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });
});
</script>
@endsection
