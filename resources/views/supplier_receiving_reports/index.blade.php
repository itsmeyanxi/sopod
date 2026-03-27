@extends('layouts.app')

@section('title', 'Supply Receiving Reports')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">SUPPLY RECEIVING REPORTS</h1>
            <div class="flex gap-2">
                <a href="{{ route('supplier_receiving_reports.exportExcel', request()->query()) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-file-excel mr-1"></i> Export CSV
                </a>
                <a href="{{ route('supplier_receiving_reports.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                    <i class="fas fa-plus mr-1"></i> Create New SRR
                </a>
            </div>
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
        <form method="GET" action="{{ route('supplier_receiving_reports.index') }}" class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="SRR code, supply, PO#, CV#..."
                        class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Status</label>
                    <select name="status" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="saved" {{ request('status') == 'saved' ? 'selected' : '' }}>Saved</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition text-sm">
                        <i class="fas fa-search mr-1"></i> Filter
                    </button>
                    <a href="{{ route('supplier_receiving_reports.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-500 transition text-sm">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-200">
                <thead class="bg-gray-100 text-gray-500 uppercase text-sm">
                    <tr>
                        <th class="border border-gray-200 px-4 py-3">SRR CODE</th>
                        <th class="border border-gray-200 px-4 py-3">DATE</th>
                        <th class="border border-gray-200 px-4 py-3">SUPPLY</th>
                        <th class="border border-gray-200 px-4 py-3">PO NO</th>
                        <th class="border border-gray-200 px-4 py-3">TYPE</th>
                        <th class="border border-gray-200 px-4 py-3">TOTAL BOXES</th>
                        <th class="border border-gray-200 px-4 py-3">TOTAL WEIGHT</th>
                        <th class="border border-gray-200 px-4 py-3">STATUS</th>
                        <th class="border border-gray-200 px-4 py-3">CREATED BY</th>
                        <th class="border border-gray-200 px-4 py-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="text-gray-500">
                    @forelse($reports as $report)
                        <tr class="hover:bg-gray-100/40">
                            <td class="border border-gray-200 px-4 py-3 font-semibold">{{ $report->srr_code }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $report->report_date->format('M d, Y') }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $report->supplier_name }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $report->po_no ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs bg-blue-100/40 border border-blue-700 text-blue-700">
                                    {{ ucfirst(str_replace('_', ' ', $report->report_type)) }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-3 text-center">{{ $report->items->sum('no_of_boxes') }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-center">{{ number_format($report->items->sum('net_weight'), 2) }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <span class="px-3 py-1 rounded text-xs font-semibold
                                    @if($report->status === 'draft') bg-gray-200 text-gray-800
                                    @elseif($report->status === 'saved') bg-teal-600 text-white
                                    @elseif($report->status === 'pending') bg-yellow-600 text-white
                                    @elseif($report->status === 'approved') bg-green-600 text-white
                                    @elseif($report->status === 'rejected') bg-red-600 text-white
                                    @endif">
                                    {{ ucfirst($report->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-3">{{ $report->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('supplier_receiving_reports.show', $report->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    @if(!$report->isApproved())
                                        <a href="{{ route('supplier_receiving_reports.edit', $report->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">
                                            Edit
                                        </a>
                                    @endif
                                    @if($report->isDraft())
                                        <form action="{{ route('supplier_receiving_reports.destroy', $report->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this report?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="border border-gray-200 px-4 py-8 text-center text-gray-500">
                                No supply receiving reports found. <a href="{{ route('supplier_receiving_reports.create') }}" class="text-purple-700 hover:text-purple-700">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $reports->links() }}
        </div>
    </div>
</div>
@endsection
