@extends('layouts.app')

@section('title', "AR Summary - $customerName")

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">{{ $customerName }}</h1>
                <p class="text-gray-400 text-sm">Customer Code: <span class="text-gray-300 font-mono">{{ $customerCode }}</span></p>
                <p class="text-gray-400 text-sm">Complete AR Summary for All Aging Periods</p>
            </div>
            <a href="{{ route('aging_reports.summary', ['filter_date' => $filterDate, 'include' => $include]) }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium transition flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Summary</span>
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg p-4 shadow-lg">
                <p class="text-blue-100 text-sm mb-1">Total Invoices</p>
                <p class="text-white text-3xl font-bold">{{ $totalInvoices }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg p-4 shadow-lg">
                <p class="text-purple-100 text-sm mb-1">Total Outstanding AR</p>
                <p class="text-white text-3xl font-bold">₱{{ number_format($totalOutstanding, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-lg p-4 shadow-lg">
                <p class="text-indigo-100 text-sm mb-1">Aging Buckets</p>
                <p class="text-white text-3xl font-bold">
                    @php
                        $activeBuckets = collect($bucketSummary)->filter(fn($b) => $b['count'] > 0)->count();
                    @endphp
                    {{ $activeBuckets }}
                </p>
            </div>
        </div>

        <!-- Aging Buckets Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Current (Not Yet Due) -->
            <a href="{{ route('aging_reports.detail', ['customer_code' => $customerCode, 'bucket' => 'current', 'filter_date' => $filterDate, 'include' => $include]) }}"
               class="bg-gradient-to-br from-green-600 to-green-700 rounded-lg p-4 shadow-lg hover:shadow-xl hover:scale-105 transition transform cursor-pointer border-l-4 border-green-400">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-white font-bold text-lg">Current</h3>
                        <p class="text-green-100 text-xs">Not Yet Due</p>
                    </div>
                    <i class="fas fa-check-circle text-green-200 text-2xl"></i>
                </div>
                <p class="text-white text-2xl font-bold">{{ $bucketSummary['current']['count'] }}</p>
                <p class="text-green-100 text-sm mt-2">₱{{ number_format($bucketSummary['current']['total'], 2) }}</p>
            </a>

            <!-- 1-30 Days -->
            <a href="{{ route('aging_reports.detail', ['customer_code' => $customerCode, 'bucket' => '1_30', 'filter_date' => $filterDate, 'include' => $include]) }}"
               class="bg-gradient-to-br from-yellow-600 to-yellow-700 rounded-lg p-4 shadow-lg hover:shadow-xl hover:scale-105 transition transform cursor-pointer border-l-4 border-yellow-400">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-white font-bold text-lg">1-30 Days</h3>
                        <p class="text-yellow-100 text-xs">Overdue</p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-yellow-200 text-2xl"></i>
                </div>
                <p class="text-white text-2xl font-bold">{{ $bucketSummary['1_30']['count'] }}</p>
                <p class="text-yellow-100 text-sm mt-2">₱{{ number_format($bucketSummary['1_30']['total'], 2) }}</p>
            </a>

            <!-- 31-60 Days -->
            <a href="{{ route('aging_reports.detail', ['customer_code' => $customerCode, 'bucket' => '31_60', 'filter_date' => $filterDate, 'include' => $include]) }}"
               class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-lg p-4 shadow-lg hover:shadow-xl hover:scale-105 transition transform cursor-pointer border-l-4 border-orange-400">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-white font-bold text-lg">31-60 Days</h3>
                        <p class="text-orange-100 text-xs">Overdue</p>
                    </div>
                    <i class="fas fa-exclamation text-orange-200 text-2xl"></i>
                </div>
                <p class="text-white text-2xl font-bold">{{ $bucketSummary['31_60']['count'] }}</p>
                <p class="text-orange-100 text-sm mt-2">₱{{ number_format($bucketSummary['31_60']['total'], 2) }}</p>
            </a>

            <!-- 61-90 Days -->
            <a href="{{ route('aging_reports.detail', ['customer_code' => $customerCode, 'bucket' => '61_90', 'filter_date' => $filterDate, 'include' => $include]) }}"
               class="bg-gradient-to-br from-red-600 to-red-700 rounded-lg p-4 shadow-lg hover:shadow-xl hover:scale-105 transition transform cursor-pointer border-l-4 border-red-400">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-white font-bold text-lg">61-90 Days</h3>
                        <p class="text-red-100 text-xs">Overdue</p>
                    </div>
                    <i class="fas fa-fire text-red-200 text-2xl"></i>
                </div>
                <p class="text-white text-2xl font-bold">{{ $bucketSummary['61_90']['count'] }}</p>
                <p class="text-red-100 text-sm mt-2">₱{{ number_format($bucketSummary['61_90']['total'], 2) }}</p>
            </a>

            <!-- 91-120 Days -->
            <a href="{{ route('aging_reports.detail', ['customer_code' => $customerCode, 'bucket' => '91_120', 'filter_date' => $filterDate, 'include' => $include]) }}"
               class="bg-gradient-to-br from-red-700 to-red-800 rounded-lg p-4 shadow-lg hover:shadow-xl hover:scale-105 transition transform cursor-pointer border-l-4 border-red-500">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-white font-bold text-lg">91-120 Days</h3>
                        <p class="text-red-100 text-xs">Overdue</p>
                    </div>
                    <i class="fas fa-exclamation-circle text-red-200 text-2xl"></i>
                </div>
                <p class="text-white text-2xl font-bold">{{ $bucketSummary['91_120']['count'] }}</p>
                <p class="text-red-100 text-sm mt-2">₱{{ number_format($bucketSummary['91_120']['total'], 2) }}</p>
            </a>

            <!-- 120+ Days -->
            <a href="{{ route('aging_reports.detail', ['customer_code' => $customerCode, 'bucket' => 'over_120', 'filter_date' => $filterDate, 'include' => $include]) }}"
               class="bg-gradient-to-br from-red-900 to-red-950 rounded-lg p-4 shadow-lg hover:shadow-xl hover:scale-105 transition transform cursor-pointer border-l-4 border-red-600">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-white font-bold text-lg">120+ Days</h3>
                        <p class="text-red-100 text-xs">Critically Overdue</p>
                    </div>
                    <i class="fas fa-skull-crossbones text-red-200 text-2xl"></i>
                </div>
                <p class="text-white text-2xl font-bold">{{ $bucketSummary['over_120']['count'] }}</p>
                <p class="text-red-100 text-sm mt-2">₱{{ number_format($bucketSummary['over_120']['total'], 2) }}</p>
            </a>
        </div>

        <!-- Legend -->
        <div class="mt-6 bg-gray-700 rounded-lg p-4">
            <p class="text-gray-300 text-sm mb-3 font-semibold">📋 How to Use:</p>
            <ul class="text-gray-400 text-sm space-y-1">
                <li>• <strong>Click any card</strong> to see all invoices in that aging bucket</li>
                <li>• <strong>Invoice count</strong> shows how many invoices in that bucket</li>
                <li>• <strong>Amount</strong> shows total outstanding AR for that bucket</li>
                <li>• <strong>Colors indicate urgency:</strong> Green (safe) → Yellow → Orange → Red (critical)</li>
            </ul>
        </div>
    </div>
</div>
@endsection
