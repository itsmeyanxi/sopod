@extends('layouts.app')

@section('title', 'Accounts Payable Invoices')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">ACCOUNTS PAYABLE INVOICES</h1>
            <a href="{{ route('accounts_payable_invoices.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                <i class="fas fa-plus mr-1"></i> Create New Invoice
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

        <!-- Search RFP Section -->
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-2">Create Invoice from Approved Request for Payment</h3>
            <div class="relative">
                <input
                    type="text"
                    id="rfpSearchInput"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Search by RFP No, Payee, or Company..."
                />
                <div id="rfpSearchResults" class="hidden absolute z-10 w-full mt-2 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-96 overflow-y-auto"></div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-gray-900 border border-gray-700">
                <thead>
                    <tr class="bg-gray-700">
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">APV No</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">APV Date</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">RFP No</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Vendor Name</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Payment Type</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-right text-gray-300">Grand Total</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-300">Status</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Created By</th>
                        <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-700 transition">
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">{{ $invoice->apv_no }}</td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">{{ $invoice->apv_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">
                                @if($invoice->requestForPayment)
                                    <span class="text-purple-400">{{ $invoice->requestForPayment->rfp_no }}</span>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">{{ $invoice->vendor_name }}</td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">
                                @if($invoice->payment_type === 'downpayment')
                                    <span class="px-2 py-1 bg-blue-900 text-blue-300 rounded text-xs">Downpayment</span>
                                @else
                                    <span class="px-2 py-1 bg-green-900 text-green-300 rounded text-xs">Full Payment</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-right text-gray-300">
                                {{ $invoice->currency }} {{ number_format($invoice->grand_total, 2) }}
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-center">
                                @if($invoice->status === 'pending')
                                    <span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded text-xs">Pending</span>
                                @elseif($invoice->status === 'approved')
                                    <span class="px-2 py-1 bg-green-900 text-green-300 rounded text-xs">Approved</span>
                                @elseif($invoice->status === 'rejected')
                                    <span class="px-2 py-1 bg-red-900 text-red-300 rounded text-xs">Rejected</span>
                                @elseif($invoice->status === 'paid')
                                    <span class="px-2 py-1 bg-blue-900 text-blue-300 rounded text-xs">Paid</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-gray-300">
                                {{ $invoice->creator->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border-b border-gray-700 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('accounts_payable_invoices.show', $invoice->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @if($invoice->status === 'pending')
                                        <a href="{{ route('accounts_payable_invoices.edit', $invoice->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 text-xs">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                No invoices found. Create your first invoice using the button above or search for an approved RFP.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('rfpSearchInput');
    const searchResults = document.getElementById('rfpSearchResults');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const searchTerm = this.value.trim();

        if (searchTerm.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`{{ route('accounts_payable_invoices.search_rfps') }}?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(rfps => {
                    if (rfps.length === 0) {
                        searchResults.innerHTML = '<div class="p-4 text-gray-400">No approved RFPs found</div>';
                        searchResults.classList.remove('hidden');
                        return;
                    }

                    let html = '<div class="divide-y divide-gray-700">';
                    rfps.forEach(rfp => {
                        html += `
                            <a href="{{ route('accounts_payable_invoices.create') }}?rfp_id=${rfp.id}"
                               class="block p-3 hover:bg-gray-700 transition">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-purple-400">${rfp.rfp_no}</div>
                                        <div class="text-sm text-gray-300">${rfp.payee}</div>
                                        <div class="text-xs text-gray-400">${rfp.company}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-300">${rfp.date}</div>
                                        <div class="text-sm text-green-400">₱${parseFloat(rfp.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
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
                    searchResults.innerHTML = '<div class="p-4 text-red-400">Error searching RFPs</div>';
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
