@extends('layouts.app')

@section('title', 'AP Ledger')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">AP Ledger</h1>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('ap_ledger.index') }}" class="flex flex-wrap gap-3 mb-6">
            <div>
                <label class="block text-gray-500 text-xs mb-1">Supplier</label>
                <select name="supplier" class="border border-gray-600 rounded px-3 py-2 text-white text-sm min-w-[200px]">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplierName)
                        <option value="{{ $supplierName }}" {{ request('supplier') === $supplierName ? 'selected' : '' }}>{{ $supplierName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-500 text-xs mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-600 rounded px-3 py-2 text-white text-sm">
            </div>
            <div>
                <label class="block text-gray-500 text-xs mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-600 rounded px-3 py-2 text-white text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('ap_ledger.index') }}" class="bg-gray-200 text-gray-200 px-4 py-2 rounded hover:bg-gray-300 transition text-sm">Clear</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-4 py-3 text-center">Date</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">Type</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Reference</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Supplier</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Description</th>
                        <th class="border border-gray-700 px-4 py-3 text-right">Debit</th>
                        <th class="border border-gray-700 px-4 py-3 text-right">Credit</th>
                        <th class="border border-gray-700 px-4 py-3 text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr class="hover:bg-gray-900">
                        <td class="border border-gray-700 px-4 py-3 text-center text-gray-200">{{ $entry->txn_date->format('M d, Y') }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-center">
                            @if($entry->type === 'Invoice')
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-semibold">Invoice</span>
                            @elseif($entry->type === 'Payment')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Payment</span>
                            @elseif($entry->type === 'Debit Memo')
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-semibold">Debit Memo</span>
                            @elseif($entry->type === 'Void')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Void</span>
                            @else
                                <span class="bg-gray-700 text-gray-200 px-2 py-1 rounded text-xs font-semibold">{{ $entry->type }}</span>
                            @endif
                        </td>
                        <td class="border border-gray-700 px-4 py-3 font-semibold text-blue-600">{{ $entry->reference }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-gray-200">{{ $entry->supplier_name }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-gray-300">{{ $entry->description }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-right {{ $entry->debit > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                            {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}
                        </td>
                        <td class="border border-gray-700 px-4 py-3 text-right {{ $entry->credit > 0 ? 'text-green-600 font-semibold' : 'text-gray-500' }}">
                            {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}
                        </td>
                        <td class="border border-gray-700 px-4 py-3 text-right font-bold text-white">
                            {{ number_format($entry->running_balance, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="border border-gray-700 px-4 py-8 text-center text-gray-500">No ledger entries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $entries->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
