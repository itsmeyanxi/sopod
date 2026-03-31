@extends('layouts.app')

@section('title', 'View Check Voucher')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Action Buttons -->
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('check_vouchers.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
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
                @if(auth()->user()->canApproveAPVAsDH() || auth()->user()->canApproveCV())
                    <button type="button" onclick="confirmDelete()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                @endif
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

        <!-- Check Voucher Content -->
        <div id="printableVoucher" class="bg-gray-800 text-black p-8 rounded">
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
            @if(is_array($voucher->journal_entries) && count($voucher->journal_entries) > 0)
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
                    <p class="font-bold border-t border-black pt-1">{{ $voucher->creator->name ?? ($voucher->prepared_by ?? '___________________') }}</p>
                    @if($voucher->creator && $voucher->created_at)
                        <p class="text-xs text-gray-500 italic mt-1">
                            Digitally Signed<br>
                            {{ $voucher->created_at->format('d M Y | H:i') }}
                        </p>
                    @endif
                </div>
                <div class="text-center">
                    <p class="mb-8">Reviewed by:</p>
                    <p class="font-bold border-t border-black pt-1">{{ $voucher->accountingReviewer->name ?? ($voucher->reviewed_by ?? '___________________') }}</p>
                    @if($voucher->accountingReviewer && $voucher->accounting_reviewed_at)
                        <p class="text-xs text-gray-500 italic mt-1">
                            Digitally Signed<br>
                            {{ $voucher->accounting_reviewed_at->format('d M Y | H:i') }}
                            @if($voucher->accounting_reviewed_latitude && $voucher->accounting_reviewed_longitude)
                                <br>Coords: {{ $voucher->accounting_reviewed_latitude }}, {{ $voucher->accounting_reviewed_longitude }}
                                @if($voucher->accounting_reviewed_location) ({{ $voucher->accounting_reviewed_location }}) @endif
                            @endif
                        </p>
                    @endif
                </div>
                <div class="text-center">
                    <p class="mb-8">Approved by:</p>
                    <p class="font-bold border-t border-black pt-1">{{ $voucher->approvalUser->name ?? ($voucher->approved_by ?? 'ODM / FDM') }}</p>
                    @if($voucher->approvalUser && $voucher->approval_date)
                        <p class="text-xs text-gray-500 italic mt-1">
                            Digitally Signed<br>
                            {{ $voucher->approval_date->format('d M Y | H:i') }}
                            @if($voucher->approved_latitude && $voucher->approved_longitude)
                                <br>Coords: {{ $voucher->approved_latitude }}, {{ $voucher->approved_longitude }}
                                @if($voucher->approved_location) ({{ $voucher->approved_location }}) @endif
                            @endif
                        </p>
                    @endif
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
            <div class="text-center text-xs text-gray-300 border-t border-gray-600 pt-3">
                <p class="mb-1"><em>This is a system generated document. No signature is required.</em></p>
                <p class="mb-1"><em>This document is not valid for claiming input tax</em></p>
                <div class="flex justify-between mt-3">
                    <p>Print Date/Time: {{ now()->format('n/j/Y g:i:s A') }}</p>
                    <p>Page 1 of 1</p>
                </div>
            </div>
        </div>

        <!-- Approval Trail -->
        <div class="mb-6 mt-6 p-4 bg-gray-900 border border-gray-700 rounded">
            <h3 class="text-lg font-semibold text-white mb-4">Approval Trail</h3>
            <div class="space-y-3">
                <!-- Accounting Manager Review -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($voucher->accounting_reviewed_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-300">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Accounting Manager Review</span>
                            @if($voucher->accounting_reviewed_by && $voucher->accountingReviewer)
                                <span class="text-green-700">✓ Reviewed</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $voucher->accountingReviewer->name }}
                                    on {{ $voucher->accounting_reviewed_at->format('M d, Y h:i A') }}
                                </small>
                            @else
                                <span class="text-yellow-700">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- ODM/FDM Final Approval -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($voucher->status === 'approved' && $voucher->approvalUser)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($voucher->accounting_reviewed_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-300">
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
                            <span class="font-semibold">ODM/FDM Approval</span>
                            @if($voucher->status === 'approved' && $voucher->approvalUser)
                                <span class="text-green-700">✓ Approved</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $voucher->approvalUser->name }}
                                    on {{ $voucher->approval_date->format('M d, Y h:i A') }}
                                </small>
                            @elseif($voucher->accounting_reviewed_by)
                                <span class="text-yellow-700">Pending</span>
                            @else
                                <span class="text-gray-500">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($voucher->status === 'rejected')
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
                            @if($voucher->rejection_reason)
                                <p class="text-gray-500 mt-2">
                                    <strong>Reason:</strong> {{ $voucher->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Approval Buttons -->
        @if($voucher->status === 'pending' && $voucher->approval_stage !== 'rejected')
            <div class="flex gap-3 mb-4">
                @if($voucher->approval_stage === 'pending_accounting' && auth()->user()->canApproveCVAsAccounting())
                    <button type="button" onclick="showApproveAccountingModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Accounting Manager
                    </button>
                @endif

                @if($voucher->approval_stage === 'pending_odm' && auth()->user()->canApproveCV())
                    <button type="button" onclick="showApproveODMModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as ODM/FDM
                    </button>
                @endif

                @if(auth()->user()->canApproveCV())
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Accounting Manager Approval Modal -->
<div id="approveAccountingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Accounting Manager</h3>
        <form action="{{ route('check_vouchers.approve_accounting', $voucher->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="acct_latitude">
            <input type="hidden" name="longitude" id="acct_longitude">
            <input type="hidden" name="location" id="acct_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="acct_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveAccountingModal()" class="bg-gray-200 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" id="acct_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ODM/FDM Approval Modal -->
<div id="approveODMModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as ODM/FDM</h3>
        <form action="{{ route('check_vouchers.approve', $voucher->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="odm_latitude">
            <input type="hidden" name="longitude" id="odm_longitude">
            <input type="hidden" name="location" id="odm_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="odm_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveODMModal()" class="bg-gray-200 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" id="odm_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Check Voucher</h3>
        <form action="{{ route('check_vouchers.reject', $voucher->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-500 mb-2">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" rows="4"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-200 text-white px-4 py-2 rounded hover:bg-gray-700">
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
// Accounting Manager Approval
function showApproveAccountingModal() {
    document.getElementById('approveAccountingModal').classList.remove('hidden');
    captureGeolocation('acct');
}
function closeApproveAccountingModal() {
    document.getElementById('approveAccountingModal').classList.add('hidden');
}

// ODM/FDM Approval
function showApproveODMModal() {
    document.getElementById('approveODMModal').classList.remove('hidden');
    captureGeolocation('odm');
}
function closeApproveODMModal() {
    document.getElementById('approveODMModal').classList.add('hidden');
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
    .bg-gray-800, .bg-gray-700, .bg-gray-200 {
        background-color: white !important;
    }
    button, .no-print {
        display: none !important;
    }
}
</style>

<!-- Delete Confirmation Modal -->
@if(auth()->user()->canApproveAPVAsDH() || auth()->user()->canApproveCV())
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Delete Check Voucher</h3>
        <p class="text-gray-500 mb-4">Are you sure you want to delete check voucher <strong>{{ $voucher->cv_no }}</strong>? This action cannot be undone.</p>
        <form action="{{ route('check_vouchers.destroy', $voucher->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeDeleteModal()" class="bg-gray-200 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    <i class="fas fa-trash mr-1"></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
@endif
@endsection