@extends('layouts.app')
@section('title', 'Lapsing Schedule Summary')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-white">LAPSING SCHEDULE</h1>
                <p class="text-sm text-gray-300 mt-1">MEATPLUS TRADING - Fixed Asset Summary</p>
            </div>
            <a href="{{ route('fixed_assets.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                <i class="fas fa-list mr-1"></i> Asset List
            </a>
        </div>

        <!-- COST Section -->
        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-200 mb-3 border-b border-gray-600 pb-2">Cost</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-700 text-gray-300 text-xs uppercase">
                            <th class="border border-gray-700 px-3 py-2 text-left w-48">Asset Group</th>
                            <th class="border border-gray-700 px-3 py-2 text-center">Assets</th>
                            <th class="border border-gray-700 px-3 py-2 text-right">Cost</th>
                            <th class="border border-gray-700 px-3 py-2 text-right">Additions</th>
                            <th class="border border-gray-700 px-3 py-2 text-right">Disposals</th>
                            <th class="border border-gray-700 px-3 py-2 text-right">Accum. Dep.</th>
                            <th class="border border-gray-700 px-3 py-2 text-right">Monthly Dep.</th>
                            <th class="border border-gray-700 px-3 py-2 text-right bg-green-50">Net Book Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groups as $group)
                        @php $data = $summaryData[$group]; @endphp
                        <tr class="hover:bg-gray-900 border-b border-gray-100 {{ $data['asset_count'] == 0 ? 'text-gray-300' : '' }}">
                            <td class="border border-gray-700 px-3 py-2 font-semibold text-gray-200">
                                <a href="{{ route('fixed_assets.index', ['asset_group' => $group]) }}" class="hover:text-blue-600 hover:underline">{{ $group }}</a>
                            </td>
                            <td class="border border-gray-700 px-3 py-2 text-center font-semibold">{{ number_format($data['asset_count']) }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($data['cost'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right {{ $data['additions'] > 0 ? 'text-green-700 font-semibold' : '' }}">{{ number_format($data['additions'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right {{ $data['disposals'] > 0 ? 'text-red-700 font-semibold' : '' }}">{{ number_format($data['disposals'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right text-orange-700">{{ number_format($data['accumulated_depreciation'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($data['monthly_depreciation'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right font-bold bg-green-50 {{ $data['net_book_value'] > 0 ? 'text-green-700' : 'text-gray-400' }}">{{ number_format($data['net_book_value'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-800 text-white font-bold">
                            <td class="border border-gray-700 px-3 py-3">TOTAL</td>
                            <td class="border border-gray-700 px-3 py-3 text-center">{{ number_format($totals['asset_count']) }}</td>
                            <td class="border border-gray-700 px-3 py-3 text-right">{{ number_format($totals['cost'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-3 text-right">{{ number_format($totals['additions'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-3 text-right">{{ number_format($totals['disposals'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-3 text-right">{{ number_format($totals['accumulated_depreciation'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-3 text-right">{{ number_format($totals['monthly_depreciation'], 2) }}</td>
                            <td class="border border-gray-700 px-3 py-3 text-right bg-green-900">{{ number_format($totals['net_book_value'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Visual Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Cost Breakdown by Group -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-200 mb-3">Cost by Asset Group</h3>
                @foreach($groups as $group)
                    @php
                        $data = $summaryData[$group];
                        $pct = $totals['cost'] > 0 ? ($data['cost'] / $totals['cost']) * 100 : 0;
                    @endphp
                    @if($data['cost'] > 0)
                    <div class="mb-2">
                        <div class="flex justify-between text-xs text-gray-300 mb-0.5">
                            <span>{{ Str::limit($group, 25) }}</span>
                            <span>{{ number_format($pct, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-600 rounded h-2">
                            <div class="bg-blue-600 h-2 rounded" style="width: {{ min($pct, 100) }}%"></div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            <!-- Key Metrics -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-200 mb-3">Key Metrics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-300">Total Assets</span>
                        <span class="font-bold text-white">{{ number_format($totals['asset_count']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-300">Total Cost</span>
                        <span class="font-bold text-white">{{ number_format($totals['cost'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-300">Total NBV</span>
                        <span class="font-bold text-green-700">{{ number_format($totals['net_book_value'], 2) }}</span>
                    </div>
                    @php
                        $depRatio = $totals['cost'] > 0 ? ($totals['accumulated_depreciation'] / $totals['cost']) * 100 : 0;
                    @endphp
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-300">Depreciation Ratio</span>
                        <span class="font-bold text-orange-700">{{ number_format($depRatio, 1) }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-300">Monthly Dep. Expense</span>
                        <span class="font-bold text-red-700">{{ number_format($totals['monthly_depreciation'], 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Status Breakdown -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-200 mb-3">Asset Status</h3>
                @php
                    $activeCount = \App\Models\FixedAsset::active()->count();
                    $disposedCount = \App\Models\FixedAsset::disposed()->count();
                    $fullyDepCount = \App\Models\FixedAsset::where('remaining_life_months', 0)->whereNull('disposal_date')->count();
                    $total = max(1, $activeCount + $disposedCount + $fullyDepCount);
                @endphp
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-green-700 font-semibold">Active</span>
                            <span>{{ $activeCount }} ({{ number_format(($activeCount / $total) * 100, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-600 rounded h-3">
                            <div class="bg-green-500 h-3 rounded" style="width: {{ ($activeCount / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-yellow-700 font-semibold">Fully Depreciated</span>
                            <span>{{ $fullyDepCount }} ({{ number_format(($fullyDepCount / $total) * 100, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-600 rounded h-3">
                            <div class="bg-yellow-500 h-3 rounded" style="width: {{ ($fullyDepCount / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-red-700 font-semibold">Disposed</span>
                            <span>{{ $disposedCount }} ({{ number_format(($disposedCount / $total) * 100, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-600 rounded h-3">
                            <div class="bg-red-500 h-3 rounded" style="width: {{ ($disposedCount / $total) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
