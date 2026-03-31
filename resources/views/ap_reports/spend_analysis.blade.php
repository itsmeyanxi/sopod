@extends('layouts.app')

@section('title', 'Spend Analysis')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">Spend Analysis</h1>
            <a href="{{ route('ap_dashboard') }}" class="text-gray-500 hover:text-gray-200 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-lg p-5 text-white shadow">
                <div class="text-sm font-medium opacity-90">Total Spend</div>
                <div class="text-2xl font-bold mt-1">{{ number_format($totalSpend, 2) }}</div>
                <div class="text-xs opacity-80 mt-1">All approved/pending invoices</div>
            </div>
            <div class="bg-gradient-to-br from-teal-500 to-teal-700 rounded-lg p-5 text-white shadow">
                <div class="text-sm font-medium opacity-90">Avg. Payment Cycle</div>
                <div class="text-2xl font-bold mt-1">{{ $avgDays ? round($avgDays) : 'N/A' }} days</div>
                <div class="text-xs opacity-80 mt-1">From APV to Check Voucher approval</div>
            </div>
        </div>

        <!-- Spend by Supplier -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white mb-3">Spend by Supplier</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="border border-gray-700 px-4 py-3 text-left">#</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">Supplier</th>
                            <th class="border border-gray-700 px-4 py-3 text-right">Amount</th>
                            <th class="border border-gray-700 px-4 py-3 text-right">% of Total</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bySupplier as $index => $item)
                        @php
                            $pct = $totalSpend > 0 ? ($item->amount / $totalSpend) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-gray-900">
                            <td class="border border-gray-700 px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-white font-semibold">{{ $item->vendor_name }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-right font-semibold text-white">{{ number_format($item->amount, 2) }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-right text-gray-300">{{ number_format($pct, 1) }}%</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="border border-gray-700 px-4 py-8 text-center text-gray-500">No spend data yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($bySupplier->count() > 0)
                    <tfoot>
                        <tr class="bg-gray-900 font-bold">
                            <td colspan="2" class="border border-gray-700 px-4 py-3 text-white">Total</td>
                            <td class="border border-gray-700 px-4 py-3 text-right text-white">{{ number_format($totalSpend, 2) }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-right text-white">100%</td>
                            <td class="border border-gray-700 px-4 py-3"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
