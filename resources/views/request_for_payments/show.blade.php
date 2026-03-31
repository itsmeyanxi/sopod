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
                    <label class="font-semibold text-gray-500">RFP NO:</label>
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
            <label class="block font-semibold text-gray-500 mb-2">COMPANY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->company }}</p>
        </div>

        <!-- Payment Methods & Dates Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Left Column - Payment Methods -->
            <div class="bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-500 mb-3">PAYMENT METHODS:</label>
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
                                <span class="text-green-700 mr-2">&#10003;</span>
                                <span class="text-gray-300">{{ $methodLabels[$method] ?? ucfirst($method) }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500">No payment methods specified</p>
                    @endif
                </div>
            </div>

            <!-- Right Column - Dates and Reference Numbers -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->date->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">DUE DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->due_date ? $rfp->due_date->format('F d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">LINKED PO:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">
                        @if($rfp->purchaseOrder)
                            <a href="{{ route('purchase_orders.show', $rfp->purchaseOrder->id) }}" class="text-purple-700 hover:text-purple-700">
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
                <label class="block font-semibold text-gray-500 mb-1">PAYEE (Vendor/Supplier):</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->payee }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">AMOUNT:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 font-semibold text-lg">&#8369;{{ number_format($rfp->amount, 2) }}</p>
            </div>
        </div>

        <!-- Particulars -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-2">PARTICULARS:</label>
            <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[100px]">
                {{ $rfp->particulars ?? 'No particulars provided' }}
            </div>
        </div>

        <!-- Bank -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-1">BANK/S:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->bank ?? 'N/A' }}</p>
        </div>

        <!-- APV and CV Numbers -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">APV NO.:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->apv_no ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">CV NO.:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->cv_no ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Requestor and Checker -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">REQUESTED BY:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->requested_by ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">CHECKED BY:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->checked_by ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Created By -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-1">CREATED BY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $rfp->creator->name ?? 'N/A' }}</p>
        </div>

        <!-- Signature Section -->
        <div class="mb-6">
            <div class="border border-gray-700 rounded">
                <table class="w-full" style="table-layout: fixed;">
                    <colgroup>
                        <col style="width: 25%;">
                        <col style="width: 25%;">
                        <col style="width: 25%;">
                        <col style="width: 25%;">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Prepared By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Checked By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm" colspan="2">Approved By:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $rfp->creator->name ?? '' }}</span>
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $rfp->departmentHeadApprover->name ?? '' }}</span>
                                @if($rfp->departmentHeadApprover && $rfp->department_head_approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $rfp->department_head_approved_at->format('d M Y | H:i') }}
                                        @if($rfp->department_head_approved_latitude && $rfp->department_head_approved_longitude)
                                            <br>Coords: {{ $rfp->department_head_approved_latitude }}, {{ $rfp->department_head_approved_longitude }}
                                            @if($rfp->department_head_approved_location)
                                                ({{ $rfp->department_head_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $rfp->accountingApprover->name ?? '' }}</span>
                                @if($rfp->accountingApprover && $rfp->accounting_approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $rfp->accounting_approved_at->format('d M Y | H:i') }}
                                        @if($rfp->accounting_approved_latitude && $rfp->accounting_approved_longitude)
                                            <br>Coords: {{ $rfp->accounting_approved_latitude }}, {{ $rfp->accounting_approved_longitude }}
                                            @if($rfp->accounting_approved_location)
                                                ({{ $rfp->accounting_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $rfp->approver->name ?? '' }}</span>
                                @if($rfp->approver && $rfp->approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $rfp->approved_at->format('d M Y | H:i') }}
                                        @if($rfp->approved_latitude && $rfp->approved_longitude)
                                            <br>Coords: {{ $rfp->approved_latitude }}, {{ $rfp->approved_longitude }}
                                            @if($rfp->approved_location)
                                                ({{ $rfp->approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-gray-700 text-gray-500 text-xs italic">
                            <td class="border border-gray-700 px-4 py-2 text-center">Requisitioner</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Department Head</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Finance Manager</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">CFO / President</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Trail -->
        <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
            <h3 class="text-lg font-semibold text-white mb-4">Approval Trail</h3>
            <div class="space-y-3">
                <!-- Department Head Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($rfp->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Department Head Check</span>
                            @if($rfp->department_head_approved_by && $rfp->departmentHeadApprover)
                                <span class="text-green-700">✓ Checked</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $rfp->departmentHeadApprover->name }}
                                    on {{ $rfp->department_head_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @else
                                <span class="text-yellow-700">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Accounting Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($rfp->accounting_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($rfp->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-700">
                                <i class="fas fa-lock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Accounting Check (Finance Manager)</span>
                            @if($rfp->accounting_approved_by && $rfp->accountingApprover)
                                <span class="text-green-700">✓ Checked</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $rfp->accountingApprover->name }}
                                    on {{ $rfp->accounting_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @elseif($rfp->department_head_approved_by)
                                <span class="text-yellow-700">Pending</span>
                            @else
                                <span class="text-gray-300">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Executive Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($rfp->status === 'approved' && $rfp->approver)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($rfp->accounting_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-700">
                                <i class="fas fa-lock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Executive Approval (CFO/President)</span>
                            @if($rfp->status === 'approved' && $rfp->approver)
                                <span class="text-green-700">✓ Approved</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $rfp->approver->name }}
                                    on {{ $rfp->approved_at->format('M d, Y h:i A') }}
                                </small>
                            @elseif($rfp->accounting_approved_by)
                                <span class="text-yellow-700">Pending</span>
                            @else
                                <span class="text-gray-300">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($rfp->status === 'rejected')
                    <div class="flex items-start gap-4 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-700">
                                <span class="font-semibold">Rejected</span>
                            </p>
                            @if($rfp->rejection_reason)
                                <p class="text-gray-500 mt-2">
                                    <strong>Reason:</strong> {{ $rfp->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Approval Buttons -->
        @if($rfp->status === 'pending' && $rfp->approval_stage !== 'rejected')
            <div class="flex gap-3 mb-4 mt-6">
                @if($rfp->approval_stage === 'pending_dh' && auth()->user()->canApproveRFPAsDH())
                    <button type="button" onclick="showApproveDHModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Department Head
                    </button>
                @endif

                @if($rfp->approval_stage === 'pending_accounting' && auth()->user()->canApproveRFPAsAccounting())
                    <button type="button" onclick="showApproveAccountingModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Finance Manager
                    </button>
                @endif

                @if($rfp->approval_stage === 'pending_executive' && auth()->user()->canApproveRFPAsExecutive())
                    <button type="button" onclick="showApproveExecutiveModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Executive
                    </button>
                @endif

                @if(auth()->user()->canApproveRequestForPayments())
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                @endif
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('request_for_payments.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                Back to List
            </a>
            <div class="flex gap-4">
                <a href="{{ route('request_for_payments.edit', $rfp->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('request_for_payments.print', $rfp->id) }}" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition inline-block">
                    Print
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Department Head Approval Modal -->
<div id="approveDHModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Department Head</h3>
        <form action="{{ route('request_for_payments.approve_dh', $rfp->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="dh_latitude">
            <input type="hidden" name="longitude" id="dh_longitude">
            <input type="hidden" name="location" id="dh_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="dh_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveDHModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" id="dh_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Accounting Approval Modal -->
<div id="approveAccountingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Finance Manager</h3>
        <form action="{{ route('request_for_payments.approve_accounting', $rfp->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="acct_latitude">
            <input type="hidden" name="longitude" id="acct_longitude">
            <input type="hidden" name="location" id="acct_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="acct_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveAccountingModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" id="acct_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Executive Approval Modal -->
<div id="approveExecutiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Executive (CFO/President)</h3>
        <form action="{{ route('request_for_payments.approve', $rfp->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="exec_latitude">
            <input type="hidden" name="longitude" id="exec_longitude">
            <input type="hidden" name="location" id="exec_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="exec_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveExecutiveModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" id="exec_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Request for Payment</h3>
        <form action="{{ route('request_for_payments.reject', $rfp->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-500 mb-2">Rejection Reason (Optional):</label>
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
// Department Head Approval
function showApproveDHModal() {
    document.getElementById('approveDHModal').classList.remove('hidden');
    captureGeolocation('dh');
}
function closeApproveDHModal() {
    document.getElementById('approveDHModal').classList.add('hidden');
}

// Accounting Approval
function showApproveAccountingModal() {
    document.getElementById('approveAccountingModal').classList.remove('hidden');
    captureGeolocation('acct');
}
function closeApproveAccountingModal() {
    document.getElementById('approveAccountingModal').classList.add('hidden');
}

// Executive Approval
function showApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.remove('hidden');
    captureGeolocation('exec');
}
function closeApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.add('hidden');
}

// Reject
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Geolocation Capture
function enableSubmitButton(submitBtn, withLocation) {
    if (!submitBtn) return;
    submitBtn.disabled = false;
    if (withLocation) {
        submitBtn.className = 'bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700';
        submitBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Approval';
    } else {
        submitBtn.className = 'bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700';
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Approve Without Location';
    }
}

function captureGeolocationByIP(prefix, statusEl, submitBtn) {
    statusEl.textContent = 'Using IP-based location as fallback...';
    statusEl.className = 'text-sm text-blue-700';
    fetch('https://ipapi.co/json/')
        .then(response => response.json())
        .then(data => {
            if (data.latitude && data.longitude) {
                document.getElementById(prefix + '_latitude').value = data.latitude;
                document.getElementById(prefix + '_longitude').value = data.longitude;
                document.getElementById(prefix + '_location').value = data.city || data.region || 'Unknown';
                statusEl.textContent = 'Location captured (IP-based): ' + data.latitude + ', ' + data.longitude + ' (' + (data.city || 'Unknown') + ')';
                statusEl.className = 'text-sm text-green-700';
                enableSubmitButton(submitBtn, true);
            } else {
                statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
                statusEl.className = 'text-sm text-yellow-700';
                enableSubmitButton(submitBtn, false);
            }
        })
        .catch(() => {
            statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
            statusEl.className = 'text-sm text-yellow-700';
            enableSubmitButton(submitBtn, false);
        });
}

function captureGeolocation(prefix) {
    const statusEl = document.getElementById(prefix + '_geolocation_status');
    const submitBtn = document.getElementById(prefix + '_submit_btn');
    if (!navigator.geolocation) {
        captureGeolocationByIP(prefix, statusEl, submitBtn);
        return;
    }
    statusEl.textContent = 'Capturing location...';
    statusEl.className = 'text-sm text-blue-700';
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            document.getElementById(prefix + '_latitude').value = lat;
            document.getElementById(prefix + '_longitude').value = lng;
            getLocationName(lat, lng, prefix);
            statusEl.textContent = 'Location captured: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
            statusEl.className = 'text-sm text-green-700';
            enableSubmitButton(submitBtn, true);
        },
        function(error) {
            captureGeolocationByIP(prefix, statusEl, submitBtn);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function getLocationName(lat, lng, prefix) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
        .then(response => response.json())
        .then(data => {
            if (data.address) {
                const locationName = data.address.city || data.address.town || data.address.village || 'Unknown';
                document.getElementById(prefix + '_location').value = locationName;
            }
        })
        .catch(() => {
            document.getElementById(prefix + '_location').value = 'Location: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
        });
}
</script>
@endsection