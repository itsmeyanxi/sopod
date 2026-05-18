@extends('layouts.app')
@section('title', 'EWT Detail — '.$vendorName)
@section('content')
<div class="container mx-auto">
<div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-4">
        <div>
            <h1 class="text-2xl font-bold">EWT Detail</h1>
            <p class="text-orange-400 font-semibold mt-1">{{ $vendorName }}</p>
            <p class="text-gray-400 text-xs mt-0.5">{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</p>
        </div>
        <a href="{{ route('accounts_payable_invoices.ewt_register') }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}{{ $status ? '&status='.$status : '' }}"
           class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 text-sm">← Back to EWT Register</a>
    </div>

    <div class="overflow-x-auto mb-6">
        <table class="w-full border-collapse text-sm">
            <thead class="bg-orange-700 text-white uppercase text-xs">
                <tr>
                    <th class="border border-gray-600 px-3 py-2 text-left">APV No</th>
                    <th class="border border-gray-600 px-3 py-2 text-left">Date</th>
                    <th class="border border-gray-600 px-3 py-2 text-left">Status</th>
                    <th class="border border-gray-600 px-3 py-2 text-left">Particulars</th>
                    <th class="border border-gray-600 px-3 py-2 text-center">Tax Code</th>
                    <th class="border border-gray-600 px-3 py-2 text-center">Rate</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">Gross</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">VAT</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">Net of VAT</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">EWT</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">Amount Due</th>
                </tr>
            </thead>
            <tbody class="text-gray-200">
                @forelse($rows as $r)
                <tr class="hover:bg-gray-700/40 border-b border-gray-700">
                    <td class="border border-gray-700 px-3 py-2">
                        <a href="{{ route('accounts_payable_invoices.show', $r['apv_id']) }}" class="text-purple-400 hover:text-purple-300">{{ $r['apv_no'] }}</a>
                    </td>
                    <td class="border border-gray-700 px-3 py-2 text-gray-300 text-xs">{{ \Carbon\Carbon::parse($r['apv_date'])->format('M d, Y') }}</td>
                    <td class="border border-gray-700 px-3 py-2">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $r['status']==='approved' ? 'bg-green-800 text-green-300' : 'bg-yellow-800 text-yellow-300' }}">
                            {{ ucfirst($r['status']) }}
                        </span>
                    </td>
                    <td class="border border-gray-700 px-3 py-2 text-gray-300 text-xs max-w-xs truncate">{{ $r['particulars'] }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-center font-mono text-xs text-yellow-300">{{ $r['tax_code'] }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-center text-xs text-gray-400">{{ $r['rate_pct'] }}%</td>
                    <td class="border border-gray-700 px-3 py-2 text-right">₱{{ number_format($r['gross'],2) }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-yellow-400">₱{{ number_format($r['vat'],2) }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-blue-400">₱{{ number_format($r['net'],2) }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-red-400">(₱{{ number_format($r['ewt'],2) }})</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-green-400 font-semibold">₱{{ number_format($r['amount_due'],2) }}</td>
                </tr>
                @empty
                <tr><td colspan="11" class="text-center text-gray-500 py-6">No records.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-700 font-bold text-white">
                <tr>
                    <td colspan="6" class="border border-gray-600 px-3 py-2">TOTAL</td>
                    <td class="border border-gray-600 px-3 py-2 text-right">₱{{ number_format($totals['gross'],2) }}</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-yellow-300">₱{{ number_format($totals['vat'],2) }}</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-blue-300">₱{{ number_format($totals['net'],2) }}</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-red-300">(₱{{ number_format($totals['ewt'],2) }})</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-green-300">₱{{ number_format($totals['amount_due'],2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary Card -->
    <div class="flex justify-end">
        <div class="bg-gray-900 border border-gray-700 rounded px-4 py-3 text-sm" style="min-width:240px;">
            <div class="flex justify-between gap-6 mb-1"><span class="text-gray-400">Gross:</span><span class="font-bold text-white">₱{{ number_format($totals['gross'],2) }}</span></div>
            <div class="flex justify-between gap-6 mb-1"><span class="text-gray-400">VAT (12%):</span><span class="text-yellow-400">₱{{ number_format($totals['vat'],2) }}</span></div>
            <div class="flex justify-between gap-6 mb-1"><span class="text-gray-400">Net of VAT:</span><span class="text-blue-400">₱{{ number_format($totals['net'],2) }}</span></div>
            <div class="flex justify-between gap-6 mb-1"><span class="text-gray-400">Wtax:</span><span class="text-red-400">(₱{{ number_format($totals['ewt'],2) }})</span></div>
            <div class="flex justify-between gap-6 border-t border-gray-600 pt-2 mt-1"><span class="text-white font-semibold">Amount Due:</span><span class="font-bold text-green-400">₱{{ number_format($totals['amount_due'],2) }}</span></div>
        </div>
    </div>
</div>
</div>
@endsection
