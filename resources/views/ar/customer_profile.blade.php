@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gray-50 rounded-xl shadow-lg border border-gray-200">
        <!-- Header -->
        <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                    👤 Customer AR Profile
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    <span class="font-semibold">{{ $customerName }}</span>
                    <span class="mx-2">·</span>
                    <span class="font-mono text-blue-700">{{ $customerCode }}</span>
                </p>
            </div>
            <a href="{{ route('aging_reports.view') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Aging Reports
            </a>
        </div>

        <!-- Summary Stats -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-100 rounded-lg p-4 border border-blue-700">
                    <p class="text-gray-500 text-sm font-semibold">Total Invoiced</p>
                    <p class="text-2xl font-bold text-blue-700 mt-1">₱{{ number_format($summary->total_invoiced ?? 0, 2) }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-4 border border-green-200">
                    <p class="text-gray-500 text-sm font-semibold">Total Collected</p>
                    <p class="text-2xl font-bold text-green-700 mt-1">₱{{ number_format($summary->total_collected ?? 0, 2) }}</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-4 border border-purple-700">
                    <p class="text-gray-500 text-sm font-semibold">Total Adjustments</p>
                    <p class="text-2xl font-bold text-purple-700 mt-1">₱{{ number_format($summary->total_adjustments ?? 0, 2) }}</p>
                </div>
                <div class="bg-red-100 rounded-lg p-4 border border-red-200">
                    <p class="text-gray-500 text-sm font-semibold">Outstanding Balance</p>
                    <p class="text-2xl font-bold text-red-700 mt-1">₱{{ number_format($summary->outstanding_balance ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="p-6">
            <div class="flex gap-4 border-b border-gray-200 mb-6">
                <button class="tab-btn active px-4 py-2 font-semibold border-b-2 border-blue-500 text-blue-700 hover:text-blue-700" data-tab="aging">
                    <i class="fas fa-file-invoice mr-2"></i> AR Aging
                </button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-500 hover:text-gray-500" data-tab="collections">
                    <i class="fas fa-credit-card mr-2"></i> Collections
                </button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-500 hover:text-gray-500" data-tab="adjustments">
                    <i class="fas fa-sliders-h mr-2"></i> Adjustments
                </button>
            </div>

            <!-- AR Aging Tab -->
            <div id="aging-tab" class="tab-content active">
                @if($arAging->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-white text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-left">Invoice No</th>
                                    <th class="px-4 py-3 text-left">Invoice Date</th>
                                    <th class="px-4 py-3 text-left">DR No</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3 text-right">Settled</th>
                                    <th class="px-4 py-3 text-right">Net AR</th>
                                    <th class="px-4 py-3 text-center">Age (days)</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($arAging as $invoice)
                                <tr class="text-gray-500 hover:bg-white/50">
                                    <td class="px-4 py-3 font-mono text-blue-700">{{ $invoice->invoice_no }}</td>
                                    <td class="px-4 py-3">{{ $invoice->invoice_date ? Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') : '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-green-700">{{ $invoice->so_dr_po ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($invoice->invoice_amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($invoice->settled_invoice_amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $invoice->net_ar_balance > 0 ? 'text-red-700' : 'text-green-700' }}">
                                        ₱{{ number_format($invoice->net_ar_balance ?? 0, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-yellow-700">{{ $invoice->age ?? 0 }}</td>
                                    <td class="px-4 py-3">
                                        @if($invoice->status === 'Paid')
                                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Paid</span>
                                        @elseif($invoice->status === 'Partial')
                                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">Partial</span>
                                        @else
                                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">Outstanding</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-gray-600 text-3xl mb-3 block"></i>
                        <p class="text-gray-500">No AR aging records found for this customer</p>
                    </div>
                @endif
            </div>

            <!-- Collections Tab -->
            <div id="collections-tab" class="tab-content hidden">
                @if($payments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-white text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-left">CR No</th>
                                    <th class="px-4 py-3 text-left">Date</th>
                                    <th class="px-4 py-3 text-left">DR No</th>
                                    <th class="px-4 py-3 text-left">Invoice No</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3 text-left">Payment Type</th>
                                    <th class="px-4 py-3 text-left">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($payments as $payment)
                                <tr class="text-gray-500 hover:bg-white/50">
                                    <td class="px-4 py-3 font-mono text-blue-700">{{ $payment->collection_receipt_number ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $payment->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-green-700">{{ $payment->dr_no ?? '—' }}</td>
                                    <td class="px-4 py-3 font-mono">{{ $payment->invoice_no ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-green-700">₱{{ number_format($payment->amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-3">
                                        @if($payment->payment_means)
                                            @php
                                                $means = is_string($payment->payment_means) ? json_decode($payment->payment_means, true) : $payment->payment_means;
                                                $type = is_array($means) ? ($means['type'] ?? 'Unknown') : 'Unknown';
                                            @endphp
                                            <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded text-xs capitalize">{{ $type }}</span>
                                        @else
                                            <span class="text-gray-500 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $payment->payment_notes ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-gray-600 text-3xl mb-3 block"></i>
                        <p class="text-gray-500">No collections recorded for this customer</p>
                    </div>
                @endif
            </div>

            <!-- Adjustments Tab -->
            <div id="adjustments-tab" class="tab-content hidden">
                @if($adjustments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-white text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-left">Ref No</th>
                                    <th class="px-4 py-3 text-left">Date</th>
                                    <th class="px-4 py-3 text-left">Type</th>
                                    <th class="px-4 py-3 text-left">DR No</th>
                                    <th class="px-4 py-3 text-left">Invoice No</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3 text-left">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($adjustments as $adj)
                                <tr class="text-gray-500 hover:bg-white/50">
                                    <td class="px-4 py-3 font-mono text-blue-700">{{ $adj->reference_number }}</td>
                                    <td class="px-4 py-3">{{ $adj->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">
                                            {{ str_replace('_', ' ', ucwords($adj->transaction_type)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-green-700">{{ $adj->dr_no ?? '—' }}</td>
                                    <td class="px-4 py-3 font-mono">{{ $adj->invoice_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $adj->amount < 0 ? 'text-red-700' : 'text-green-700' }}">
                                        {{ $adj->amount < 0 ? '−' : '+'}}₱{{ number_format(abs($adj->amount ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $adj->remarks ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-gray-600 text-3xl mb-3 block"></i>
                        <p class="text-gray-500">No adjustments recorded for this customer</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabName = this.dataset.tab;

        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
            tab.classList.remove('active');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active', 'border-blue-500', 'text-blue-700');
            b.classList.add('text-gray-500');
        });

        // Show selected tab and highlight button
        document.getElementById(tabName + '-tab').classList.remove('hidden');
        document.getElementById(tabName + '-tab').classList.add('active');
        this.classList.add('active', 'border-blue-500', 'text-blue-700');
        this.classList.remove('text-gray-500');
    });
});
</script>
@endsection
