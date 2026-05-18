@extends('layouts.app')
@section('title', 'EWT Register')
@section('content')
<div class="container mx-auto">
<div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-4">
        <h1 class="text-2xl font-bold">EWT Register</h1>
        <a href="{{ route('accounts_payable_invoices.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 text-sm">← Back</a>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-5 items-end">
        <div>
            <label class="block text-gray-400 text-xs mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-gray-400 text-xs mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-gray-400 text-xs mb-1">Status</label>
            <select name="status" class="bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm">
                <option value="">All</option>
                <option value="approved" {{ $status==='approved'?'selected':'' }}>Approved</option>
                <option value="pending"  {{ $status==='pending' ?'selected':'' }}>Pending</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('accounts_payable_invoices.ewt_register') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 text-sm">Clear</a>
        </div>
    </form>

    @if(empty($vendors))
        <div class="text-center text-gray-500 py-10">No EWT records found for selected period. Records appear only when APV items have a tax code (C158, C160, etc.) selected.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead class="bg-orange-700 text-white uppercase text-xs">
                <tr>
                    <th class="border border-gray-600 px-3 py-2 text-left">Vendor Name</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">Items</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">Gross</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">VAT (12%)</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">Net of VAT</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">EWT</th>
                    <th class="border border-gray-600 px-3 py-2 text-right">Amount Due</th>
                </tr>
            </thead>
            <tbody class="text-gray-200">
                @php $tGross=0;$tVat=0;$tNet=0;$tEwt=0;$tDue=0; @endphp
                @foreach($vendors as $name => $v)
                @php $tGross+=$v['gross'];$tVat+=$v['vat'];$tNet+=$v['net'];$tEwt+=$v['ewt'];$tDue+=$v['amount_due']; @endphp
                <tr class="hover:bg-gray-700/40 border-b border-gray-700">
                    <td class="border border-gray-700 px-3 py-2">
                        <a href="{{ route('accounts_payable_invoices.ewt_detail', rawurlencode($name)) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}{{ $status ? '&status='.$status : '' }}"
                           class="text-orange-400 hover:text-orange-300 font-semibold">{{ $name }}</a>
                    </td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-gray-300">{{ $v['count'] }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-right">₱{{ number_format($v['gross'],2) }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-yellow-400">₱{{ number_format($v['vat'],2) }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-blue-400">₱{{ number_format($v['net'],2) }}</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-red-400">(₱{{ number_format($v['ewt'],2) }})</td>
                    <td class="border border-gray-700 px-3 py-2 text-right text-green-400 font-semibold">₱{{ number_format($v['amount_due'],2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-700 font-bold text-white text-sm">
                <tr>
                    <td class="border border-gray-600 px-3 py-2" colspan="2">TOTAL</td>
                    <td class="border border-gray-600 px-3 py-2 text-right">₱{{ number_format($tGross,2) }}</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-yellow-300">₱{{ number_format($tVat,2) }}</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-blue-300">₱{{ number_format($tNet,2) }}</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-red-300">(₱{{ number_format($tEwt,2) }})</td>
                    <td class="border border-gray-600 px-3 py-2 text-right text-green-300">₱{{ number_format($tDue,2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
</div>
@endsection
