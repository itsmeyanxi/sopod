@extends('layouts.app')
@section('title', 'Depreciation Report')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold">DEPRECIATION REPORT</h1>
            <a href="{{ route('fixed_assets.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-6">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Year</label>
                <select name="year" class="border border-gray-300 rounded px-3 py-2 text-sm">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cost Center</label>
                <select name="cost_center" class="border border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="">All Cost Centers</option>
                    @foreach($costCenters as $cc)
                        <option value="{{ $cc }}" {{ $costCenter == $cc ? 'selected' : '' }}>{{ $cc }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded text-sm">Apply</button>
            </div>
        </form>

        @php
            $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $monthTotals = array_fill(1, 12, 0);
            $grandTotal = 0;
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase">
                        <th class="px-2 py-2 text-left sticky left-0 bg-gray-100 z-10">Asset Code</th>
                        <th class="px-2 py-2 text-left">Description</th>
                        <th class="px-2 py-2 text-left">Group</th>
                        <th class="px-2 py-2 text-left">Cost Center</th>
                        @foreach($months as $i => $m)
                            <th class="px-2 py-2 text-right">{{ $m }}</th>
                        @endforeach
                        <th class="px-2 py-2 text-right font-bold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($depData as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1.5 font-mono sticky left-0 bg-white">{{ $row['asset']->asset_code }}</td>
                            <td class="px-2 py-1.5 max-w-[200px] truncate" title="{{ $row['asset']->asset_description }}">{{ \Illuminate\Support\Str::limit($row['asset']->asset_description, 30) }}</td>
                            <td class="px-2 py-1.5">{{ $row['asset']->asset_group }}</td>
                            <td class="px-2 py-1.5">{{ $row['asset']->cost_center_name ?? '—' }}</td>
                            @for($m = 1; $m <= 12; $m++)
                                <td class="px-2 py-1.5 text-right font-mono">
                                    @if($row['months'][$m] > 0)
                                        {{ number_format($row['months'][$m], 2) }}
                                        @php $monthTotals[$m] += $row['months'][$m]; @endphp
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="px-2 py-1.5 text-right font-mono font-semibold">{{ number_format($row['total'], 2) }}</td>
                            @php $grandTotal += $row['total']; @endphp
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="px-4 py-8 text-center text-gray-400">No depreciation data for {{ $year }}.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($depData) > 0)
                <tfoot>
                    <tr class="bg-gray-50 font-semibold text-xs border-t-2 border-gray-300">
                        <td colspan="4" class="px-2 py-2 sticky left-0 bg-gray-50">TOTALS</td>
                        @for($m = 1; $m <= 12; $m++)
                            <td class="px-2 py-2 text-right font-mono">{{ number_format($monthTotals[$m], 2) }}</td>
                        @endfor
                        <td class="px-2 py-2 text-right font-mono text-blue-700">{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
