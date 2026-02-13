@extends('layouts.app')

@section('title', 'View Request for Payment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">REQUEST FOR PAYMENT</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-300">RFP NO:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $rfp->rfp_no }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($rfp->status === 'pending') bg-yellow-600 text-white
                    @elseif($rfp->status === 'approved') bg-green-600 text-white
                    @elseif($rfp->status === 'rejected') bg-red-600 text-white
                    @else bg-blue-600 text-white
                    @endif">
                    {{ ucfirst($rfp->status) }}
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

        <!-- Company -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-2">COMPANY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->company }}</p>
        </div>

        <!-- Payment Methods & Dates Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Left Column - Payment Methods -->
            <div class="bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-300 mb-3">PAYMENT METHODS:</label>
                <div class="space-y-2">
                    @php
                        $methods = is_array($rfp->payment_methods) ? $rfp->payment_methods : json_decode($rfp->payment_methods ?? '[]', true);
                        $methodLabels = [
                            'managers_check' => "Manager's Check",
                            'regular_check' => 'Regular Check',
                            'wire_transfer' => 'Wire Transfer',
                            'fund_transfer' => 'Fund Transfer',
                            'pdc' => 'PDC (Post-Dated Check)',
                            'cash' => 'Cash',
                            'auto_debit' => 'Auto Debit',
                            'others' => 'Others',
                        ];
                    @endphp
                    @if(!empty($methods))
                        @foreach($methods as $method)
                            <div class="flex items-center p-2 bg-gray-800 rounded">
                                <span class="text-green-400 mr-2">&#10003;</span>
                                <span class="text-gray-300">{{ $methodLabels[$method] ?? ucfirst($method) }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-400">No payment methods specified</p>
                    @endif
                </div>
            </div>

            <!-- Right Column - Dates and Reference Numbers -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->date->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DUE DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->due_date ? $rfp->due_date->format('F d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">LINKED PO:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">
                        @if($rfp->purchaseOrder)
                            <a href="{{ route('purchase_orders.show', $rfp->purchaseOrder->id) }}" class="text-purple-400 hover:text-purple-300">
                                {{ $rfp->purchaseOrder->po_no }}
                            </a>
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Form Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">PAYEE:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->payee }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">AMOUNT:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 font-semibold text-lg">&#8369;{{ number_format($rfp->amount, 2) }}</p>
            </div>
        </div>

        <!-- Particulars -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-2">PARTICULARS:</label>
            <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[100px]">
                {{ $rfp->particulars ?? 'No particulars provided' }}
            </div>
        </div>

        <!-- Bank -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-1">BANK/S:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->bank ?? 'N/A' }}</p>
        </div>

        <!-- APV and CV Numbers -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">APV NO.:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->apv_no ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">CV NO.:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->cv_no ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Requestor and Checker -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">REQUESTED BY:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->requested_by ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">CHECKED BY:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->checked_by ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Created By -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-1">CREATED BY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->creator->name ?? 'N/A' }}</p>
        </div>

        <!-- Signature Section -->
        <div class="mb-6">
            <div class="border border-gray-700 rounded">
                <div class="bg-gray-900 border-b border-gray-700 px-4 py-2 text-center font-semibold text-yellow-400">
                    FOR FINANCE USE ONLY
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By (Php 50,000 above):</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                        </tr>
                        <tr class="bg-gray-700 text-gray-300 text-xs italic">
                            <td class="border border-gray-700 px-4 py-2 text-center">Finance Manager</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">CFO / President</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Status -->
        @if(auth()->user()->canApproveRequestForPayments() && $rfp->status === 'pending')
            <div class="flex gap-3 mb-4 mt-6">
                <form action="{{ route('request_for_payments.approve', $rfp->id) }}" method="POST">
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

        @if($rfp->status === 'approved' && $rfp->approver)
            <div class="p-4 bg-green-900/20 border border-green-700 rounded mb-4 mt-6">
                <p class="text-green-400">
                    Approved by {{ $rfp->approver->name }}
                    on {{ $rfp->approved_at->format('M d, Y h:i A') }}
                </p>
            </div>
        @endif

        @if($rfp->status === 'rejected' && $rfp->approver)
            <div class="p-4 bg-red-900/20 border border-red-700 rounded mb-4 mt-6">
                <p class="text-red-400">
                    Rejected by {{ $rfp->approver->name }}
                    on {{ $rfp->approved_at->format('M d, Y h:i A') }}
                </p>
                @if($rfp->rejection_reason)
                    <p class="text-gray-300 mt-2">
                        <strong>Reason:</strong> {{ $rfp->rejection_reason }}
                    </p>
                @endif
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('request_for_payments.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                Back to List
            </a>
            <div class="flex gap-4">
                <a href="{{ route('request_for_payments.edit', $rfp->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <button onclick="window.print()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Request for Payment</h3>
        <form action="{{ route('request_for_payments.reject', $rfp->id) }}" method="POST">
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
    .bg-gray-800, .bg-gray-700, .bg-blue-600, .bg-green-600, button, a {
        display: none !important;
    }
    .container {
        max-width: 100%;
    }
}
</style>
@endsection
