@extends('layouts.app')

@section('title', 'Disposal Module')

@section('content')
<style>
    .stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:.45rem; padding:1rem; text-align:center; }
    .stat-label { font-size:.75rem; font-weight:700; text-transform:uppercase; color:#6b7280; letter-spacing:.05em; }
    .stat-value { font-size:1.75rem; font-weight:800; color:#111827; margin:.25rem 0; }
    .table-container { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; overflow:hidden; }
    .table-header { padding:1rem; border-bottom:1px solid #e5e7eb; }
    .asset-table { width:100%; border-collapse:collapse; font-size:.875rem; }
    .asset-table thead th { background:#f3f4f6; padding:.75rem; font-weight:600; text-align:left; border-bottom:2px solid #e5e7eb; }
    .asset-table tbody td { padding:.75rem; border-bottom:1px solid #f3f4f6; }
    .asset-table tbody tr:hover { background:#f9fafb; }
    .asset-code { font-weight:600; color:#1f2937; }
    .disposal-badge { display:inline-block; padding:.25rem .75rem; border-radius:9999px; font-size:.75rem; font-weight:600; background:#fee2e2; color:#991b1b; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Disposal Module</h1>
        <p class="text-gray-600 mt-1">Archived assets marked for disposal</p>
    </div>
    <a href="{{ route('fixed_assets.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold">
        ← Back to Fixed Assets
    </a>
</div>

<!-- STATS -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-label">Total Disposed Assets</div>
        <div class="stat-value text-red-600">{{ $summary['total_disposed'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Original Cost</div>
        <div class="stat-value">₱{{ number_format($summary['total_cost'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Disposal Amount</div>
        <div class="stat-value text-green-600">₱{{ number_format($summary['total_disposal'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Accumulated Depreciation</div>
        <div class="stat-value">₱{{ number_format($summary['total_accum_dep'], 2) }}</div>
    </div>
</div>

<!-- FILTERS -->
<div class="table-container mb-6">
    <div class="table-header">
        <form method="GET" class="flex gap-3 flex-wrap items-end">
            <div class="flex-1 min-w-64">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Asset code, description, plate no..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div class="min-w-48">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Asset Group</label>
                <select name="asset_group" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="">All Groups</option>
                    @foreach($assetGroups as $group)
                    <option value="{{ $group }}" {{ request('asset_group') === $group ? 'selected' : '' }}>{{ $group }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-48">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Asset Class</label>
                <select name="asset_class" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="">All Classes</option>
                    @foreach($assetClasses as $class)
                    <option value="{{ $class }}" {{ request('asset_class') === $class ? 'selected' : '' }}>{{ $class }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Disposal From</label>
                <input type="date" name="disposal_date_from" value="{{ request('disposal_date_from') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div class="min-w-40">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Disposal To</label>
                <input type="date" name="disposal_date_to" value="{{ request('disposal_date_to') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            @if(request()->hasAny(['search', 'asset_group', 'asset_class', 'disposal_date_from', 'disposal_date_to']))
            <a href="{{ route('disposals.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
            @endif
        </form>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto">
        <table class="asset-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Asset Code</th>
                    <th>Description</th>
                    <th>Group</th>
                    <th>Class</th>
                    <th>Assigned To</th>
                    <th>Original Cost</th>
                    <th>Accumulated Dep.</th>
                    <th>Net Book Value</th>
                    <th>Disposal Amount</th>
                    <th>Disposal Date</th>
                    <th>Disposed By</th>
                    <th>Reason</th>
                    <th style="width:60px;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($assets as $asset)
                <tr>
                    <td class="text-gray-500">{{ $loop->iteration }}</td>
                    <td><span class="asset-code">{{ $asset->asset_code ?? '—' }}</span></td>
                    <td>{{ $asset->asset_description }}</td>
                    <td>{{ $asset->asset_group }}</td>
                    <td>{{ $asset->asset_class ?? '—' }}</td>
                    <td>{{ $asset->assigned_person ?? '—' }}</td>
                    <td class="text-right font-semibold">₱{{ number_format($asset->cost, 2) }}</td>
                    <td class="text-right">₱{{ number_format($asset->accumulated_depreciation, 2) }}</td>
                    <td class="text-right">₱{{ number_format($asset->net_book_value, 2) }}</td>
                    <td class="text-right font-semibold text-green-700">₱{{ number_format($asset->disposal_amount, 2) }}</td>
                    <td>{{ $asset->disposal_date ? $asset->disposal_date->format('M d, Y') : '—' }}</td>
                    <td>{{ $asset->disposed_by ?? '—' }}</td>
                    <td class="text-xs text-gray-600">{{ Str::limit($asset->disposal_reason, 30) }}</td>
                    <td><a href="{{ route('disposals.show', $asset->id) }}" class="text-blue-600 hover:underline text-sm">View</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center py-8 text-gray-400">
                        <i class="fas fa-inbox text-4xl block mb-2 text-gray-300"></i>
                        No disposed assets found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($assets->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $assets->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
