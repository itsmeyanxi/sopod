@extends('layouts.app')

@section('title', 'View AR Adjustment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">AR ADJUSTMENT DETAILS</h1>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded text-sm bg-purple-100 text-purple-700">{{ $adjustment->formatted_type }}</span>
                <span class="px-3 py-1 rounded text-sm {{ $adjustment->is_decrease ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                    {{ $adjustment->is_decrease ? 'Decrease AR' : 'Increase AR' }}
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Main Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Reference Number:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white font-semibold">{{ $adjustment->reference_number }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Transaction Date:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white">{{ $adjustment->transaction_date->format('F d, Y') }}</p>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Customer Name:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white">{{ $adjustment->customer_name }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Customer Code:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white">{{ $adjustment->customer_code ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Branch:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white">{{ $adjustment->branch ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Document References -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">DR Number:</label>
                @if($adjustment->dr_no)
                    @if($adjustment->delivery)
                        <a href="{{ route('deliveries.show', $adjustment->delivery->id) }}" class="px-4 py-2 bg-blue-900/30 border border-blue-600 rounded text-blue-300 hover:bg-blue-900/50 transition inline-block">
                            <i class="fas fa-link mr-1"></i> {{ $adjustment->dr_no }}
                        </a>
                    @else
                        <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white">{{ $adjustment->dr_no }}</p>
                    @endif
                @else
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-300">N/A</p>
                @endif
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Invoice Number:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white">{{ $adjustment->invoice_number ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Linked Delivery Information -->
        @if($adjustment->delivery)
        <div class="mb-6 p-4 bg-blue-900/30 border border-blue-600 rounded">
            <h3 class="font-semibold text-blue-300 mb-3">📦 Linked Delivery Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div>
                    <p class="text-gray-400">Delivery Batch</p>
                    <p class="text-blue-300 font-semibold">{{ $adjustment->delivery->delivery_batch ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Delivery Status</p>
                    <p class="text-blue-300 font-semibold">{{ ucfirst($adjustment->delivery->status ?? 'pending') }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Requested Date</p>
                    <p class="text-blue-300 font-semibold">{{ $adjustment->delivery->request_delivery_date?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Linked Receiving Report Information -->
        @if($adjustment->receivingReport)
        @php
            $rrTotal = $adjustment->receivingReport->items ? $adjustment->receivingReport->items->sum('total_amount') : 0;
            $amountMismatch = abs((float)$adjustment->amount) != (float)$rrTotal && $rrTotal > 0;
        @endphp
        <div class="mb-6 p-4 bg-green-900/30 border border-green-600 rounded">
            <h3 class="font-semibold text-green-300 mb-3">
                <i class="fas fa-clipboard-check mr-1"></i> Linked Receiving Report
                <a href="{{ route('receiving-reports.show', $adjustment->receivingReport->id) }}" class="text-xs text-blue-400 hover:underline ml-2">View RR</a>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <div>
                    <p class="text-gray-400">RR Number</p>
                    <p class="text-green-300 font-semibold">{{ $adjustment->receivingReport->rr_number ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Received By</p>
                    <p class="text-green-300 font-semibold">{{ $adjustment->receivingReport->received_by ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Received Date</p>
                    <p class="text-green-300 font-semibold">{{ $adjustment->receivingReport->received_date?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-400">RR Total Amount</p>
                    <p class="text-green-300 font-semibold">₱{{ number_format($rrTotal, 2) }}</p>
                </div>
            </div>
            @if($amountMismatch)
            <div class="mt-3 p-2 bg-yellow-900/40 border border-yellow-600 rounded text-yellow-300 text-xs flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                <span><strong>Amount mismatch:</strong> This adjustment is ₱{{ number_format(abs($adjustment->amount), 2) }} but the RR total is ₱{{ number_format($rrTotal, 2) }}. Update via the Receiving Report to sync.</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Amount & Account -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Amount:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-2xl font-bold {{ $adjustment->is_decrease ? 'text-red-700' : 'text-green-700' }}">
                    {{ $adjustment->is_decrease ? '-' : '+' }}₱{{ number_format(abs($adjustment->amount), 2) }}
                </p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">GL Account:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white font-semibold">{{ $adjustment->gl_account ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Linked GL Account Information -->
        @if($adjustment->glAccount)
        <div class="mb-6 p-4 bg-amber-900/30 border border-amber-600 rounded">
            <h3 class="font-semibold text-amber-300 mb-3">📊 GL Account Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-gray-400">Account Code</p>
                    <p class="text-amber-300 font-semibold">{{ $adjustment->glAccount->account_code ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Account Name</p>
                    <p class="text-amber-300 font-semibold">{{ $adjustment->glAccount->account_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-400">FS Line Item</p>
                    <p class="text-amber-300 font-semibold">{{ $adjustment->glAccount->fs_line_item ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-400">FS Notes</p>
                    <p class="text-amber-300 font-semibold">{{ $adjustment->glAccount->fs_notes ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Signature -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-1">Signed By:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-white">{{ $adjustment->signed_by }}</p>
        </div>

        <!-- Remarks & Attachment -->
        @if($adjustment->remarks || $adjustment->attachment_path)
            <div class="grid grid-cols-1 {{ $adjustment->remarks && $adjustment->attachment_path ? 'md:grid-cols-2' : '' }} gap-6 mb-6">
                @if($adjustment->remarks)
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">Remarks:</label>
                    <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-white min-h-[80px]">
                        {{ $adjustment->remarks }}
                    </div>
                </div>
                @endif
                @if($adjustment->attachment_path)
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">Supporting Document:</label>
                    <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded">
                        <a href="{{ asset('storage/' . $adjustment->attachment_path) }}" target="_blank" class="flex items-center gap-3 text-purple-700 hover:text-purple-900 transition">
                            @if(str_ends_with(strtolower($adjustment->attachment_name), '.pdf'))
                                <i class="fas fa-file-pdf text-2xl text-red-500"></i>
                            @else
                                <i class="fas fa-file-image text-2xl text-blue-500"></i>
                            @endif
                            <div>
                                <p class="font-medium text-sm">{{ $adjustment->attachment_name }}</p>
                                <p class="text-xs text-gray-400">Click to view / download</p>
                            </div>
                        </a>
                    </div>
                </div>
                @endif
            </div>
        @endif

        <!-- Audit Information -->
        <div class="bg-gray-700 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-gray-300 mb-3">Audit Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">Created By:</label>
                    <p>{{ $adjustment->created_by }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">Created At:</label>
                    <p>{{ $adjustment->created_at->format('F d, Y h:i A') }}</p>
                </div>
                @if($adjustment->updated_at && $adjustment->updated_at != $adjustment->created_at)
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">Last Modified:</label>
                        <p>{{ $adjustment->updated_at->format('F d, Y h:i A') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-4 flex-wrap">
            <a href="{{ route('ar_adjustments.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            @if($adjustment->transaction_type === 'credit_memo')
                <a href="{{ route('ar_adjustments.print_memo', ['id' => $adjustment->id, 'type' => 'credit']) }}" target="_blank" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-print mr-1"></i> Print Credit Memo
                </a>
            @elseif($adjustment->transaction_type === 'debit_memo')
                <a href="{{ route('ar_adjustments.print_memo', ['id' => $adjustment->id, 'type' => 'debit']) }}" target="_blank" class="bg-orange-600 text-white px-6 py-2 rounded hover:bg-orange-700 transition">
                    <i class="fas fa-print mr-1"></i> Print Debit Memo
                </a>
            @else
                <a href="{{ route('ar_adjustments.print', $adjustment->id) }}" target="_blank" class="bg-green-700 text-white px-6 py-2 rounded hover:bg-green-800 transition">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
            @endif
            <a href="{{ route('ar_adjustments.edit', $adjustment->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            <form action="{{ route('ar_adjustments.destroy', $adjustment->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this adjustment?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
