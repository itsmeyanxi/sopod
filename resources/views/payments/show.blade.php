@extends('layouts.app')

@section('title', 'Payment Details — CR #' . ($payment->collection_receipt_number ?? 'N/A'))

@section('content')
<div class="container mx-auto max-w-4xl">
    <div class="mb-4">
        <a href="{{ route('payments.entry') }}" class="text-sm text-gray-300 hover:text-gray-200">
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
                    @if(($payment->status ?? 'Clearing') === 'Clearing' && (auth()->user()->canEditPayments() || auth()->user()->canRequestPaymentEdit()))
                    <a href="{{ route('payments.edit', $payment->id) }}" class="px-3 py-1 rounded-full text-xs font-bold bg-gray-800 text-blue-700 hover:bg-blue-50 transition">
                        <i class="fas fa-edit mr-1"></i>{{ auth()->user()->canEditPayments() ? 'Edit' : 'Request Edit' }}
                    </a>
                    @endif
                    @if(auth()->user()->isAdminUser())
                    <button onclick="showDeleteModal()" class="px-3 py-1 rounded-full text-xs font-bold bg-red-800 text-white hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                    @endif
                    <span class="px-3 py-1 rounded-full text-xs font-bold
                        @if(($payment->status ?? 'Clearing') === 'Clearing') bg-yellow-100 text-yellow-800
                        @elseif($payment->status === 'Posted') bg-green-100 text-green-800
                        @elseif($payment->status === 'Bounced') bg-red-100 text-red-800
                        @else bg-gray-700 text-white @endif">
                        {{ $payment->status ?? 'Clearing' }}
                    </span>
                    @if($payment->is_short_payment)
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-300">
                        Short Payment
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Customer & Invoice Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 border-b border-gray-100">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Customer Information</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-300">Customer Name</span>
                        <span class="text-sm font-semibold text-white">{{ $payment->customer_name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-300">Customer Code</span>
                        <span class="text-sm text-white">{{ $payment->customer_code ?? '—' }}</span>
                    </div>
                    @if(auth()->user()->isAdminUser())
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-300">Branch</span>
                        <span class="text-sm text-white">{{ $payment->branch ?? '—' }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Invoice / Delivery</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-300">Invoice No</span>
                        <span class="text-sm font-semibold text-white">{{ $payment->invoice_no ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-300">DR No</span>
                        <span class="text-sm text-white">{{ $payment->dr_no ?? '—' }}</span>
                    </div>
                    @if($payment->invoice_outstanding)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-300">Outstanding at Payment</span>
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
                <p class="text-xs text-gray-300">by {{ $payment->created_by ?? 'System' }}</p>
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

        <!-- AR Adjustments for this DR -->
        @if($arAdjustments->isNotEmpty())
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-4">
                AR Adjustments Applied to DR {{ $payment->dr_no }}
            </h3>
            <div class="bg-gray-900 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-700 text-xs text-gray-400 uppercase">
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Reference</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-left">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $netAdj = 0; @endphp
                        @foreach($arAdjustments as $adj)
                        @php
                            $adjAmt = (float)$adj->amount;
                            $isDecrease = (bool)$adj->is_decrease;
                            $signed = $isDecrease ? -$adjAmt : $adjAmt;
                            $netAdj += $signed;
                        @endphp
                        <tr class="border-b border-gray-800 hover:bg-gray-800">
                            <td class="px-4 py-2 text-gray-400 whitespace-nowrap">
                                {{ $adj->transaction_date ? \Carbon\Carbon::parse($adj->transaction_date)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-4 py-2 text-gray-300 whitespace-nowrap capitalize">
                                {{ str_replace('_', ' ', $adj->transaction_type ?? '—') }}
                            </td>
                            <td class="px-4 py-2 text-gray-400 whitespace-nowrap">
                                {{ $adj->reference_number ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-right font-semibold whitespace-nowrap {{ $isDecrease ? 'text-green-400' : 'text-red-400' }}">
                                {{ $isDecrease ? '-' : '+' }}₱{{ number_format($adjAmt, 2) }}
                            </td>
                            <td class="px-4 py-2 text-gray-400 text-xs">{{ $adj->remarks ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-600 bg-gray-800">
                            <td colspan="3" class="px-4 py-2 text-xs font-bold text-gray-300 uppercase">Net Adjustment</td>
                            <td class="px-4 py-2 text-right font-bold {{ $netAdj < 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $netAdj < 0 ? '' : '+' }}₱{{ number_format($netAdj, 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

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
                    <span class="text-lg font-bold {{ $remainingCredit > 0 ? 'text-green-700' : 'text-gray-300' }}">
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
                    $glAccount = $paymentMeans['gl_account_name'] ?? $paymentMeans['gl_account'] ?? null;
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
                        <span class="text-gray-300">Bank</span>
                        <p class="font-semibold text-white">{{ $bankName }}</p>
                    </div>
                    @endif
                    @if(!empty($checkNumber) && $method === 'check')
                    <div>
                        <span class="text-gray-300">Check Number</span>
                        <p class="font-semibold text-white">{{ $checkNumber }}</p>
                    </div>
                    @endif
                    @if(!empty($reference) && $method === 'bank_transfer')
                    <div>
                        <span class="text-gray-300">Reference No</span>
                        <p class="font-semibold text-white">{{ $reference }}</p>
                    </div>
                    @endif
                    @if(!empty($dueDate))
                    <div>
                        <span class="text-gray-300">Due Date</span>
                        <p class="font-semibold text-white">{{ \Carbon\Carbon::parse($dueDate)->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if(!empty($glAccount))
                    <div>
                        <span class="text-gray-300">G/L Account</span>
                        <p class="font-semibold text-white">{{ $glAccount }}</p>
                    </div>
                    @endif
                    @if(!empty($meansAmount))
                    <div>
                        <span class="text-gray-300">Check/Transfer Amount</span>
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
                    <span class="text-xs text-gray-300">Notes</span>
                    <p class="text-sm text-white">{{ $payment->payment_notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdminUser())
<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-bold text-red-400 mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>Permanently Delete Payment
        </h3>
        <div class="text-sm text-gray-300 mb-4">
            <p>You are about to <strong class="text-red-400">permanently delete</strong> this payment:</p>
            <ul class="mt-2 space-y-1 text-xs text-gray-400">
                <li>CR#: <strong class="text-white">{{ $payment->collection_receipt_number ?? 'N/A' }}</strong></li>
                <li>DR#: <strong class="text-white">{{ $payment->dr_no ?? 'N/A' }}</strong></li>
                <li>Amount: <strong class="text-white">₱{{ number_format((float)($payment->amount ?? 0), 2) }}</strong></li>
            </ul>
            <div class="mt-3 p-2 bg-yellow-900 bg-opacity-40 rounded text-yellow-300 text-xs">
                <i class="fas fa-info-circle mr-1"></i>
                This will reverse the AR Aging balance and permanently remove this record.
            </div>
        </div>
        <form action="{{ route('payments.destroy', $payment->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <label class="block text-sm text-gray-400 mb-1">Reason for deletion <span class="text-red-400">*</span></label>
            <textarea name="delete_reason" required rows="3" class="w-full bg-gray-700 text-white rounded px-3 py-2 text-sm border border-gray-600 focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Enter reason for deleting this payment..."></textarea>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-600 text-white rounded text-sm hover:bg-gray-500">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded text-sm hover:bg-red-600 font-bold">
                    <i class="fas fa-trash mr-1"></i>Delete Permanently
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showDeleteModal() { document.getElementById('deleteModal').classList.remove('hidden'); }
function closeDeleteModal() { document.getElementById('deleteModal').classList.add('hidden'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDeleteModal(); });
</script>
@endif
@endsection
