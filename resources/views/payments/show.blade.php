@extends('layouts.app')

@section('title', 'Payment Details — CR #' . ($payment->collection_receipt_number ?? 'N/A'))

@section('content')
<div class="container mx-auto max-w-4xl">
    <div class="mb-4">
        <a href="{{ route('payments.entry') }}" class="text-sm text-gray-500 hover:text-gray-200">
            <i class="fas fa-arrow-left mr-1"></i> Back to Payments
        </a>
    </div>

    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-white">Payment Details</h2>
                    <p class="text-blue-100 text-sm mt-0.5">CR #{{ $payment->collection_receipt_number ?? 'N/A' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if(($payment->status ?? 'Posted') === 'Posted' && (auth()->user()->canEditPayments() || auth()->user()->canRequestPaymentEdit()))
                    <a href="{{ route('payments.edit', $payment->id) }}" class="px-3 py-1 rounded-full text-xs font-bold bg-gray-800 text-blue-700 hover:bg-blue-50 transition">
                        <i class="fas fa-edit mr-1"></i>{{ auth()->user()->canEditPayments() ? 'Edit' : 'Request Edit' }}
                    </a>
                    @endif
                    <span class="px-3 py-1 rounded-full text-xs font-bold
                        {{ ($payment->status ?? 'Posted') === 'Posted' ? 'bg-green-100 text-green-800' : 'bg-gray-700 text-white' }}">
                        {{ $payment->status ?? 'Posted' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Customer & Invoice Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 border-b border-gray-100">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Customer Information</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Customer Name</span>
                        <span class="text-sm font-semibold text-white">{{ $payment->customer_name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Customer Code</span>
                        <span class="text-sm text-white">{{ $payment->customer_code ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Branch</span>
                        <span class="text-sm text-white">{{ $payment->branch ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Invoice / Delivery</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Invoice No</span>
                        <span class="text-sm font-semibold text-white">{{ $payment->invoice_no ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">DR No</span>
                        <span class="text-sm text-white">{{ $payment->dr_no ?? '—' }}</span>
                    </div>
                    @if($payment->invoice_outstanding)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Outstanding at Payment</span>
                        <span class="text-sm text-white">₱{{ number_format((float)$payment->invoice_outstanding, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 border-b border-gray-100">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Collection Receipt Date</h3>
                <p class="text-sm font-semibold text-white">
                    {{ $payment->collection_receipt_date ? \Carbon\Carbon::parse($payment->collection_receipt_date)->format('M d, Y') : '—' }}
                </p>
            </div>
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Payment Posting Date</h3>
                <p class="text-sm font-semibold text-white">
                    {{ $payment->payment_posting_date ? \Carbon\Carbon::parse($payment->payment_posting_date)->format('M d, Y') : '—' }}
                </p>
            </div>
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Created</h3>
                <p class="text-sm text-white">
                    {{ $payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->format('M d, Y h:i A') : '—' }}
                </p>
                <p class="text-xs text-gray-500">by {{ $payment->created_by ?? 'System' }}</p>
            </div>
        </div>

        <!-- Amount Breakdown -->
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-4">Amount Breakdown</h3>
            <div class="bg-gray-900 rounded-lg p-4 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-300">Gross Amount</span>
                    <span class="text-lg font-bold text-white">₱{{ number_format((float)($payment->gross_amount ?? $payment->amount ?? 0), 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-300">EWT</span>
                    <span class="text-sm text-orange-600">- ₱{{ number_format((float)($payment->ewt ?? $payment->tax ?? 0), 2) }}</span>
                </div>
                @if((float)($payment->other_adjustment ?? 0) != 0)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-300">Other Adjustments</span>
                    <span class="text-sm text-yellow-600">₱{{ number_format((float)$payment->other_adjustment, 2) }}</span>
                </div>
                @endif
                @if((float)($payment->credit_applied ?? 0) > 0)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-300">Credit Applied</span>
                    <span class="text-sm text-purple-600">
                        - ₱{{ number_format((float)$payment->credit_applied, 2) }}
                    </span>
                </div>
                @if(isset($creditSources) && $creditSources->isNotEmpty())
                    @foreach($creditSources as $cs)
                    <div class="flex justify-between items-center pl-4">
                        <span class="text-xs text-gray-400">from <a href="{{ route('payments.show', $cs->id) }}" class="text-blue-600 hover:underline">CR #{{ $cs->collection_receipt_number }}</a></span>
                        <span class="text-xs text-purple-500">- ₱{{ number_format((float)$cs->credit_amount, 2) }}</span>
                    </div>
                    @endforeach
                @elseif($creditSource)
                    <div class="flex justify-between items-center pl-4">
                        <span class="text-xs text-gray-400">from <a href="{{ route('payments.show', $creditSource->id) }}" class="text-blue-600 hover:underline">CR #{{ $creditSource->collection_receipt_number }}</a></span>
                        <span class="text-xs text-purple-500">- ₱{{ number_format((float)$payment->credit_applied, 2) }}</span>
                    </div>
                @endif
                @endif
                <hr class="border-gray-700">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-200">Net Amount (Check Amount)</span>
                    <span class="text-xl font-bold text-green-700">₱{{ number_format((float)($payment->check_amount ?? $payment->net ?? 0), 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Overpayment / Credit -->
        @if((float)($payment->overpayment ?? 0) > 0)
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-4">Credit Balance (Overpayment)</h3>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm text-purple-700">Overpayment Amount</span>
                    <span class="text-lg font-bold text-purple-800">₱{{ number_format((float)$payment->overpayment, 2) }}</span>
                </div>
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm text-purple-700">Remaining Credit</span>
                    <span class="text-lg font-bold {{ $remainingCredit > 0 ? 'text-green-700' : 'text-gray-500' }}">
                        ₱{{ number_format($remainingCredit, 2) }}
                    </span>
                </div>

                @if($creditApplications->isNotEmpty())
                <div class="mt-3 border-t border-purple-200 pt-3">
                    <p class="text-xs font-bold text-purple-600 uppercase mb-2">Applied To:</p>
                    @foreach($creditApplications as $app)
                    <div class="flex justify-between text-xs py-1">
                        <a href="{{ route('payments.show', $app->id) }}" class="text-blue-600 hover:underline">
                            CR #{{ $app->collection_receipt_number }} — Invoice {{ $app->invoice_no ?? $app->dr_no }}
                        </a>
                        <span class="text-purple-700">₱{{ number_format((float)$app->credit_applied, 2) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Payment Method -->
        <div class="p-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-4">Payment Method</h3>
            <div class="bg-gray-900 rounded-lg p-4">
                @php
                    $typeLabels = ['check' => 'Check', 'bank_transfer' => 'Bank Transfer', 'cash' => 'Cash'];
                    $typeColors = ['check' => 'blue', 'bank_transfer' => 'green', 'cash' => 'yellow'];
                    $method = $payment->payment_method ?? $payment->payment_option ?? 'cash';
                    $color = $typeColors[$method] ?? 'gray';

                    // Fallback: use payment table columns if payment_means_data is incomplete
                    $bankName = $paymentMeans['bank_name'] ?? $payment->bank ?? null;
                    $checkNumber = $paymentMeans['check_number'] ?? $payment->reference_no ?? null;
                    $reference = $paymentMeans['reference'] ?? $payment->reference_no ?? null;
                    $dueDate = $paymentMeans['due_date'] ?? null;
                    $glAccount = $paymentMeans['gl_account'] ?? null;
                    $meansAmount = $paymentMeans['amount'] ?? null;
                @endphp
                <div class="flex items-center gap-3 mb-3">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-{{ $color }}-100 text-{{ $color }}-700 border border-{{ $color }}-300">
                        {{ $typeLabels[$method] ?? ucfirst($method) }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    @if(!empty($bankName))
                    <div>
                        <span class="text-gray-500">Bank</span>
                        <p class="font-semibold text-white">{{ $bankName }}</p>
                    </div>
                    @endif
                    @if(!empty($checkNumber) && $method === 'check')
                    <div>
                        <span class="text-gray-500">Check Number</span>
                        <p class="font-semibold text-white">{{ $checkNumber }}</p>
                    </div>
                    @endif
                    @if(!empty($reference) && $method === 'bank_transfer')
                    <div>
                        <span class="text-gray-500">Reference No</span>
                        <p class="font-semibold text-white">{{ $reference }}</p>
                    </div>
                    @endif
                    @if(!empty($dueDate))
                    <div>
                        <span class="text-gray-500">Due Date</span>
                        <p class="font-semibold text-white">{{ \Carbon\Carbon::parse($dueDate)->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if(!empty($glAccount))
                    <div>
                        <span class="text-gray-500">G/L Account</span>
                        <p class="font-semibold text-white">{{ $glAccount }}</p>
                    </div>
                    @endif
                    @if(!empty($meansAmount))
                    <div>
                        <span class="text-gray-500">Check/Transfer Amount</span>
                        <p class="font-semibold text-white">₱{{ number_format((float)$meansAmount, 2) }}</p>
                    </div>
                    @endif
                    @if(empty($bankName) && empty($checkNumber) && empty($reference) && empty($dueDate) && empty($glAccount))
                    <div class="col-span-2">
                        <p class="text-gray-400 italic text-xs">No additional payment details recorded.</p>
                    </div>
                    @endif
                </div>
                @if($payment->payment_notes)
                <div class="mt-3 pt-3 border-t border-gray-700">
                    <span class="text-xs text-gray-500">Notes</span>
                    <p class="text-sm text-white">{{ $payment->payment_notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
