@extends('layouts.app')

@section('title', 'Cash Forecast')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">Cash Forecast (Next 60 Days)</h1>
            <a href="{{ route('ap_dashboard') }}" class="text-gray-500 hover:text-gray-200 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Forecast Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-5 text-center">
                <div class="text-xs font-semibold text-red-600 uppercase">Next 7 Days</div>
                <div class="text-2xl font-bold text-red-800 mt-1">{{ number_format($totals['7_days'], 2) }}</div>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-5 text-center">
                <div class="text-xs font-semibold text-orange-600 uppercase">Next 30 Days</div>
                <div class="text-2xl font-bold text-orange-800 mt-1">{{ number_format($totals['30_days'], 2) }}</div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 text-center">
                <div class="text-xs font-semibold text-blue-600 uppercase">Next 60 Days</div>
                <div class="text-2xl font-bold text-blue-800 mt-1">{{ number_format($totals['60_days'], 2) }}</div>
            </div>
        </div>

        <!-- Invoice List -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-4 py-3 text-left">APV No.</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Vendor</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">Due Date</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">Days Until Due</th>
                        <th class="border border-gray-700 px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-gray-900">
                        <td class="border border-gray-700 px-4 py-3 font-semibold text-blue-600">{{ $inv->apv_no }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-gray-200">{{ $inv->vendor_name }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-center text-gray-200">{{ $inv->due_date ? $inv->due_date->format('M d, Y') : '-' }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $inv->days_until_due <= 7 ? 'bg-red-100 text-red-700' : ($inv->days_until_due <= 30 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-700 text-gray-200') }}">
                                {{ $inv->days_until_due }} day(s)
                            </span>
                        </td>
                        <td class="border border-gray-700 px-4 py-3 text-right font-semibold text-white">{{ number_format($inv->balance, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="border border-gray-700 px-4 py-8 text-center text-gray-500">No invoices due in the next 60 days.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
