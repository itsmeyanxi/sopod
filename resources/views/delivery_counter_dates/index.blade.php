@extends('layouts.app')

@section('title', 'Delivery Counter Dates')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Delivery Counter Dates</h1>
                <p class="text-sm text-gray-500 mt-1">Set counter dates for deliveries to track aging</p>
            </div>
            <button type="button" id="bulkSaveBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-md font-medium transition hidden">
                <i class="fas fa-save mr-1"></i> Save All Changes (<span id="changeCount">0</span>)
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 font-semibold uppercase">Total Delivered</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($totalDelivered) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                <p class="text-xs text-gray-500 font-semibold uppercase">With Counter Date</p>
                <p class="text-2xl font-bold text-green-700">{{ number_format($withCounter) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-orange-500">
                <p class="text-xs text-gray-500 font-semibold uppercase">Without Counter Date</p>
                <p class="text-2xl font-bold text-orange-700">{{ number_format($withoutCounter) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <form method="GET" action="{{ route('delivery_counter_dates.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 font-semibold mb-1">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Customer, DR No., Invoice..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 font-semibold mb-1">Filter</label>
                    <select name="filter" class="bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-gray-700">
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All Deliveries</option>
                        <option value="without_counter" {{ $filter === 'without_counter' ? 'selected' : '' }}>Without Counter Date</option>
                        <option value="with_counter" {{ $filter === 'with_counter' ? 'selected' : '' }}>With Counter Date</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 font-semibold mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-gray-700">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 font-semibold mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-gray-700">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if($search || $filter !== 'all' || $dateFrom || $dateTo)
                <a href="{{ route('delivery_counter_dates.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md font-medium transition">Clear</a>
                @endif
            </form>
        </div>

        <!-- Bulk date setter -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 flex items-center gap-3">
            <i class="fas fa-calendar-alt text-blue-600"></i>
            <span class="text-sm text-blue-800 font-medium">Bulk Set:</span>
            <input type="date" id="bulkDate" class="bg-white border border-blue-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:ring-2 focus:ring-blue-500">
            <button type="button" id="applyBulkSelected" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-medium transition">
                Apply to Selected
            </button>
            <button type="button" id="applyBulkEmpty" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded text-xs font-medium transition">
                Apply to All Empty
            </button>
            <div class="ml-auto flex items-center gap-2">
                <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                <label for="selectAll" class="text-xs text-gray-600">Select All</label>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-red-800 text-white">
                            <th class="px-3 py-3 text-center w-10">
                                <input type="checkbox" id="selectAllHeader" class="rounded border-gray-300">
                            </th>
                            <th class="px-3 py-3 text-left font-semibold">DR No.</th>
                            <th class="px-3 py-3 text-left font-semibold">Invoice No.</th>
                            <th class="px-3 py-3 text-left font-semibold">Customer</th>
                            <th class="px-3 py-3 text-left font-semibold">Branch</th>
                            <th class="px-3 py-3 text-center font-semibold">Delivery Date</th>
                            <th class="px-3 py-3 text-center font-semibold">Status</th>
                            <th class="px-3 py-3 text-center font-semibold" style="min-width:160px;">Counter Date</th>
                            <th class="px-3 py-3 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveries as $delivery)
                        <tr class="border-b border-gray-100 hover:bg-red-50 transition" data-id="{{ $delivery->id }}" data-dr="{{ $delivery->dr_no }}">
                            <td class="px-3 py-2.5 text-center">
                                <input type="checkbox" class="row-check rounded border-gray-300" value="{{ $delivery->id }}">
                            </td>
                            <td class="px-3 py-2.5 font-mono text-xs text-gray-800 font-medium">{{ $delivery->dr_no ?? '—' }}</td>
                            <td class="px-3 py-2.5 font-mono text-xs text-gray-600">{{ $delivery->sales_invoice_no ?? '—' }}</td>
                            <td class="px-3 py-2.5">
                                <div class="font-semibold text-gray-800">{{ $delivery->customer_name }}</div>
                                <div class="text-xs text-gray-400">{{ $delivery->customer_code }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $delivery->branch ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-center text-gray-600">
                                {{ $delivery->request_delivery_date ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('m/d/Y') : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">{{ $delivery->status }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <input type="date" class="counter-date-input bg-gray-50 border border-gray-200 rounded px-2 py-1 text-sm text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full"
                                    value="{{ $delivery->counter_date ? \Carbon\Carbon::parse($delivery->counter_date)->format('Y-m-d') : '' }}"
                                    data-original="{{ $delivery->counter_date ? \Carbon\Carbon::parse($delivery->counter_date)->format('Y-m-d') : '' }}"
                                    data-id="{{ $delivery->id }}">
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                @if($delivery->counter_date)
                                <button type="button" class="clear-btn text-red-500 hover:text-red-700 text-xs" data-id="{{ $delivery->id }}">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-truck text-4xl mb-2"></i>
                                <p>No delivered items found.</p>
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
    const pendingChanges = {};

    // Track changes on counter date inputs
    document.querySelectorAll('.counter-date-input').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.dataset.id;
            const original = this.dataset.original;
            const newVal = this.value;

            if (newVal !== original && newVal) {
                pendingChanges[id] = newVal;
                this.classList.add('border-blue-500', 'bg-blue-50');
                this.classList.remove('border-gray-200', 'bg-gray-50');
            } else {
                delete pendingChanges[id];
                this.classList.remove('border-blue-500', 'bg-blue-50');
                this.classList.add('border-gray-200', 'bg-gray-50');
            }

            updateBulkButton();
        });
    });

    function updateBulkButton() {
        const count = Object.keys(pendingChanges).length;
        const btn = document.getElementById('bulkSaveBtn');
        document.getElementById('changeCount').textContent = count;
        btn.classList.toggle('hidden', count === 0);
    }

    // Bulk save
    document.getElementById('bulkSaveBtn').addEventListener('click', async function() {
        const updates = Object.entries(pendingChanges).map(([id, counter_date]) => ({ id: parseInt(id), counter_date }));

        if (!updates.length) return;

        Swal.fire({ title: 'Saving...', text: `Updating ${updates.length} counter date(s)`, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch("{{ route('delivery_counter_dates.bulkUpdate') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ updates })
            });

            const data = await res.json();
            Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 2000, showConfirmButton: false });

            // Update originals and reset styles
            updates.forEach(u => {
                const input = document.querySelector(`.counter-date-input[data-id="${u.id}"]`);
                if (input) {
                    input.dataset.original = u.counter_date;
                    input.classList.remove('border-blue-500', 'bg-blue-50');
                    input.classList.add('border-gray-200', 'bg-gray-50');
                }
            });

            Object.keys(pendingChanges).forEach(k => delete pendingChanges[k]);
            updateBulkButton();

            // Reload to update age column
            setTimeout(() => location.reload(), 1500);
        } catch (e) {
            Swal.fire('Error', 'Failed to save counter dates.', 'error');
        }
    });

    // Select all checkboxes
    function syncSelectAll(checked) {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = checked);
    }
    document.getElementById('selectAll').addEventListener('change', function() { syncSelectAll(this.checked); document.getElementById('selectAllHeader').checked = this.checked; });
    document.getElementById('selectAllHeader').addEventListener('change', function() { syncSelectAll(this.checked); document.getElementById('selectAll').checked = this.checked; });

    // Apply bulk date to selected
    document.getElementById('applyBulkSelected').addEventListener('click', function() {
        const date = document.getElementById('bulkDate').value;
        if (!date) { Swal.fire('Select a Date', 'Please pick a counter date first.', 'warning'); return; }

        const checked = document.querySelectorAll('.row-check:checked');
        if (!checked.length) { Swal.fire('No Selection', 'Please select at least one delivery.', 'warning'); return; }

        checked.forEach(cb => {
            const row = cb.closest('tr');
            const input = row.querySelector('.counter-date-input');
            if (input) {
                input.value = date;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    // Apply bulk date to all empty
    document.getElementById('applyBulkEmpty').addEventListener('click', function() {
        const date = document.getElementById('bulkDate').value;
        if (!date) { Swal.fire('Select a Date', 'Please pick a counter date first.', 'warning'); return; }

        document.querySelectorAll('.counter-date-input').forEach(input => {
            if (!input.dataset.original) {
                input.value = date;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    // Clear individual counter date
    document.querySelectorAll('.clear-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const result = await Swal.fire({ title: 'Clear Counter Date?', text: 'This will remove the counter date from this delivery and its aging record.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, clear it' });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`/delivery-counter-dates/${id}/clear`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });

                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Cleared!', text: data.message, timer: 1500, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1200);
                }
            } catch (e) {
                Swal.fire('Error', 'Failed to clear counter date.', 'error');
            }
        });
    });
});
</script>
@endsection
