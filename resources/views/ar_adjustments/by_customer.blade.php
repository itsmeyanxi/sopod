@extends('layouts.app')

@section('title', 'AR Adjustments - ' . $customer->customer_name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold">AR Adjustments</h1>
                <p class="text-gray-300 mt-1">
                    <strong>{{ $customer->customer_name }}</strong>
                    <span class="text-gray-300">({{ $customer->customer_code }})</span>
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('ar_adjustments.index') }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded transition flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Back to All
                </a>
                <a href="{{ route('customers.show', $customer->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition flex items-center gap-2">
                    <i class="fas fa-user"></i> Customer Profile
                </a>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-100 rounded-lg p-4 border-l-4 border-blue-500">
                <p class="text-gray-300 text-sm">Total Adjustments</p>
                <p class="text-3xl font-bold text-blue-700">{{ $summary['total_count'] }}</p>
            </div>
            <div class="bg-green-100 rounded-lg p-4 border-l-4 border-green-500">
                <p class="text-gray-300 text-sm">Total Credit (Decrease)</p>
                <p class="text-2xl font-bold text-green-700">₱{{ number_format($summary['credit_total'], 2) }}</p>
            </div>
            <div class="bg-red-100 rounded-lg p-4 border-l-4 border-red-500">
                <p class="text-gray-300 text-sm">Total Debit (Increase)</p>
                <p class="text-2xl font-bold text-red-700">₱{{ number_format($summary['debit_total'], 2) }}</p>
            </div>
            <div class="bg-purple-100 rounded-lg p-4 border-l-4 border-purple-500">
                <p class="text-gray-300 text-sm">Net Impact</p>
                <p class="text-2xl font-bold {{ $summary['net_total'] < 0 ? 'text-red-700' : 'text-green-700' }}">
                    {{ $summary['net_total'] < 0 ? '-' : '+' }}₱{{ number_format(abs($summary['net_total']), 2) }}
                </p>
            </div>
        </div>

        {{-- AR Aging Summary --}}
        @if($arAging->count() > 0)
        <div class="mb-6 bg-blue-50 border border-blue-700 rounded-lg p-4">
            <h3 class="font-semibold text-blue-700 mb-3 flex items-center">
                <i class="fas fa-calendar-alt mr-2"></i> Outstanding AR Invoices
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                @foreach($arAging as $record)
                <div class="bg-gray-700/50 p-3 rounded">
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-semibold text-white">{{ $record->invoice_no ?? 'N/A' }}</span>
                        <span class="text-yellow-700 font-bold">₱{{ number_format($record->net_ar_balance ?? 0, 2) }}</span>
                    </div>
                    <div class="text-gray-300 text-xs">
                        <div>Date: {{ $record->invoice_date?->format('M d, Y') ?? 'N/A' }}</div>
                        <div>Status: <span class="text-blue-700">{{ ucfirst($record->status ?? 'Outstanding') }}</span></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Adjustments Table --}}
        <div>
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                <i class="fas fa-list mr-2"></i> Adjustment History
            </h3>

            @if($adjustments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-700 text-sm">
                    <thead class="bg-gray-900 text-gray-300">
                        <tr>
                            <th class="border border-gray-700 px-4 py-3 text-left">Date</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">Reference</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">Type</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">DR No</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">Invoice No</th>
                            <th class="border border-gray-700 px-4 py-3 text-right">Amount</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">GL Account</th>
                            <th class="border border-gray-700 px-4 py-3 text-left">Remarks</th>
                            <th class="border border-gray-700 px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adjustments as $adj)
                        <tr class="border-b border-gray-700 hover:bg-gray-700/50">
                            <td class="border border-gray-700 px-4 py-3">{{ $adj->transaction_date->format('M d, Y') }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <span class="bg-blue-100 border border-blue-200 px-2 py-1 rounded text-xs font-mono">
                                    {{ $adj->reference_number }}
                                </span>
                            </td>
                            <td class="border border-gray-700 px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-medium
                                    {{ $adj->transaction_type === 'atd' ? 'bg-blue-600' : '' }}
                                    {{ $adj->transaction_type === 'offset' ? 'bg-indigo-600' : '' }}
                                    {{ $adj->transaction_type === 'credit_memo' ? 'bg-green-600' : '' }}
                                    {{ $adj->transaction_type === 'debit_memo' ? 'bg-red-600' : '' }}
                                    {{ $adj->transaction_type === 'adjustment' ? 'bg-yellow-600' : '' }}
                                    {{ $adj->transaction_type === 'write_off' ? 'bg-purple-600' : '' }}
                                ">
                                    {{ $adj->formatted_type }}
                                </span>
                            </td>
                            <td class="border border-gray-700 px-4 py-3">
                                {{ $adj->dr_no ? $adj->dr_no : '—' }}
                            </td>
                            <td class="border border-gray-700 px-4 py-3">
                                {{ $adj->invoice_number ? $adj->invoice_number : '—' }}
                            </td>
                            <td class="border border-gray-700 px-4 py-3 text-right font-semibold {{ $adj->is_decrease ? 'text-red-700' : 'text-green-700' }}">
                                {{ $adj->is_decrease ? '-' : '+' }}₱{{ number_format(abs($adj->amount), 2) }}
                            </td>
                            <td class="border border-gray-700 px-4 py-3 text-xs">{{ $adj->gl_account }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-xs max-w-xs truncate" title="{{ $adj->remarks ?? '' }}">
                                {{ $adj->remarks ?? '—' }}
                            </td>
                            <td class="border border-gray-700 px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('ar_adjustments.show', $adj->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition inline-block">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('ar_adjustments.edit', $adj->id) }}" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-xs transition inline-block">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="bg-gray-700 rounded-lg p-8 text-center">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                <p class="text-gray-300">No adjustments found for this customer.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
