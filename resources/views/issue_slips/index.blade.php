@extends('layouts.app')

@section('title', 'Issue Slips')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">ISSUE SLIPS</h1>
            <a href="{{ route('issue_slips.create') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-plus mr-1"></i> New Issue Slip
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-gray-400 text-xs mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm" placeholder="IS#, SO#, Customer...">
            </div>
            <div>
                <label class="block text-gray-400 text-xs mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
            </div>
            <div>
                <label class="block text-gray-400 text-xs mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="{{ route('issue_slips.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 text-sm">Clear</a>
        </form>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-700">
                <thead class="bg-red-700 text-white">
                    <tr>
                        <th class="border border-gray-700 px-3 py-2 text-left">IS NUMBER</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">DATE</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">ORIGIN</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">BRANCH</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">SO NUMBER</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">DESTINATION</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">CREATED BY</th>
                        <th class="border border-gray-700 px-3 py-2 text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issueSlips as $is)
                    <tr class="hover:bg-gray-700">
                        <td class="border border-gray-700 px-3 py-2">
                            <a href="{{ route('issue_slips.show', $is->id) }}" class="text-blue-400 hover:underline font-semibold">{{ $is->issue_slip_number }}</a>
                        </td>
                        <td class="border border-gray-700 px-3 py-2">{{ $is->date->format('M d, Y') }}</td>
                        <td class="border border-gray-700 px-3 py-2">{{ $is->origin ?? '-' }}</td>
                        <td class="border border-gray-700 px-3 py-2">{{ $is->branch ?? optional($is->salesOrder)->branch ?? optional(optional($is->salesOrder)->customer)->branch ?? '-' }}</td>
                        <td class="border border-gray-700 px-3 py-2">{{ $is->sales_order_number }}</td>
                        <td class="border border-gray-700 px-3 py-2">{{ $is->destination ?? $is->customer_name }}</td>
                        <td class="border border-gray-700 px-3 py-2">{{ $is->creator->name ?? '-' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('issue_slips.show', $is->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">View</a>
                                <a href="{{ route('issue_slips.print', $is->id) }}" class="bg-purple-600 text-white px-3 py-1 rounded text-sm hover:bg-purple-700" target="_blank">Print</a>
                                <a href="{{ route('issue_slips.edit', $is->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700">Edit</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="border border-gray-700 px-3 py-6 text-center text-gray-400">No issue slips found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $issueSlips->links() }}
        </div>
    </div>
</div>
@endsection
