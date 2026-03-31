@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gray-900 rounded-xl shadow-lg p-6 border border-gray-800">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">📦 Receiving Reports (Backload)</h2>
            
            <button onclick="window.location.href='{{ route('receiving-reports.export') }}?{{ http_build_query(request()->all()) }}'" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export to Excel
            </button>
        </div>

        {{-- Filters --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-gray-800/50 p-4 rounded-lg">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2">
            </div>
            
            <div>
                <label class="block text-sm text-gray-500 mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2">
            </div>
            
            <div>
                <label class="block text-sm text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2">
                    <option value="">All Statuses</option>
                    <option value="Received" {{ request('status') == 'Received' ? 'selected' : '' }}>Received</option>
                    <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Processing</option>
                    <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm text-gray-500 mb-1">Search</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="RR No, SO, Customer..." 
                           class="flex-1 bg-gray-900 border border-gray-700 text-gray-200 rounded-md px-3 py-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Search
                    </button>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-500">
                <thead class="bg-gray-800 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">RR Number</th>
                        <th class="px-4 py-3 text-left">Sales Order</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Received Date</th>
                        <th class="px-4 py-3 text-right">Quantity</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($receivingReports as $rr)
                    <tr class="hover:bg-gray-800/50">
                        <td class="px-4 py-3 font-mono text-blue-700">{{ $rr->rr_number }}</td>
                        <td class="px-4 py-3">{{ $rr->sales_order_number }}</td>
                        <td class="px-4 py-3">{{ $rr->customer_name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $rr->received_date ? $rr->received_date->format('M d, Y') : '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($rr->quantity ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right">₱{{ number_format($rr->total_amount ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded text-xs font-semibold
                                {{ $rr->status == 'Received' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $rr->status == 'Processing' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $rr->status == 'Completed' ? 'bg-blue-100 text-blue-700' : '' }}">
                                {{ $rr->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('receiving-reports.show', $rr->id) }}" 
                                   class="text-blue-700 hover:text-blue-700" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('receiving-reports.print', $rr->id) }}" target="_blank"
                                   class="text-green-700 hover:text-green-700" title="Print">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No receiving reports found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection