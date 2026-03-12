@extends('layouts.app')

@section('title', "AR Details - $customerName - $bucketLabel")

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">{{ $customerName }}</h1>
                <p class="text-gray-400 text-sm">Aging Bucket: <span class="text-blue-300 font-semibold">{{ $bucketLabel }}</span></p>
                <p class="text-gray-400 text-sm">Customer Code: <span class="text-gray-300 font-mono">{{ $customerCode }}</span></p>
            </div>
            <div class="flex gap-3 flex-wrap justify-end">
                <a href="{{ route('aging_reports.summary', ['filter_date' => $filterDate, 'include' => $include]) }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Summary</span>
                </a>
                <button type="button" id="export_detail_btn"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export to Excel</span>
                </button>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 mb-6 shadow-lg">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-white text-sm opacity-90 mb-1">Total Invoices</p>
                    <p class="text-white text-3xl font-bold">{{ count($invoices) }}</p>
                </div>
                <div>
                    <p class="text-white text-sm opacity-90 mb-1">Total Outstanding</p>
                    <p class="text-white text-2xl font-bold">₱{{ number_format($totalAmount, 2) }}</p>
                </div>
                <div>
                    <p class="text-white text-sm opacity-90 mb-1">Aging Bucket</p>
                    <p class="text-white text-lg font-bold">{{ $bucketLabel }}</p>
                </div>
                <div>
                    <p class="text-white text-sm opacity-90 mb-1">Average Days</p>
                    <p class="text-white text-2xl font-bold">
                        @if(count($invoices) > 0)
                            {{ round(collect($invoices)->avg('days_overdue'), 0) }}
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-gray-700 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                @if(count($invoices) > 0)
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-blue-600 text-white text-sm">
                                <th class="px-4 py-3 text-left font-semibold border-r border-blue-700">Invoice No</th>
                                <th class="px-4 py-3 text-left font-semibold border-r border-blue-700">DR No</th>
                                <th class="px-4 py-3 text-left font-semibold border-r border-blue-700">Invoice Date</th>
                                <th class="px-4 py-3 text-left font-semibold border-r border-blue-700">Due Date</th>
                                <th class="px-4 py-3 text-center font-semibold border-r border-blue-700">Days Overdue</th>
                                <th class="px-4 py-3 text-right font-semibold border-r border-blue-700">Invoice Amount</th>
                                <th class="px-4 py-3 text-right font-semibold border-r border-blue-700">Settled</th>
                                <th class="px-4 py-3 text-right font-semibold border-r border-blue-700">Outstanding</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-200">
                            @foreach($invoices as $invoice)
                            <tr class="border-b border-gray-600 hover:bg-gray-600 transition">
                                <td class="px-4 py-3 font-mono font-medium">{{ $invoice['invoice_no'] }}</td>
                                <td class="px-4 py-3 font-mono text-sm">{{ $invoice['dr_no'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">{{ Carbon\Carbon::parse($invoice['invoice_date'])->format('M d, Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-semibold">{{ Carbon\Carbon::parse($invoice['due_date'])->format('M d, Y') ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($invoice['days_overdue'] > 0)
                                        <span class="bg-red-600 text-white px-3 py-1 rounded text-sm font-bold">
                                            {{ $invoice['days_overdue'] }} days
                                        </span>
                                    @else
                                        <span class="bg-green-600 text-white px-3 py-1 rounded text-sm">Due Soon</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">₱{{ number_format($invoice['invoice_amount'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-green-400">₱{{ number_format($invoice['settled_amount'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-yellow-400">₱{{ number_format($invoice['net_ar_balance'], 2) }}</td>
                                <td class="px-4 py-3 text-left">
                                    @if($invoice['status'] === 'Open' || $invoice['status'] === 'open')
                                        <span class="bg-yellow-600 text-white px-2 py-1 rounded text-xs">{{ ucfirst($invoice['status']) }}</span>
                                    @elseif($invoice['status'] === 'Closed' || $invoice['status'] === 'closed')
                                        <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">{{ ucfirst($invoice['status']) }}</span>
                                    @else
                                        <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs">{{ ucfirst($invoice['status']) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            <!-- Total Row -->
                            <tr class="bg-blue-600 text-white font-bold text-lg">
                                <td class="px-4 py-4" colspan="5">TOTAL FOR {{ strtoupper($bucketLabel) }}</td>
                                <td class="px-4 py-4 text-right">₱{{ number_format(collect($invoices)->sum('invoice_amount'), 2) }}</td>
                                <td class="px-4 py-4 text-right">₱{{ number_format(collect($invoices)->sum('settled_amount'), 2) }}</td>
                                <td class="px-4 py-4 text-right bg-blue-700">₱{{ number_format($totalAmount, 2) }}</td>
                                <td class="px-4 py-4"></td>
                            </tr>
                        </tbody>
                    </table>
                @else
                    <div class="px-6 py-12 text-center">
                        <i class="fas fa-inbox text-4xl text-gray-500 mb-3"></i>
                        <p class="text-gray-400 text-lg">No invoices found in this aging bucket.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('export_detail_btn')?.addEventListener('click', function() {
    const params = new URLSearchParams({
        customer_code: '{{ $customerCode }}',
        bucket: '{{ $bucket }}',
        filter_date: '{{ $filterDate }}',
        include: '{{ $include }}'
    });
    // You can implement export functionality here
    alert('Export functionality to be implemented');
});
</script>
@endsection
