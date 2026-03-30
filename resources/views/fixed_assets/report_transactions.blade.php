@extends('layouts.app')
@section('title', 'Asset Transaction Summary')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold">ASSET TRANSACTION SUMMARY</h1>
            <a href="{{ route('fixed_assets.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                <p class="text-xs text-gray-500">Total Assets</p>
                <p class="text-lg font-bold text-blue-700">{{ number_format($summary['total_assets']) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3 border border-green-100">
                <p class="text-xs text-gray-500">Total Cost</p>
                <p class="text-lg font-bold text-green-700">{{ number_format($summary['total_cost'], 2) }}</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-100">
                <p class="text-xs text-gray-500">Accum. Depreciation</p>
                <p class="text-lg font-bold text-yellow-700">{{ number_format($summary['total_accum_dep'], 2) }}</p>
            </div>
            <div class="bg-red-50 rounded-lg p-3 border border-red-100">
                <p class="text-xs text-gray-500">Total Disposals</p>
                <p class="text-lg font-bold text-red-700">{{ number_format($summary['total_disposal'], 2) }}</p>
            </div>
            <div class="bg-purple-50 rounded-lg p-3 border border-purple-100">
                <p class="text-xs text-gray-500">Net Book Value</p>
                <p class="text-lg font-bold text-purple-700">{{ number_format($summary['total_nbv'], 2) }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-6">
            <select name="asset_group" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">All Groups</option>
                @foreach($groups as $g)
                    <option value="{{ $g }}" {{ request('asset_group') == $g ? 'selected' : '' }}>{{ $g }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded px-3 py-2 text-sm" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded px-3 py-2 text-sm" placeholder="To">
            <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('fixed_assets.report_transactions') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-3 py-2 text-left">Asset Code</th>
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-left">Group</th>
                        <th class="px-3 py-2 text-left">Acq. Date</th>
                        <th class="px-3 py-2 text-right">Cost</th>
                        <th class="px-3 py-2 text-right">Accum. Dep.</th>
                        <th class="px-3 py-2 text-right">Disposal</th>
                        <th class="px-3 py-2 text-right">NBV</th>
                        <th class="px-3 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assets as $asset)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-xs">
                                <a href="{{ route('fixed_assets.show', $asset->id) }}" class="text-blue-600 hover:underline">{{ $asset->asset_code ?? '—' }}</a>
                            </td>
                            <td class="px-3 py-2 max-w-[200px] truncate" title="{{ $asset->asset_description }}">{{ \Illuminate\Support\Str::limit($asset->asset_description, 40) }}</td>
                            <td class="px-3 py-2 text-xs">{{ $asset->asset_group }}</td>
                            <td class="px-3 py-2 text-xs">{{ $asset->acquisition_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-3 py-2 text-right font-mono">{{ number_format($asset->cost, 2) }}</td>
                            <td class="px-3 py-2 text-right font-mono">
                                @if($asset->accumulated_depreciation < 0)
                                    ({{ number_format(abs($asset->accumulated_depreciation), 2) }})
                                @else
                                    {{ number_format($asset->accumulated_depreciation, 2) }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-mono">
                                @if($asset->disposal_amount > 0)
                                    {{ number_format($asset->disposal_amount, 2) }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-semibold">{{ number_format($asset->net_book_value, 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($asset->status == 'Disposed')
                                    <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs">Disposed</span>
                                @elseif($asset->status == 'Fully Depreciated')
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">Fully Dep.</span>
                                @else
                                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">No assets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $assets->links() }}</div>
    </div>
</div>
@endsection
