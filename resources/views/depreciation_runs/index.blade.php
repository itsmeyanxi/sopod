@extends('layouts.app')
@section('title', 'Depreciation Runs')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">DEPRECIATION RUNS</h1>
            <a href="{{ route('depreciation_runs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                <i class="fas fa-plus mr-1"></i> New Depreciation Run
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-3 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                <p class="text-xs text-blue-600 font-semibold">Total</p>
                <p class="text-xl font-bold text-blue-800">{{ number_format($summary['total']) }}</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
                <p class="text-xs text-yellow-600 font-semibold">Draft</p>
                <p class="text-xl font-bold text-yellow-800">{{ number_format($summary['draft']) }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                <p class="text-xs text-green-600 font-semibold">Posted</p>
                <p class="text-xl font-bold text-green-800">{{ number_format($summary['posted']) }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                <p class="text-xs text-red-600 font-semibold">Void</p>
                <p class="text-xl font-bold text-red-800">{{ number_format($summary['void']) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('depreciation_runs.index') }}" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search run number, description..." class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                <input type="month" name="period" value="{{ request('period') }}" class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                <select name="status" class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <option value="Draft"  {{ request('status') === 'Draft'  ? 'selected' : '' }}>Draft</option>
                    <option value="Posted" {{ request('status') === 'Posted' ? 'selected' : '' }}>Posted</option>
                    <option value="Void"   {{ request('status') === 'Void'   ? 'selected' : '' }}>Void</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex-1"><i class="fas fa-filter mr-1"></i> Filter</button>
                    <a href="{{ route('depreciation_runs.index') }}" class="bg-gray-200 text-gray-700 px-3 py-2 rounded text-sm hover:bg-gray-300"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-200 px-3 py-3 text-left">Run Number</th>
                        <th class="border border-gray-200 px-3 py-3 text-center">Period</th>
                        <th class="border border-gray-200 px-3 py-3 text-left">Description</th>
                        <th class="border border-gray-200 px-3 py-3 text-center">Assets</th>
                        <th class="border border-gray-200 px-3 py-3 text-right">Total Amount</th>
                        <th class="border border-gray-200 px-3 py-3 text-left">JV Number</th>
                        <th class="border border-gray-200 px-3 py-3 text-center">Status</th>
                        <th class="border border-gray-200 px-3 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                    <tr class="hover:bg-gray-50 border-b border-gray-100">
                        <td class="border border-gray-200 px-3 py-2">
                            <a href="{{ route('depreciation_runs.show', $run->id) }}" class="text-blue-700 hover:underline font-mono font-bold text-xs">{{ $run->run_number }}</a>
                        </td>
                        <td class="border border-gray-200 px-3 py-2 text-center text-xs font-semibold">{{ $run->period_label }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-gray-700 max-w-xs truncate" title="{{ $run->description }}">{{ Str::limit($run->description, 45) }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-center text-xs">{{ count($run->depreciation_entries ?? []) }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-right font-semibold">{{ number_format($run->total_debit, 2) }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-xs font-mono text-gray-500">{{ $run->jv_number ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-center">
                            @if($run->status === 'Posted')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Posted</span>
                            @elseif($run->status === 'Void')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Void</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-semibold">Draft</span>
                            @endif
                        </td>
                        <td class="border border-gray-200 px-3 py-2 text-center">
                            <a href="{{ route('depreciation_runs.show', $run->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs" title="View"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-calculator text-4xl mb-3 block"></i>
                            No depreciation runs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $runs->appends(request()->query())->links('vendor.pagination.elegant') }}
        </div>
    </div>
</div>
@endsection
