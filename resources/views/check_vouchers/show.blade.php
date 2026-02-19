@extends('layouts.app')

@section('title', 'View Check Voucher')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Action Buttons -->
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('check_vouchers.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-2">
                @if($voucher->status === 'pending')
                    <a href="{{ route('check_vouchers.edit', $voucher->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
                <a href="{{ route('check_vouchers.print', $voucher->id) }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition inline-block">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
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
        @if(in_array(auth()->user()->role, ['Admin', 'IT', 'Accounting_Approver']) && $voucher->status === 'pending')
            <div class="flex gap-3 mb-4">
                <form action="{{ route('check_vouchers.approve', $voucher->id) }}" method="POST">
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

        @if($voucher->status === 'approved' && $voucher->approvalUser)
            <div class="p-4 bg-green-900/20 border border-green-700 rounded mb-4">
                <p class="text-green-400">
                    Approved by {{ $voucher->approvalUser->name }}
                    on {{ $voucher->approval_date->format('M d, Y h:i A') }}
                </p>
            </div>
        @endif

        @if($voucher->status === 'rejected' && $voucher->approvalUser)
            <div class="p-4 bg-red-900/20 border border-red-700 rounded mb-4">
                <p class="text-red-400">
                    Rejected by {{ $voucher->approvalUser->name }}
                    on {{ $voucher->approval_date->format('M d, Y h:i A') }}
                </p>
                @if($voucher->rejection_reason)
                    <p class="text-gray-300 mt-2">
                        <strong>Reason:</strong> {{ $voucher->rejection_reason }}
                    </p>
                @endif
            </div>
        @endif

        <!-- Check Voucher Content (Print-friendly) -->
        <div id="printableVoucher" class="bg-white text-black p-8 rounded">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="flex justify-between items-start">
                    <div class="text-left">
                        <h2 class="text-2xl font-bold">Meatplus Trading Corp</h2>
                        <p class="text-xs">12F Victoria Building</p>
                        <p class="text-xs">United Nations Avenue, Ermita, Manila, Philippines, 1004</p>
                        <p class="text-xs">VAT Reg. TIN 006-873-989-000</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm"><strong>CV No.:</strong> <span class="text-red-600">{{ $voucher->cv_no }}</span></p>
                        <p class="text-sm"><strong>CV Date:</strong> {{ $voucher->cv_date->format('m/d/Y') }}</p>
                    </div>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-center mb-6 border-y-2 border-black py-2">CHECK VOUCHER</h1>

            <!-- Supplier and Check Info Grid -->
            <div class="grid grid-cols-2 gap-8 mb-6 text-sm">
                <!-- Left Column - Supplier -->
                <div>
                    <div class="mb-2">
                        <p class="font-semibold">Supplier Code:</p>
                        <p>{{ $voucher->supplier_code ?? '' }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-semibold">Supplier Name:</p>
                        <p>{{ $voucher->supplier_name }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-semibold">Supplier Address</p>
                        <p>{{ $voucher->supplier_address ?? '' }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-semibold">Supplier TIN</p>
                        <p>{{ $voucher->supplier_tin ?? '' }}</p>
                    </div>
                </div>

                <!-- Right Column - Check Details -->
                <div>
                    <div class="mb-2">
                        <p class="font-semibold">Check No.:</p>
                        <p>{{ $voucher->check_no ?? '0' }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-semibold">Bank:</p>
                        <p>{{ $voucher->bank ?? '' }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-semibold">Branch:</p>
                        <p>{{ $voucher->branch ?? '' }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-semibold">Check Amount:</p>
                        <p class="text-lg font-bold">{{ number_format($voucher->check_amount, 2) }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-semibold">Check Date:</p>
                        <p>{{ $voucher->check_date->format('m/d/Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Details Table -->
            <div class="mb-6">
                <p class="font-semibold mb-2">Details</p>
                <table class="w-full border-collapse border border-black text-sm">
                    <thead class="bg-red-700 text-white">
                        <tr>
                            <th class="border border-black px-2 py-1">Date</th>
                            <th class="border border-black px-2 py-1">Type</th>
                            <th class="border border-black px-2 py-1">Reference No.</th>
                            <th class="border border-black px-2 py-1">APV No.</th>
                            <th class="border border-black px-2 py-1">Paid Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-black px-2 py-1 text-center">
                                {{ $voucher->payment_date ? $voucher->payment_date->format('m/d/Y') : $voucher->cv_date->format('m/d/Y') }}
                            </td>
                            <td class="border border-black px-2 py-1 text-center">{{ $voucher->payment_type ?? 'AP' }}</td>
                            <td class="border border-black px-2 py-1 text-center">{{ $voucher->reference_no ?? '' }}</td>
                            <td class="border border-black px-2 py-1 text-center">{{ $voucher->apv_no ?? '' }}</td>
                            <td class="border border-black px-2 py-1 text-right">{{ number_format($voucher->paid_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Particulars -->
            <div class="mb-6 border border-black p-3">
                <p class="text-sm whitespace-pre-wrap">{{ $voucher->particulars }}</p>
            </div>

            <!-- Journal Entry -->
            @if($voucher->journal_entries && count($voucher->journal_entries) > 0)
            <div class="mb-6">
                <p class="font-semibold mb-2">Journal Entry</p>
                <table class="w-full border-collapse border border-black text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border border-black px-2 py-1">Account Code</th>
                            <th class="border border-black px-2 py-1">Account Name</th>
                            <th class="border border-black px-2 py-1">Debit</th>
                            <th class="border border-black px-2 py-1">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->journal_entries as $entry)
                        <tr>
                            <td class="border border-black px-2 py-1">{{ $entry['account_code'] ?? '' }}</td>
                            <td class="border border-black px-2 py-1">{{ $entry['account_name'] ?? '' }}</td>
                            <td class="border border-black px-2 py-1 text-right">{{ isset($entry['debit']) ? number_format($entry['debit'], 2) : '' }}</td>
                            <td class="border border-black px-2 py-1 text-right">{{ isset($entry['credit']) ? number_format($entry['credit'], 2) : '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="mb-6">
                <p class="font-semibold mb-2">Journal Entry</p>
                <table class="w-full border-collapse border border-black text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border border-black px-2 py-1">Account Code</th>
                            <th class="border border-black px-2 py-1">Account Name</th>
                            <th class="border border-black px-2 py-1">Debit</th>
                            <th class="border border-black px-2 py-1">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-black px-2 py-1 h-12">&nbsp;</td>
                            <td class="border border-black px-2 py-1">&nbsp;</td>
                            <td class="border border-black px-2 py-1">&nbsp;</td>
                            <td class="border border-black px-2 py-1">&nbsp;</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif

            <!-- Signatures -->
            <div class="grid grid-cols-3 gap-8 mb-6 text-sm">
                <div class="text-center">
                    <p class="mb-8">Prepared by:</p>
                    <p class="font-bold border-t border-black pt-1">{{ $voucher->prepared_by ?? '___________________' }}</p>
                </div>
                <div class="text-center">
                    <p class="mb-8">Reviewed by:</p>
                    <p class="font-bold border-t border-black pt-1">{{ $voucher->reviewed_by ?? '___________________' }}</p>
                </div>
                <div class="text-center">
                    <p class="mb-8">Approved by:</p>
                    <p class="font-bold border-t border-black pt-1">{{ $voucher->approved_by ?? 'ODM / FDM' }}</p>
                </div>
            </div>

            <!-- Received By -->
            <div class="mb-6 text-sm">
                <div class="flex justify-end items-center gap-4">
                    <span>Received by and date received:</span>
                    <div class="border-b border-black w-64 text-center">
                        {{ $voucher->received_by ?? '' }}
                        @if($voucher->date_received)
                            - {{ $voucher->date_received->format('m/d/Y') }}
                        @endif
                    </div>
                </div>
                <p class="text-center text-xs mt-2">Signature over printed name</p>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-600 border-t border-gray-300 pt-3">
                <p class="mb-1"><em>This is a system generated document. No signature is required.</em></p>
                <p class="mb-1"><em>This document is not valid for claiming input tax</em></p>
                <div class="flex justify-between mt-3">
                    <p>Print Date/Time: {{ now()->format('n/j/Y g:i:s A') }}</p>
                    <p>Page 1 of 1</p>
                </div>
            </div>

            <!-- Status Footer (Not printed) -->
            <div class="mt-6 pt-4 border-t border-gray-300 text-xs text-gray-500 no-print">
                <div class="flex justify-between">
                    <div>
                        <p>Status:
                            <span class="font-semibold {{ $voucher->status === 'approved' ? 'text-green-600' : ($voucher->status === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ strtoupper($voucher->status) }}
                            </span>
                        </p>
                        <p>Created by: {{ $voucher->creator->name ?? 'N/A' }} on {{ $voucher->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                    <div class="text-right">
                        @if($voucher->updated_by)
                            <p>Last updated by: {{ $voucher->updater->name ?? 'N/A' }}</p>
                            <p>on {{ $voucher->updated_at->format('F d, Y h:i A') }}</p>
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
        <h3 class="text-xl font-bold text-white mb-4">Reject Check Voucher</h3>
        <form action="{{ route('check_vouchers.reject', $voucher->id) }}" method="POST">
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
    #printableVoucher, #printableVoucher * {
        visibility: visible;
    }
    #printableVoucher {
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
