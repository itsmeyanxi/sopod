@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Purchase Requests</h1>
            <div class="flex items-center gap-3">
                @if(auth()->user()->canApprovePurchaseRequests())
                    <button type="button" id="bulkApproveBtn"
                        onclick="submitBulkApprove()"
                        class="hidden bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                        <i class="fas fa-check-double mr-1"></i> Approve Selected (<span id="selectedCount">0</span>)
                    </button>
                @endif
                <a href="{{ route('purchase_requests.create') }}" class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition">
                    <i class="fas fa-plus mr-2"></i>Create New PR
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Bulk Approve Form (hidden) -->
        @if(auth()->user()->canApprovePurchaseRequests())
        <form id="bulkApproveForm" action="{{ route('purchase_requests.bulk_approve') }}" method="POST" class="hidden">
            @csrf
        </form>
        @endif

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-200">
                <thead class="bg-gray-100 text-gray-500 uppercase text-xs">
                    <tr>
                        @if(auth()->user()->canApprovePurchaseRequests())
                        <th class="border border-gray-200 px-3 py-3 w-10">
                            <input type="checkbox" id="selectAll" class="cursor-pointer" title="Select all pending">
                        </th>
                        @endif
                        <th class="border border-gray-200 px-4 py-3 text-left">PR No</th>
                        <th class="border border-gray-200 px-4 py-3 text-left">Company</th>
                        <th class="border border-gray-200 px-4 py-3 text-left">Requisitioner</th>
                        <th class="border border-gray-200 px-4 py-3 text-left">Date of Request</th>
                        <th class="border border-gray-200 px-4 py-3 text-left">Status</th>
                        <th class="border border-gray-200 px-4 py-3 text-left">Created By</th>
                        <th class="border border-gray-200 px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-500 divide-y divide-gray-700">
                    @forelse($purchaseRequests as $pr)
                        <tr class="hover:bg-gray-100/40 transition">
                            @if(auth()->user()->canApprovePurchaseRequests())
                            <td class="border border-gray-200 px-3 py-3 text-center">
                                @if($pr->status === 'pending')
                                    <input type="checkbox" name="ids[]" value="{{ $pr->id }}"
                                        class="pr-checkbox cursor-pointer" form="bulkApproveForm">
                                @endif
                            </td>
                            @endif
                            <td class="border border-gray-200 px-4 py-3">
                                @if($pr->status === 'approved')
                                    <a href="{{ route('purchase_requests.go_to_po', $pr->id) }}" class="text-blue-600 hover:underline font-semibold" title="Click to go to PO">
                                        {{ $pr->pr_no }}
                                    </a>
                                @else
                                    {{ $pr->pr_no }}
                                @endif
                            </td>
                            <td class="border border-gray-200 px-4 py-3">{{ $pr->company }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $pr->requisitioner }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $pr->date_of_request->format('M d, Y') }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($pr->status === 'pending') bg-yellow-600 text-white
                                    @elseif($pr->status === 'approved') bg-green-600 text-white
                                    @elseif($pr->status === 'rejected') bg-red-600 text-white
                                    @else bg-blue-600 text-white
                                    @endif">
                                    {{ ucfirst($pr->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-3">{{ $pr->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('purchase_requests.show', $pr->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">View</a>
                                    <a href="{{ route('purchase_requests.edit', $pr->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">Edit</a>
                                    <form action="{{ route('purchase_requests.destroy', $pr->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this PR?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canApprovePurchaseRequests() ? 8 : 7 }}" class="border border-gray-200 px-4 py-8 text-center text-gray-500">
                                No purchase requests found. <a href="{{ route('purchase_requests.create') }}" class="text-blue-700 hover:underline">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $purchaseRequests->links() }}
        </div>
    </div>
</div>

@if(auth()->user()->canApprovePurchaseRequests())
<script>
const selectAll = document.getElementById('selectAll');
const bulkApproveBtn = document.getElementById('bulkApproveBtn');
const selectedCount = document.getElementById('selectedCount');

function updateBulkBtn() {
    const checked = document.querySelectorAll('.pr-checkbox:checked').length;
    selectedCount.textContent = checked;
    if (checked > 0) {
        bulkApproveBtn.classList.remove('hidden');
    } else {
        bulkApproveBtn.classList.add('hidden');
    }
}

selectAll.addEventListener('change', function () {
    document.querySelectorAll('.pr-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkBtn();
});

document.querySelectorAll('.pr-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkBtn);
});

function submitBulkApprove() {
    const count = document.querySelectorAll('.pr-checkbox:checked').length;
    if (confirm(`Approve ${count} selected Purchase Request(s)?`)) {
        document.getElementById('bulkApproveForm').submit();
    }
}
</script>
@endif
@endsection
