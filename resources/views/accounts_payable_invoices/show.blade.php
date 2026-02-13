@extends('layouts.app')

@section('title', 'View Accounts Payable Invoice')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Action Buttons -->
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('accounts_payable_invoices.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-2">
                @if($invoice->status === 'pending')
                    <a href="{{ route('accounts_payable_invoices.edit', $invoice->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
                <button onclick="window.print()" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
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

        <!-- Approval Actions -->
        @if(in_array(auth()->user()->role, ['Admin', 'IT', 'Accounting_Approver']) && $invoice->status === 'pending')
            <div class="flex gap-3 mb-4">
                <form action="{{ route('accounts_payable_invoices.approve', $invoice->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        Approve
                    </button>
                </form>
                <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                    Reject
                </button>
            </div>
        @endif

        @if($invoice->status === 'approved' && $invoice->approver)
            <div class="p-4 bg-green-900/20 border border-green-700 rounded mb-4">
                <p class="text-green-400">
                    Approved by {{ $invoice->approver->name }}
                    on {{ $invoice->approved_at->format('M d, Y h:i A') }}
                </p>
            </div>
        @endif

        @if($invoice->status === 'rejected' && $invoice->approver)
            <div class="p-4 bg-red-900/20 border border-red-700 rounded mb-4">
                <p class="text-red-400">
                    Rejected by {{ $invoice->approver->name }}
                    on {{ $invoice->approved_at->format('M d, Y h:i A') }}
                </p>
                @if($invoice->rejection_reason)
                    <p class="text-gray-300 mt-2">
                        <strong>Reason:</strong> {{ $invoice->rejection_reason }}
                    </p>
                @endif
            </div>
        @endif

        <!-- Invoice Content (Print-friendly) -->
        <div id="printableInvoice" class="bg-white text-black p-8 rounded">
            <!-- Header -->
            <div class="border-b-2 border-black pb-4 mb-6">
                <div class="text-center mb-4">
                    <h1 class="text-3xl font-bold">ACCOUNTS PAYABLE VOUCHER</h1>
                    <p class="text-sm text-gray-600 mt-1">APV No: {{ $invoice->apv_no }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p><strong>APV Date:</strong> {{ $invoice->apv_date->format('F d, Y') }}</p>
                        <p><strong>Document Date:</strong> {{ $invoice->document_date->format('F d, Y') }}</p>
                        @if($invoice->due_date)
                            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('F d, Y') }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        @if($invoice->requestForPayment)
                            <p><strong>RFP No:</strong> {{ $invoice->requestForPayment->rfp_no }}</p>
                        @endif
                        @if($invoice->purchase_order_no)
                            <p><strong>PO No:</strong> {{ $invoice->purchase_order_no }}</p>
                        @endif
                        @if($invoice->reference_no)
                            <p><strong>Reference No:</strong> {{ $invoice->reference_no }}</p>
                        @endif
                        <p><strong>Payment Type:</strong>
                            <span class="px-2 py-1 {{ $invoice->payment_type === 'downpayment' ? 'bg-blue-100' : 'bg-green-100' }} rounded">
                                {{ $invoice->payment_type === 'downpayment' ? 'Downpayment' : 'Full Payment' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">VENDOR INFORMATION</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        @if($invoice->vendor_code)
                            <p><strong>Vendor Code:</strong> {{ $invoice->vendor_code }}</p>
                        @endif
                        <p><strong>Vendor Name:</strong> {{ $invoice->vendor_name }}</p>
                        @if($invoice->vendor_address)
                            <p><strong>Address:</strong> {{ $invoice->vendor_address }}</p>
                        @endif
                    </div>
                    <div>
                        @if($invoice->vendor_tin)
                            <p><strong>TIN:</strong> {{ $invoice->vendor_tin }}</p>
                        @endif
                        @if($invoice->payment_terms)
                            <p><strong>Payment Terms:</strong> {{ $invoice->payment_terms }}</p>
                        @endif
                        <p><strong>Currency:</strong> {{ $invoice->currency }}
                            @if($invoice->forex_rate && $invoice->currency !== 'PHP')
                                (Rate: {{ number_format($invoice->forex_rate, 4) }})
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Particulars -->
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">PARTICULARS</h2>
                <div class="bg-gray-50 p-4 rounded text-sm whitespace-pre-wrap">{{ $invoice->particulars }}</div>
            </div>

            <!-- Accounting Details -->
            @if($invoice->item_code || $invoice->cost_center || $invoice->account_code || $invoice->account_name)
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">ACCOUNTING DETAILS</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    @if($invoice->item_code)
                        <p><strong>Item Code:</strong> {{ $invoice->item_code }}</p>
                    @endif
                    @if($invoice->cost_center)
                        <p><strong>Cost Center:</strong> {{ $invoice->cost_center }}</p>
                    @endif
                    @if($invoice->account_code)
                        <p><strong>Account Code:</strong> {{ $invoice->account_code }}</p>
                    @endif
                    @if($invoice->account_name)
                        <p><strong>Account Name:</strong> {{ $invoice->account_name }}</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Amount Breakdown -->
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">AMOUNT BREAKDOWN</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-b">
                            <td class="py-2 text-right pr-4 font-semibold">Total Amount:</td>
                            <td class="py-2 text-right">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
                        </tr>
                        @if($invoice->payment_type === 'downpayment' && $invoice->downpayment_amount)
                        <tr class="border-b bg-blue-50">
                            <td class="py-2 text-right pr-4 font-semibold">Downpayment Amount:</td>
                            <td class="py-2 text-right">{{ $invoice->currency }} {{ number_format($invoice->downpayment_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="border-b">
                            <td class="py-2 text-right pr-4 font-semibold">Total Before VAT:</td>
                            <td class="py-2 text-right">{{ $invoice->currency }} {{ number_format($invoice->total_before_vat, 2) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-right pr-4 font-semibold">VAT Amount:</td>
                            <td class="py-2 text-right">{{ $invoice->currency }} {{ number_format($invoice->vat_amount, 2) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-right pr-4 font-semibold">Total After VAT:</td>
                            <td class="py-2 text-right">{{ $invoice->currency }} {{ number_format($invoice->total_after_vat, 2) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-right pr-4 font-semibold">Withholding Tax:</td>
                            <td class="py-2 text-right">({{ $invoice->currency }} {{ number_format($invoice->w_tax_amount, 2) }})</td>
                        </tr>
                        <tr class="border-t-2 border-black bg-gray-100">
                            <td class="py-3 text-right pr-4 font-bold text-lg">GRAND TOTAL:</td>
                            <td class="py-3 text-right font-bold text-lg">{{ $invoice->currency }} {{ number_format($invoice->grand_total, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Remarks -->
            @if($invoice->remarks)
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">REMARKS</h2>
                <div class="bg-gray-50 p-4 rounded text-sm whitespace-pre-wrap">{{ $invoice->remarks }}</div>
            </div>
            @endif

            <!-- Signatures -->
            <div class="mt-8">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-4">APPROVALS</h2>
                <div class="grid grid-cols-3 gap-8 text-sm">
                    <div class="text-center">
                        <div class="border-b-2 border-black h-16 mb-2"></div>
                        <p class="font-semibold">{{ $invoice->prepared_by ?? '___________________' }}</p>
                        <p class="text-xs text-gray-600">Prepared By</p>
                    </div>
                    <div class="text-center">
                        <div class="border-b-2 border-black h-16 mb-2"></div>
                        <p class="font-semibold">{{ $invoice->reviewed_by ?? '___________________' }}</p>
                        <p class="text-xs text-gray-600">Reviewed By</p>
                    </div>
                    <div class="text-center">
                        <div class="border-b-2 border-black h-16 mb-2"></div>
                        <p class="font-semibold">{{ $invoice->approved_by ?? '___________________' }}</p>
                        <p class="text-xs text-gray-600">Approved By</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-4 border-t border-gray-300 text-xs text-gray-500">
                <div class="flex justify-between">
                    <div>
                        <p>Status:
                            <span class="font-semibold {{ $invoice->status === 'approved' ? 'text-green-600' : ($invoice->status === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        </p>
                        <p>Created by: {{ $invoice->creator->name ?? 'N/A' }} on {{ $invoice->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                    <div class="text-right">
                        @if($invoice->updated_by)
                            <p>Last updated by: {{ $invoice->updater->name ?? 'N/A' }}</p>
                            <p>on {{ $invoice->updated_at->format('F d, Y h:i A') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Invoice</h3>
        <form action="{{ route('accounts_payable_invoices.reject', $invoice->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" rows="4"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Confirm Reject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableInvoice, #printableInvoice * {
        visibility: visible;
    }
    #printableInvoice {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
    }
    .bg-gray-800, .bg-gray-700, .bg-gray-600 {
        background-color: white !important;
    }
    button, .no-print {
        display: none !important;
    }
}
</style>
@endsection
