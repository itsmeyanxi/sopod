@extends('layouts.app')

@section('title', 'AP Aging Report')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">AP Aging Report</h1>
            <a href="{{ route('ap_dashboard') }}" class="text-gray-300 hover:text-gray-200 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            @php
                $bucketLabels = [
                    'current' => ['label' => 'Current', 'color' => 'green'],
                    '1_30' => ['label' => '1-30 Days', 'color' => 'yellow'],
                    '31_60' => ['label' => '31-60 Days', 'color' => 'orange'],
                    '61_90' => ['label' => '61-90 Days', 'color' => 'red'],
                    'over_90' => ['label' => '90+ Days', 'color' => 'red'],
                ];
            @endphp
            @foreach($bucketLabels as $key => $info)
            <div class="bg-{{ $info['color'] }}-50 border border-{{ $info['color'] }}-200 rounded-lg p-4 text-center">
                <div class="text-xs font-semibold text-{{ $info['color'] }}-600 uppercase">{{ $info['label'] }}</div>
                <div class="text-lg font-bold text-{{ $info['color'] }}-800 mt-1">{{ number_format($buckets[$key]['total'], 2) }}</div>
                <div class="text-xs text-{{ $info['color'] }}-500">{{ $buckets[$key]['invoices']->count() }} invoice(s)</div>
            </div>
            @endforeach
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-center">
            <span class="text-sm text-blue-600 font-medium">Grand Total:</span>
            <span class="text-xl font-bold text-blue-800 ml-2">{{ number_format($grandTotal, 2) }}</span>
        </div>

        <!-- Detail tables for each bucket -->
        @foreach($bucketLabels as $key => $info)
            @if($buckets[$key]['invoices']->count() > 0)
            <div class="mb-6">
                <h3 class="text-md font-semibold text-gray-200 mb-2">{{ $info['label'] }} ({{ number_format($buckets[$key]['total'], 2) }})</h3>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead class="bg-gray-900 text-gray-300 uppercase text-xs">
                            <tr>
                                <th class="border border-gray-700 px-3 py-2 text-left">APV No.</th>
                                <th class="border border-gray-700 px-3 py-2 text-left">Vendor</th>
                                <th class="border border-gray-700 px-3 py-2 text-center">Due Date</th>
                                <th class="border border-gray-700 px-3 py-2 text-center">Days Overdue</th>
                                <th class="border border-gray-700 px-3 py-2 text-right">Amount</th>
                                <th class="border border-gray-700 px-3 py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($buckets[$key]['invoices'] as $inv)
                            <tr class="hover:bg-gray-900">
                                <td class="border border-gray-700 px-3 py-2 font-semibold text-blue-600">{{ $inv->apv_no }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-gray-200">{{ $inv->vendor_name }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-center text-gray-200">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('M d, Y') : '-' }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-center {{ $inv->days_overdue > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">{{ $inv->days_overdue }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-right font-semibold text-white">{{ number_format($inv->balance, 2) }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $inv->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ ucfirst($inv->approval_stage ?? $inv->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        @endforeach

    </div>
</div>
@endsection
