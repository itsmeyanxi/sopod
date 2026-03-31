@extends('layouts.app')
@section('title', 'Assets by Cost Center')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">ASSETS BY COST CENTER</h1>
            <a href="{{ route('fixed_assets.index') }}" class="bg-gray-200 text-gray-200 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        {{-- Summary Table --}}
        <div class="overflow-x-auto mb-8">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-700 text-gray-300 uppercase text-xs">
                        <th class="px-4 py-2 text-left">Cost Center</th>
                        <th class="px-4 py-2 text-right">Assets</th>
                        <th class="px-4 py-2 text-right">Total Cost</th>
                        <th class="px-4 py-2 text-right">Accum. Dep.</th>
                        <th class="px-4 py-2 text-right">Net Book Value</th>
                        <th class="px-4 py-2 text-right">Monthly Dep.</th>
                        <th class="px-4 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @php $totalCost = 0; $totalDep = 0; $totalNbv = 0; $totalMonthly = 0; $totalCount = 0; @endphp
                    @forelse($costCenters as $cc)
                        @php
                            $totalCost    += $cc->total_cost;
                            $totalDep     += $cc->total_accum_dep;
                            $totalNbv     += $cc->total_nbv;
                            $totalMonthly += $cc->total_monthly_dep;
                            $totalCount   += $cc->asset_count;
                        @endphp
                        <tr class="hover:bg-gray-900 {{ $selectedCenter == $cc->cost_center_name ? 'bg-blue-50' : '' }}">
                            <td class="px-4 py-2 font-medium">{{ $cc->cost_center_name ?: '(Unassigned)' }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($cc->asset_count) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($cc->total_cost, 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($cc->total_accum_dep, 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono font-semibold">{{ number_format($cc->total_nbv, 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($cc->total_monthly_dep, 2) }}</td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('fixed_assets.report_cost_center', ['cost_center' => $cc->cost_center_name]) }}"
                                   class="text-blue-600 hover:underline text-xs">View Assets</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No cost center data.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($costCenters->count() > 0)
                <tfoot>
                    <tr class="bg-gray-900 font-semibold border-t-2 border-gray-600 text-sm">
                        <td class="px-4 py-2">TOTAL</td>
                        <td class="px-4 py-2 text-right font-mono">{{ number_format($totalCount) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ number_format($totalCost, 2) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ number_format($totalDep, 2) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-blue-700">{{ number_format($totalNbv, 2) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ number_format($totalMonthly, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Drill-down detail --}}
        @if($selectedCenter && $assets)
            <div class="border-t-2 border-blue-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-200 mb-4">
                    Assets in: <span class="text-blue-700">{{ $selectedCenter ?: '(Unassigned)' }}</span>
                    <span class="text-sm text-gray-500 font-normal ml-2">({{ $assets->count() }} assets)</span>
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-700 text-gray-300 uppercase text-xs">
                                <th class="px-3 py-2 text-left">Asset Code</th>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-left">Group</th>
                                <th class="px-3 py-2 text-right">Cost</th>
                                <th class="px-3 py-2 text-right">Accum. Dep.</th>
                                <th class="px-3 py-2 text-right">NBV</th>
                                <th class="px-3 py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($assets as $asset)
                                <tr class="hover:bg-gray-900">
                                    <td class="px-3 py-2 font-mono text-xs">
                                        <a href="{{ route('fixed_assets.show', $asset->id) }}" class="text-blue-600 hover:underline">{{ $asset->asset_code ?? '—' }}</a>
                                    </td>
                                    <td class="px-3 py-2" title="{{ $asset->asset_description }}">{{ \Illuminate\Support\Str::limit($asset->asset_description, 40) }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $asset->asset_group }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ number_format($asset->cost, 2) }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ number_format($asset->accumulated_depreciation, 2) }}</td>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
