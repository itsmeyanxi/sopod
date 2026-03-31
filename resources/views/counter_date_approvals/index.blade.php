@extends('layouts.app')

@section('title', 'Counter Date Approval')

@section('content')
<div class="p-6 bg-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">Counter Date Approval</h1>
                <p class="text-sm text-gray-300 mt-1">Review and approve delivery counter dates for aging reports</p>
            </div>
            <button type="button" id="bulkApproveBtn" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-md font-medium transition hidden">
                <i class="fas fa-check-double mr-1"></i> Approve Selected (<span id="selectedCount">0</span>)
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-300 font-semibold uppercase">Total with Counter Date</p>
                <p class="text-2xl font-bold text-white">{{ number_format($totalWithCounter) }}</p>
            </div>
            <div class="bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-orange-500">
                <p class="text-xs text-gray-300 font-semibold uppercase">Pending Approval</p>
                <p class="text-2xl font-bold text-orange-700">{{ number_format($pendingCount) }}</p>
            </div>
            <div class="bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                <p class="text-xs text-gray-300 font-semibold uppercase">Approved</p>
                <p class="text-2xl font-bold text-green-700">{{ number_format($approvedCount) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
            <form method="GET" action="{{ route('counter_date_approvals.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-300 font-semibold mb-1">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Customer, DR No., Invoice..."
                        class="w-full bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs text-gray-300 font-semibold mb-1">Status</label>
                    <select name="filter" class="bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-gray-200">
                        <option value="pending" {{ $filter === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ $filter === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if($search || $filter !== 'pending')
                <a href="{{ route('counter_date_approvals.index') }}" class="bg-gray-600 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded-md font-medium transition">Clear</a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-red-800 text-white">
                            <th class="px-3 py-3 text-center w-10">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-600">
                            </th>
                            <th class="px-3 py-3 text-left font-semibold">DR No.</th>
                            <th class="px-3 py-3 text-left font-semibold">Invoice No.</th>
                            <th class="px-3 py-3 text-left font-semibold">Customer</th>
                            <th class="px-3 py-3 text-left font-semibold">Branch</th>
                            <th class="px-3 py-3 text-center font-semibold">Delivery Date</th>
                            <th class="px-3 py-3 text-center font-semibold">Counter Date</th>
                            <th class="px-3 py-3 text-center font-semibold">Attachment</th>
                            <th class="px-3 py-3 text-center font-semibold">Status</th>
                            <th class="px-3 py-3 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveries as $delivery)
                        <tr class="border-b border-gray-100 hover:bg-red-50 transition" data-id="{{ $delivery->id }}">
                            <td class="px-3 py-2.5 text-center">
                                @if(!$delivery->counter_date_approved)
                                <input type="checkbox" class="row-check rounded border-gray-600" value="{{ $delivery->id }}">
                                @endif
                            </td>
                            <td class="px-3 py-2.5 font-mono text-xs text-white font-medium">{{ $delivery->dr_no ?? '—' }}</td>
                            <td class="px-3 py-2.5 font-mono text-xs text-gray-300">{{ $delivery->sales_invoice_no ?? '—' }}</td>
                            <td class="px-3 py-2.5">
                                <div class="font-semibold text-white">{{ $delivery->customer_name }}</div>
                                <div class="text-xs text-gray-400">{{ $delivery->customer_code }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-gray-300">{{ $delivery->branch ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-center text-gray-300">
                                {{ $delivery->request_delivery_date ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('m/d/Y') : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                    {{ \Carbon\Carbon::parse($delivery->counter_date)->format('m/d/Y') }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                @if($delivery->counter_date_attachment_path)
                                    <a href="{{ asset('storage/' . $delivery->counter_date_attachment_path) }}" target="_blank"
                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        <i class="fas fa-paperclip mr-1"></i>{{ $delivery->counter_date_attachment_name }}
                                    </a>
                                @else
                                    @if(!$delivery->counter_date_approved)
                                    <label class="cursor-pointer text-gray-400 hover:text-blue-600 text-xs transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                        <input type="file" class="hidden upload-input" data-id="{{ $delivery->id }}" accept=".pdf,.png,.jpg,.jpeg">
                                    </label>
                                    @else
                                    <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                @if($delivery->counter_date_approved)
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">
                                        <i class="fas fa-check mr-1"></i>Approved
                                    </span>
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        {{ $delivery->counter_date_approved_by }}<br>
                                        {{ \Carbon\Carbon::parse($delivery->counter_date_approved_at)->format('m/d/Y h:i A') }}
                                    </div>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-700">Pending</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('counter_date_approvals.show', $delivery->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                    @if(!$delivery->counter_date_approved)
                                    <button type="button" class="approve-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-medium transition"
                                        data-id="{{ $delivery->id }}" data-dr="{{ $delivery->dr_no }}">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-clipboard-check text-4xl mb-2"></i>
                                <p>No deliveries with counter dates found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($deliveries->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $deliveries->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Select all
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });

    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    function updateSelectedCount() {
        const count = document.querySelectorAll('.row-check:checked').length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkApproveBtn').classList.toggle('hidden', count === 0);
    }

    // Single approve
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const dr = this.dataset.dr;

            const result = await Swal.fire({
                title: 'Approve Counter Date?',
                html: `This will approve the counter date for <strong>DR ${dr}</strong> and add it to the Aging Reports.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Yes, Approve'
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`/counter-date-approvals/${id}/approve`, {
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
    });

    // Bulk approve
    document.getElementById('bulkApproveBtn').addEventListener('click', async function() {
        const ids = [...document.querySelectorAll('.row-check:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;

        const result = await Swal.fire({
            title: `Approve ${ids.length} Counter Date(s)?`,
            text: 'All selected deliveries will be approved and added to Aging Reports.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: 'Yes, Approve All'
        });

        if (!result.isConfirmed) return;

        Swal.fire({ title: 'Approving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch("{{ route('counter_date_approvals.bulkApprove') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ ids })
            });

            const data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Approved!', text: data.message, timer: 2000, showConfirmButton: false });
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Bulk approve failed.', 'error');
        }
    });

    // File upload
    document.querySelectorAll('.upload-input').forEach(input => {
        input.addEventListener('change', async function() {
            const id = this.dataset.id;
            const file = this.files[0];
            if (!file) return;

            const allowed = ['application/pdf', 'image/png', 'image/jpeg'];
            if (!allowed.includes(file.type)) {
                Swal.fire('Invalid File', 'Only PDF, PNG, and JPG files are allowed.', 'error');
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('File Too Large', 'Maximum file size is 5MB.', 'error');
                this.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('attachment', file);

            try {
                Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                const res = await fetch(`/counter-date-approvals/${id}/upload`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData
                });

                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Uploaded!', text: data.message, timer: 1500, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1200);
                } else {
                    Swal.fire('Error', data.message || 'Upload failed.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Upload failed.', 'error');
            }
        });
    });
});
</script>
@endsection
