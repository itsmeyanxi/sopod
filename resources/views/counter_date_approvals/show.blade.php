@extends('layouts.app')

@section('title', 'Counter Date Approval — DR ' . ($delivery->dr_no ?? 'N/A'))

@section('content')
<div class="p-6 bg-gray-900 min-h-screen">
    <div class="max-w-5xl mx-auto">
        <!-- Back + Actions -->
        <div class="mb-4 flex justify-between items-center flex-wrap gap-3">
            <a href="{{ route('counter_date_approvals.index') }}" class="text-sm text-gray-300 hover:text-gray-200">
                <i class="fas fa-arrow-left mr-1"></i> Back to Counter Date Approval
            </a>
            <div class="flex items-center gap-2 flex-wrap">
                @if(!$delivery->counter_date_approved)
                <button type="button" id="approveBtn" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-md font-medium transition">
                    <i class="fas fa-check mr-1"></i> Approve Counter Date
                </button>
                @else
                <span class="px-4 py-2 bg-green-100 text-green-700 rounded-md text-sm font-semibold">
                    <i class="fas fa-check-circle mr-1"></i> Approved by {{ $delivery->counter_date_approved_by }}
                    on {{ \Carbon\Carbon::parse($delivery->counter_date_approved_at)->format('M d, Y h:i A') }}
                </span>
                @endif

                @if(auth()->user()->isAdminUser())
                <button type="button" id="directEditBtn" class="bg-yellow-600 hover:bg-yellow-700 text-white px-5 py-2 rounded-md font-medium transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </button>
                @else
                <button type="button" id="requestEditBtn" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded-md font-medium transition">
                    <i class="fas fa-pen mr-1"></i> Request Edit
                </button>
                @endif
            </div>
        </div>

        @if(auth()->user()->isAdminUser() && $pendingEditRequests->count() > 0)
        <!-- Pending Edit Requests (IT only) -->
        <div class="bg-purple-900/30 border border-purple-500/50 rounded-lg p-4 mb-4">
            <h3 class="font-bold text-purple-300 mb-3 flex items-center gap-2">
                <i class="fas fa-inbox"></i> Pending Edit Requests
                <span class="bg-purple-500 text-white text-xs rounded-full px-2 py-0.5">{{ $pendingEditRequests->count() }}</span>
            </h3>
            <div class="space-y-3">
                @foreach($pendingEditRequests as $req)
                <div class="bg-gray-800 rounded-lg p-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-sm font-semibold text-white">{{ $req->requested_by }}</span>
                            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($req->requested_at)->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-gray-400">{{ $req->old_counter_date ? \Carbon\Carbon::parse($req->old_counter_date)->format('m/d/Y') : '—' }}</span>
                            <i class="fas fa-arrow-right text-gray-500 text-xs"></i>
                            <span class="text-yellow-300 font-semibold">{{ \Carbon\Carbon::parse($req->new_counter_date)->format('m/d/Y') }}</span>
                        </div>
                        @if($req->reason)
                        <p class="text-xs text-gray-400 italic">"{{ $req->reason }}"</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="approve-edit-req-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs font-medium transition"
                            data-req-id="{{ $req->id }}" data-new-date="{{ $req->new_counter_date }}" data-name="{{ $req->requested_by }}">
                            <i class="fas fa-check mr-1"></i>Approve
                        </button>
                        <button type="button" class="reject-edit-req-btn bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-medium transition"
                            data-req-id="{{ $req->id }}" data-name="{{ $req->requested_by }}">
                            <i class="fas fa-times mr-1"></i>Reject
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Main Card -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-700 bg-red-800">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-xl font-bold text-white">Delivery Details</h1>
                        <p class="text-red-200 text-sm mt-0.5">DR No: {{ $delivery->dr_no ?? '—' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 rounded text-xs font-semibold
                            {{ $delivery->counter_date_approved ? 'bg-green-500 text-white' : 'bg-orange-400 text-white' }}">
                            {{ $delivery->counter_date_approved ? 'Approved' : 'Pending Approval' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Counter Date Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-5">
                    <h3 class="font-bold text-blue-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-calendar-alt"></i> Counter Date Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Counter Date</p>
                            <p class="text-lg font-bold text-blue-800">
                                {{ $delivery->counter_date ? \Carbon\Carbon::parse($delivery->counter_date)->format('F d, Y') : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Status</p>
                            @if($delivery->counter_date_approved)
                                <p class="text-sm font-semibold text-green-700"><i class="fas fa-check-circle mr-1"></i>Approved</p>
                                <p class="text-xs text-gray-300 mt-0.5">by {{ $delivery->counter_date_approved_by }}</p>
                                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($delivery->counter_date_approved_at)->format('M d, Y h:i A') }}</p>
                            @else
                                <p class="text-sm font-semibold text-orange-600"><i class="fas fa-clock mr-1"></i>Pending Approval</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Aging Record</p>
                            @if($arAging)
                                <p class="text-sm font-semibold text-green-700"><i class="fas fa-link mr-1"></i>Linked</p>
                                <p class="text-xs text-gray-300 mt-0.5">Aging Date: {{ $arAging->aging_date ? \Carbon\Carbon::parse($arAging->aging_date)->format('M d, Y') : '—' }}</p>
                            @else
                                <p class="text-sm text-gray-400">Not yet in Aging Reports</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div>
                    <h3 class="font-bold text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-truck text-gray-300"></i> Delivery Information
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">DR Number</p>
                            <p class="text-sm font-mono font-bold text-white">{{ $delivery->dr_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Sales Invoice No.</p>
                            <p class="text-sm font-mono text-gray-200">{{ $delivery->sales_invoice_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Sales Order No.</p>
                            <p class="text-sm font-mono text-gray-200">{{ $delivery->sales_order_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Delivery Batch</p>
                            <p class="text-sm text-gray-200">{{ $delivery->delivery_batch ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Customer Name</p>
                            <p class="text-sm font-semibold text-white">{{ $delivery->customer_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Customer Code</p>
                            <p class="text-sm text-gray-200">{{ $delivery->customer_code ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Branch</p>
                            <p class="text-sm text-gray-200">{{ $delivery->branch ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Status</p>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">{{ $delivery->status }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Delivery Date</p>
                            <p class="text-sm text-gray-200">{{ $delivery->request_delivery_date ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('F d, Y') : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Sales Executive</p>
                            <p class="text-sm text-gray-200">{{ $delivery->sales_executive ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">Plate No.</p>
                            <p class="text-sm text-gray-200">{{ $delivery->plate_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-300 font-semibold uppercase mb-1">PO Number</p>
                            <p class="text-sm text-gray-200">{{ $delivery->po_number ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Uploaded Document -->
                <div>
                    <h3 class="font-bold text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-paperclip text-gray-300"></i> Supporting Document
                    </h3>
                    @if($delivery->counter_date_attachment_path)
                        <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                            <div class="flex items-center gap-4">
                                @php
                                    $ext = strtolower(pathinfo($delivery->counter_date_attachment_name, PATHINFO_EXTENSION));
                                    $isPdf = $ext === 'pdf';
                                    $isImage = in_array($ext, ['png', 'jpg', 'jpeg']);
                                @endphp
                                @if($isPdf)
                                    <i class="fas fa-file-pdf text-4xl text-red-500"></i>
                                @else
                                    <i class="fas fa-file-image text-4xl text-blue-500"></i>
                                @endif
                                <div class="flex-1">
                                    <p class="font-semibold text-white">{{ $delivery->counter_date_attachment_name }}</p>
                                    <p class="text-xs text-gray-400">Click to view or download</p>
                                </div>
                                <a href="{{ asset('storage/' . $delivery->counter_date_attachment_path) }}" target="_blank"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition">
                                    <i class="fas fa-external-link-alt mr-1"></i> Open
                                </a>
                            </div>

                            @if($isImage)
                            <div class="mt-4 border border-gray-700 rounded overflow-hidden">
                                <img src="{{ asset('storage/' . $delivery->counter_date_attachment_path) }}" alt="Attachment" class="max-w-full max-h-96 mx-auto">
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 text-center">
                            <i class="fas fa-file-upload text-3xl text-gray-300 mb-2"></i>
                            <p class="text-gray-400 text-sm">No document uploaded</p>
                            @if(!$delivery->counter_date_approved)
                            <label class="inline-block mt-3 cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition">
                                <i class="fas fa-upload mr-1"></i> Upload Document
                                <input type="file" class="hidden" id="uploadInput" accept=".pdf,.png,.jpg,.jpeg">
                            </label>
                            <p class="text-xs text-gray-400 mt-1">PDF, PNG, JPG (max 5MB)</p>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Delivery Items -->
                @if($delivery->items && $delivery->items->count() > 0)
                <div>
                    <h3 class="font-bold text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-boxes text-gray-300"></i> Delivery Items
                        <span class="text-xs bg-gray-600 text-gray-300 px-2 py-0.5 rounded-full">{{ $delivery->items->count() }}</span>
                    </h3>
                    <div class="overflow-x-auto border border-gray-700 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-700 text-gray-300">
                                    <th class="px-3 py-2 text-left text-xs font-semibold">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold">Item Code</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold">Description</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold">Brand</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold">Quantity</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold">UOM</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold">Unit Price</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalAmount = 0; @endphp
                                @foreach($delivery->items as $idx => $item)
                                <tr class="border-t border-gray-100 hover:bg-gray-900">
                                    <td class="px-3 py-2 text-gray-300">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-2 font-mono text-xs text-blue-700">{{ $item->item_code }}</td>
                                    <td class="px-3 py-2 text-white">{{ $item->item_description ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-300">{{ $item->brand ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-white">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="px-3 py-2 text-center text-gray-300">{{ $item->uom ?? 'Kgs' }}</td>
                                    <td class="px-3 py-2 text-right text-gray-200">₱{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-white">₱{{ number_format($item->total_amount, 2) }}</td>
                                </tr>
                                @php $totalAmount += $item->total_amount; @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-700 font-bold">
                                    <td colspan="7" class="px-3 py-2 text-right text-gray-300">Total:</td>
                                    <td class="px-3 py-2 text-right text-green-700">₱{{ number_format($totalAmount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                @if($delivery->additional_instructions)
                <div>
                    <h3 class="font-bold text-white mb-2"><i class="fas fa-sticky-note text-gray-300 mr-1"></i> Additional Instructions</h3>
                    <div class="bg-gray-900 border border-gray-700 rounded p-3 text-sm text-gray-200">{{ $delivery->additional_instructions }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Direct Edit Modal -->
<div id="directEditModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-white mb-1"><i class="fas fa-edit text-yellow-400 mr-2"></i>Edit Counter Date</h3>
        <p class="text-xs text-gray-400 mb-4">DR No: <span class="font-mono text-white">{{ $delivery->dr_no }}</span></p>
        <div class="mb-4">
            <label class="block text-xs text-gray-300 font-semibold mb-1">Current Counter Date</label>
            <p class="text-sm text-gray-200">{{ $delivery->counter_date ? \Carbon\Carbon::parse($delivery->counter_date)->format('F d, Y') : '—' }}</p>
        </div>
        <div class="mb-4">
            <label class="block text-xs text-gray-300 font-semibold mb-1">New Counter Date <span class="text-red-400">*</span></label>
            <input type="date" id="directEditDate" value="{{ $delivery->counter_date }}" class="w-full bg-gray-900 border border-gray-600 rounded-md px-3 py-2 text-gray-100 focus:ring-2 focus:ring-yellow-500">
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="directEditCancel" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded text-sm font-medium transition">Cancel</button>
            <button type="button" id="directEditConfirm" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded text-sm font-medium transition">
                <i class="fas fa-save mr-1"></i>Save
            </button>
        </div>
    </div>
</div>

<!-- Request Edit Modal -->
<div id="requestEditModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-white mb-1"><i class="fas fa-pen text-orange-400 mr-2"></i>Request Counter Date Edit</h3>
        <p class="text-xs text-gray-400 mb-4">DR No: <span class="font-mono text-white">{{ $delivery->dr_no }}</span> — Requires IT approval</p>
        <div class="mb-4">
            <label class="block text-xs text-gray-300 font-semibold mb-1">Current Counter Date</label>
            <p class="text-sm text-gray-200">{{ $delivery->counter_date ? \Carbon\Carbon::parse($delivery->counter_date)->format('F d, Y') : '—' }}</p>
        </div>
        <div class="mb-4">
            <label class="block text-xs text-gray-300 font-semibold mb-1">New Counter Date <span class="text-red-400">*</span></label>
            <input type="date" id="requestEditDate" class="w-full bg-gray-900 border border-gray-600 rounded-md px-3 py-2 text-gray-100 focus:ring-2 focus:ring-orange-500">
        </div>
        <div class="mb-4">
            <label class="block text-xs text-gray-300 font-semibold mb-1">Reason</label>
            <textarea id="requestEditReason" rows="3" placeholder="Why does this date need to change?" class="w-full bg-gray-900 border border-gray-600 rounded-md px-3 py-2 text-gray-100 focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="requestEditCancel" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded text-sm font-medium transition">Cancel</button>
            <button type="button" id="requestEditConfirm" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded text-sm font-medium transition">
                <i class="fas fa-paper-plane mr-1"></i>Submit Request
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const deliveryId = {{ $delivery->id }};
    const drNo = '{{ $delivery->dr_no }}';

    // Approve button
    const approveBtn = document.getElementById('approveBtn');
    if (approveBtn) {
        approveBtn.addEventListener('click', async function() {
            const result = await Swal.fire({
                title: 'Approve Counter Date?',
                html: `This will approve the counter date for <strong>DR ${drNo}</strong> and add it to the Aging Reports.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Yes, Approve'
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`/counter-date-approvals/${deliveryId}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });

                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Approved!', text: data.message, timer: 2000, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Failed to approve.', 'error');
            }
        });
    }

    // ===== DIRECT EDIT (IT only) =====
    const directEditBtn = document.getElementById('directEditBtn');
    if (directEditBtn) {
        directEditBtn.addEventListener('click', () => document.getElementById('directEditModal').classList.remove('hidden'));
    }
    const directEditCancel = document.getElementById('directEditCancel');
    if (directEditCancel) directEditCancel.addEventListener('click', () => document.getElementById('directEditModal').classList.add('hidden'));
    const directEditConfirm = document.getElementById('directEditConfirm');
    if (directEditConfirm) {
        directEditConfirm.addEventListener('click', async function() {
            const newDate = document.getElementById('directEditDate').value;
            if (!newDate) { Swal.fire('Required', 'Please select a new counter date.', 'warning'); return; }
            document.getElementById('directEditModal').classList.add('hidden');
            Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(`/counter-date-approvals/${deliveryId}/direct-edit`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ new_counter_date: newDate })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, timer: 2000, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch(e) { Swal.fire('Error', 'Failed to update.', 'error'); }
        });
    }

    // ===== REQUEST EDIT (non-IT) =====
    const requestEditBtn = document.getElementById('requestEditBtn');
    if (requestEditBtn) {
        requestEditBtn.addEventListener('click', () => document.getElementById('requestEditModal').classList.remove('hidden'));
    }
    const requestEditCancel = document.getElementById('requestEditCancel');
    if (requestEditCancel) requestEditCancel.addEventListener('click', () => document.getElementById('requestEditModal').classList.add('hidden'));
    const requestEditConfirm = document.getElementById('requestEditConfirm');
    if (requestEditConfirm) {
        requestEditConfirm.addEventListener('click', async function() {
            const newDate = document.getElementById('requestEditDate').value;
            const reason = document.getElementById('requestEditReason').value;
            if (!newDate) { Swal.fire('Required', 'Please select a new counter date.', 'warning'); return; }
            document.getElementById('requestEditModal').classList.add('hidden');
            Swal.fire({ title: 'Submitting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(`/counter-date-approvals/${deliveryId}/request-edit`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ new_counter_date: newDate, reason })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Request Submitted!', text: data.message, timer: 2500, showConfirmButton: false });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch(e) { Swal.fire('Error', 'Failed to submit request.', 'error'); }
        });
    }

    // ===== APPROVE EDIT REQUEST (IT only) =====
    document.querySelectorAll('.approve-edit-req-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const reqId = this.dataset.reqId;
            const newDate = this.dataset.newDate;
            const name = this.dataset.name;
            const result = await Swal.fire({
                title: 'Approve Edit Request?',
                html: `This will change the counter date to <strong>${newDate}</strong> as requested by <strong>${name}</strong>.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Yes, Approve'
            });
            if (!result.isConfirmed) return;
            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(`/counter-date-approvals/edit-requests/${reqId}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Approved!', text: data.message, timer: 2000, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch(e) { Swal.fire('Error', 'Failed to approve.', 'error'); }
        });
    });

    // ===== REJECT EDIT REQUEST (IT only) =====
    document.querySelectorAll('.reject-edit-req-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const reqId = this.dataset.reqId;
            const name = this.dataset.name;
            const result = await Swal.fire({
                title: 'Reject Edit Request?',
                input: 'textarea',
                inputLabel: 'Reason for rejection (optional)',
                inputPlaceholder: 'Enter reason...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Reject'
            });
            if (!result.isConfirmed) return;
            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(`/counter-date-approvals/edit-requests/${reqId}/reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ review_notes: result.value })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Rejected', text: data.message, timer: 2000, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch(e) { Swal.fire('Error', 'Failed to reject.', 'error'); }
        });
    });

    // Upload from show page
    const uploadInput = document.getElementById('uploadInput');
    if (uploadInput) {
        uploadInput.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            const allowed = ['application/pdf', 'image/png', 'image/jpeg'];
            if (!allowed.includes(file.type)) {
                Swal.fire('Invalid File', 'Only PDF, PNG, and JPG files are allowed.', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('File Too Large', 'Maximum file size is 5MB.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('attachment', file);

            Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const res = await fetch(`/counter-date-approvals/${deliveryId}/upload`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData
                });

                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Uploaded!', text: data.message, timer: 1500, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1200);
                }
            } catch (e) {
                Swal.fire('Error', 'Upload failed.', 'error');
            }
        });
    }
});
</script>
@endsection
