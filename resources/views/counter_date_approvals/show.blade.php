@extends('layouts.app')

@section('title', 'Counter Date Approval — DR ' . ($delivery->dr_no ?? 'N/A'))

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto">
        <!-- Back + Actions -->
        <div class="mb-4 flex justify-between items-center">
            <a href="{{ route('counter_date_approvals.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to Counter Date Approval
            </a>
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
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-red-800">
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
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Counter Date</p>
                            <p class="text-lg font-bold text-blue-800">
                                {{ $delivery->counter_date ? \Carbon\Carbon::parse($delivery->counter_date)->format('F d, Y') : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Status</p>
                            @if($delivery->counter_date_approved)
                                <p class="text-sm font-semibold text-green-700"><i class="fas fa-check-circle mr-1"></i>Approved</p>
                                <p class="text-xs text-gray-500 mt-0.5">by {{ $delivery->counter_date_approved_by }}</p>
                                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($delivery->counter_date_approved_at)->format('M d, Y h:i A') }}</p>
                            @else
                                <p class="text-sm font-semibold text-orange-600"><i class="fas fa-clock mr-1"></i>Pending Approval</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Aging Record</p>
                            @if($arAging)
                                <p class="text-sm font-semibold text-green-700"><i class="fas fa-link mr-1"></i>Linked</p>
                                <p class="text-xs text-gray-500 mt-0.5">Aging Date: {{ $arAging->aging_date ? \Carbon\Carbon::parse($arAging->aging_date)->format('M d, Y') : '—' }}</p>
                            @else
                                <p class="text-sm text-gray-400">Not yet in Aging Reports</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div>
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-truck text-gray-500"></i> Delivery Information
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">DR Number</p>
                            <p class="text-sm font-mono font-bold text-gray-800">{{ $delivery->dr_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Sales Invoice No.</p>
                            <p class="text-sm font-mono text-gray-700">{{ $delivery->sales_invoice_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Sales Order No.</p>
                            <p class="text-sm font-mono text-gray-700">{{ $delivery->sales_order_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Delivery Batch</p>
                            <p class="text-sm text-gray-700">{{ $delivery->delivery_batch ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Customer Name</p>
                            <p class="text-sm font-semibold text-gray-800">{{ $delivery->customer_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Customer Code</p>
                            <p class="text-sm text-gray-700">{{ $delivery->customer_code ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Branch</p>
                            <p class="text-sm text-gray-700">{{ $delivery->branch ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Status</p>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">{{ $delivery->status }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Delivery Date</p>
                            <p class="text-sm text-gray-700">{{ $delivery->request_delivery_date ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('F d, Y') : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Sales Executive</p>
                            <p class="text-sm text-gray-700">{{ $delivery->sales_executive ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Plate No.</p>
                            <p class="text-sm text-gray-700">{{ $delivery->plate_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">PO Number</p>
                            <p class="text-sm text-gray-700">{{ $delivery->po_number ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Uploaded Document -->
                <div>
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-paperclip text-gray-500"></i> Supporting Document
                    </h3>
                    @if($delivery->counter_date_attachment_path)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
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
                                    <p class="font-semibold text-gray-800">{{ $delivery->counter_date_attachment_name }}</p>
                                    <p class="text-xs text-gray-400">Click to view or download</p>
                                </div>
                                <a href="{{ asset('storage/' . $delivery->counter_date_attachment_path) }}" target="_blank"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition">
                                    <i class="fas fa-external-link-alt mr-1"></i> Open
                                </a>
                            </div>

                            @if($isImage)
                            <div class="mt-4 border border-gray-200 rounded overflow-hidden">
                                <img src="{{ asset('storage/' . $delivery->counter_date_attachment_path) }}" alt="Attachment" class="max-w-full max-h-96 mx-auto">
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
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
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-boxes text-gray-500"></i> Delivery Items
                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">{{ $delivery->items->count() }}</span>
                    </h3>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600">
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
                                <tr class="border-t border-gray-100 hover:bg-gray-50">
                                    <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-2 font-mono text-xs text-blue-700">{{ $item->item_code }}</td>
                                    <td class="px-3 py-2 text-gray-800">{{ $item->item_description ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $item->brand ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-800">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="px-3 py-2 text-center text-gray-600">{{ $item->uom ?? 'Kgs' }}</td>
                                    <td class="px-3 py-2 text-right text-gray-700">₱{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-800">₱{{ number_format($item->total_amount, 2) }}</td>
                                </tr>
                                @php $totalAmount += $item->total_amount; @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td colspan="7" class="px-3 py-2 text-right text-gray-600">Total:</td>
                                    <td class="px-3 py-2 text-right text-green-700">₱{{ number_format($totalAmount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                @if($delivery->additional_instructions)
                <div>
                    <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-sticky-note text-gray-500 mr-1"></i> Additional Instructions</h3>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700">{{ $delivery->additional_instructions }}</div>
                </div>
                @endif
            </div>
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
