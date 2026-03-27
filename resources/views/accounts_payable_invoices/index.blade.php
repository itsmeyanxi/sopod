@extends('layouts.app')

@section('title', 'Accounts Payable Invoices')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">ACCOUNTS PAYABLE INVOICES</h1>
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

        <!-- Filters -->
        <div class="mb-4 bg-gray-50 border border-gray-200 rounded p-4">
            <form method="GET" class="flex items-center gap-3 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="bg-white border border-gray-200 rounded px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                       style="width:220px;" placeholder="Search APV No, Vendor...">
                <div class="flex items-center gap-1">
                    <label class="text-xs text-gray-500 font-semibold">From:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="bg-white border border-gray-200 rounded px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="flex items-center gap-1">
                    <label class="text-xs text-gray-500 font-semibold">To:</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="bg-white border border-gray-200 rounded px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <select name="status" class="bg-white border border-gray-200 rounded px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 text-sm font-semibold">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['search','date_from','date_to','status']))
                    <a href="{{ route('accounts_payable_invoices.index') }}" class="text-xs text-gray-500 hover:text-gray-700 px-2">Clear</a>
                @endif
                <a href="{{ route('accounts_payable_invoices.export', request()->query()) }}"
                   class="ml-auto bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm font-semibold">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
            </form>
        </div>

        <!-- Search RFP Section -->
        <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
            <h3 class="font-semibold text-gray-800 mb-2">Create Invoice from Approved Request for Payment</h3>
            <div class="relative">
                <input
                    type="text"
                    id="rfpSearchInput"
                    class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Search by RFP No, Payee, or Company..."
                />
                <div id="rfpSearchResults" class="hidden absolute z-10 w-full mt-2 bg-white border border-gray-200 rounded shadow-lg max-h-96 overflow-y-auto"></div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-gray-50 border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">APV No</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">APV Date</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">RFP No</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Vendor Name</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Payment Type</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-right text-gray-500">Grand Total</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-center text-gray-500">Status</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Created By</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-center text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-100 transition">
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $invoice->apv_no }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $invoice->apv_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">
                                @if($invoice->requestForPayment)
                                    <span class="text-purple-700">{{ $invoice->requestForPayment->rfp_no }}</span>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $invoice->vendor_name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">
                                @if($invoice->payment_type === 'downpayment')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Downpayment</span>
                                @else
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Full Payment</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-right text-gray-500">
                                {{ $invoice->currency }} {{ number_format($invoice->grand_total, 2) }}
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-center">
                                @if($invoice->status === 'pending')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Pending</span>
                                @elseif($invoice->status === 'approved')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Approved</span>
                                @elseif($invoice->status === 'rejected')
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Rejected</span>
                                @elseif($invoice->status === 'paid')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Paid</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">
                                {{ $invoice->creator->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-center">
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
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                No invoices found. Create your first invoice using the button above or search for an approved RFP.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $invoices->withQueryString()->links() }}
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
                        searchResults.innerHTML = '<div class="p-4 text-gray-500">No approved RFPs found</div>';
                        searchResults.classList.remove('hidden');
                        return;
                    }

                    let html = '<div class="divide-y divide-gray-700">';
                    rfps.forEach(rfp => {
                        html += `
                            <a href="{{ route('accounts_payable_invoices.create') }}?rfp_id=${rfp.id}"
                               class="block p-3 hover:bg-gray-100 transition">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-purple-700">${rfp.rfp_no}</div>
                                        <div class="text-sm text-gray-500">${rfp.payee}</div>
                                        <div class="text-xs text-gray-500">${rfp.company}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500">${rfp.date}</div>
                                        <div class="text-sm text-green-700">₱${parseFloat(rfp.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
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
                    searchResults.innerHTML = '<div class="p-4 text-red-700">Error searching RFPs</div>';
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
