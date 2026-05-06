@extends('layouts.app')

@section('title', 'Statement of Accounts')

@section('content')
<div class="p-6 bg-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">Statement of Accounts</h1>
                <p class="text-sm text-gray-300 mt-1">Customers with outstanding balances</p>
            </div>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Search -->
        <div class="bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
            <form method="GET" action="{{ route('soa.index') }}" class="flex items-center gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by customer name or code..."
                        class="w-full bg-gray-900 border border-gray-700 rounded-md px-4 py-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-md font-medium transition">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                @if($search)
                <a href="{{ route('soa.index') }}" class="bg-gray-600 hover:bg-gray-600 text-gray-200 px-4 py-2.5 rounded-md font-medium transition">Clear</a>
                @endif
            </form>
        </div>

        <!-- Customer List -->
        <div class="bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-red-800 text-white">
                            <th class="px-4 py-3 text-left font-semibold">Customer Name</th>
                            <th class="px-4 py-3 text-left font-semibold">Branch</th>
                            <th class="px-4 py-3 text-left font-semibold">Sales Executive</th>
                            <th class="px-4 py-3 text-center font-semibold">Terms</th>
                            <th class="px-4 py-3 text-center font-semibold">Invoices</th>
                            <th class="px-4 py-3 text-right font-semibold">Outstanding Balance</th>
                            <th class="px-4 py-3 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-white">{{ $customer->client_name }}</span>
                                @if(!empty($customer->billing_address))
                                <br><span class="text-xs text-gray-400">{{ Str::limit($customer->billing_address, 50) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-300">
                                {{ $customer->branch ?? '—' }}
                                @if(($customer->branch_count ?? 1) > 1)
                                <span class="ml-1 px-1.5 py-0.5 bg-purple-900/40 text-purple-300 rounded text-xs font-semibold">{{ $customer->branch_count }} branches</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-300">{{ $customer->sales_executive ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-300">{{ $customer->terms ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 bg-blue-900/40 text-blue-300 rounded text-xs font-semibold">{{ $customer->invoice_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-red-400">
                                ₱{{ number_format((float)$customer->outstanding_balance, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('soa.show', urlencode($customer->client_name)) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                    <a href="{{ route('soa.export', urlencode($customer->client_name)) }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-medium transition">
                                        <i class="fas fa-file-excel mr-1"></i>Export
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-file-invoice text-4xl mb-2"></i>
                                <p>No customers with outstanding balances found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $customers->appends(['search' => $search])->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
