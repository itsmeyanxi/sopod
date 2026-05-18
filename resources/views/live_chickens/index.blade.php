@extends('layouts.app')

@section('title', 'Goods Receipt PO')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">📦 Goods Receipt PO Records</h1>
            @if(auth()->user()->canManageLiveChicken())
                <a href="{{ route('live_chickens.create') }}"
                   class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded font-semibold">
                    + New Record
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search PO, supplier, items..."
                   class="bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
            <select name="status" class="bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                <option value="">All Statuses</option>
                @foreach(\App\Models\LiveChicken::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-white">Filter</button>
                <a href="{{ route('live_chickens.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 rounded text-white">Reset</a>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3">PO No.</th>
                        <th class="px-3 py-3">Supplier</th>
                        <th class="px-3 py-3">Items</th>
                        <th class="px-3 py-3 text-right">PO Qty</th>
                        <th class="px-3 py-3 text-right">Actual Qty</th>
                        <th class="px-3 py-3 text-right">Amount</th>
                        <th class="px-3 py-3 text-center">Del. Week No.</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($records as $rec)
                        @php
                            $colors = \App\Models\LiveChicken::STATUS_COLORS;
                            $color  = $colors[$rec->status] ?? 'gray';
                        @endphp
                        <tr class="hover:bg-gray-700">
                            <td class="px-3 py-3 whitespace-nowrap">{{ $rec->date->format('Y-m-d') }}</td>
                            <td class="px-3 py-3 font-mono text-purple-300">{{ $rec->po_no ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $rec->supplier }}</td>
                            <td class="px-3 py-3 max-w-xs truncate">{{ $rec->items }}</td>
                            @php $poQty = $rec->getPoQty(); @endphp
                            <td class="px-3 py-3 text-right {{ $poQty > 0 && $rec->actual_qty < $poQty ? 'text-red-400 font-semibold' : 'text-gray-300' }}">
                                {{ $poQty > 0 ? number_format($poQty, 2) : '—' }}
                            </td>
                            <td class="px-3 py-3 text-right font-semibold text-white">{{ number_format($rec->actual_qty, 2) }}</td>
                            <td class="px-3 py-3 text-right">{{ number_format($rec->amount, 2) }}</td>
                            <td class="px-3 py-3 text-center">{{ $rec->delivery_week_no ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($color === 'green') bg-green-700 text-green-100
                                    @elseif($color === 'blue') bg-blue-700 text-blue-100
                                    @elseif($color === 'yellow') bg-yellow-700 text-yellow-100
                                    @else bg-red-700 text-red-100
                                    @endif">
                                    {{ $rec->status }}
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <a href="{{ route('live_chickens.show', $rec->id) }}" class="text-blue-400 hover:underline mr-2">View</a>
                                @if(auth()->user()->canManageLiveChicken())
                                    <a href="{{ route('live_chickens.edit', $rec->id) }}" class="text-yellow-400 hover:underline">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-6 text-center text-gray-400">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $records->links() }}
        </div>

    </div>
</div>
@endsection
