@extends('layouts.app')

@section('title', 'View Purchase Request')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">PURCHASE REQUISITION FORM</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-300">PR NO:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $purchaseRequest->pr_no }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($purchaseRequest->status === 'pending') bg-yellow-600 text-white
                    @elseif($purchaseRequest->status === 'approved') bg-green-600 text-white
                    @elseif($purchaseRequest->status === 'rejected') bg-red-600 text-white
                    @else bg-blue-600 text-white
                    @endif">
                    {{ ucfirst($purchaseRequest->status) }}
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Company -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-2">COMPANY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->company }}</p>
        </div>

        <!-- Form Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">REQUISITIONER:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->requisitioner }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DEPARTMENT:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->department ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">SUPPLIER:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->supplier->supplier_name ?? $purchaseRequest->supplier ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">TERMS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->terms ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->delivery_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CONTACT PERSON:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->contact_person ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DATE OF REQUEST:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->date_of_request->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DATE NEEDED:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->date_needed ? $purchaseRequest->date_needed->format('F d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">TYPE OF REQUEST:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->type_of_request ? ucfirst($purchaseRequest->type_of_request) : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">WITH BUDGET:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->with_budget ? ucfirst($purchaseRequest->with_budget) : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CHARGE TO:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->charge_to ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CONTACT NUMBER:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->contact_number ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CREATED BY:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->creator->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white mb-2">Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-700">
                    <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="border border-gray-700 px-4 py-3">NO.</th>
                            <th class="border border-gray-700 px-4 py-3">QTY</th>
                            <th class="border border-gray-700 px-4 py-3">UOM</th>
                            <th class="border border-gray-700 px-4 py-3">DESCRIPTION</th>
                            <th class="border border-gray-700 px-4 py-3">UNIT PRICE</th>
                            <th class="border border-gray-700 px-4 py-3">AMOUNT</th>
                            <th class="border border-gray-700 px-4 py-3">REMARKS/SPECIFICATIONS</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-300 divide-y divide-gray-700">
                        @foreach($purchaseRequest->items as $item)
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-4 py-3 text-center">{{ $item->item_no }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ number_format($item->qty, 2) }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->uom }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->description }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->unit_price ? '₱' . number_format($item->unit_price, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->amount ? '₱' . number_format($item->amount, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->remarks ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                        @if($purchaseRequest->items->count() > 0)
                            <tr class="font-semibold bg-gray-700">
                                <td colspan="5" class="border border-gray-700 px-4 py-3 text-right">TOTAL:</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">₱{{ number_format($purchaseRequest->items->sum('amount'), 2) }}</td>
                                <td class="border border-gray-700 px-4 py-3"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reason for Requisition -->
        <div class="mb-6">
            <label class="block font-semibold text-white mb-2">REASON FOR REQUISITION:</label>
            <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[100px]">
                {{ $purchaseRequest->reason_for_requisition ?? 'No reason provided' }}
            </div>
        </div>

        <!-- Signature Section -->
        <div class="mb-6">
            <div class="border border-gray-700 rounded">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Prepared By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm" colspan="2">Noted By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm" colspan="3">Approved By:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                            <td class="border border-gray-700 px-4 py-16 text-center"></td>
                        </tr>
                        <tr class="bg-gray-700 text-gray-300 text-xs italic">
                            <td class="border border-gray-700 px-4 py-2 text-center">Requisitioner</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Department Head</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">General Manager</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">CFO</td>
                            <td class="border border-gray-700 px-4 py-2 text-center" colspan="2">President</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Status and Actions -->
        @if(auth()->user()->canApprovePurchaseRequests() && $purchaseRequest->status === 'pending')
            <div class="flex gap-3 mb-4 mt-6">
                <form action="{{ route('purchase_requests.approve', $purchaseRequest->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve
                    </button>
                </form>

                <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                    <i class="fas fa-times mr-1"></i> Reject
                </button>
            </div>
        @endif

        @if($purchaseRequest->status === 'approved' && $purchaseRequest->approver)
            <div class="p-4 bg-green-900/20 border border-green-700 rounded mb-4 mt-6">
                <p class="text-green-400">
                    <i class="fas fa-check-circle mr-1"></i>
                    Approved by {{ $purchaseRequest->approver->name }}
                    on {{ $purchaseRequest->approved_at->format('M d, Y h:i A') }}
                </p>
            </div>
        @endif

        @if($purchaseRequest->status === 'rejected' && $purchaseRequest->approver)
            <div class="p-4 bg-red-900/20 border border-red-700 rounded mb-4 mt-6">
                <p class="text-red-400">
                    <i class="fas fa-times-circle mr-1"></i>
                    Rejected by {{ $purchaseRequest->approver->name }}
                    on {{ $purchaseRequest->approved_at->format('M d, Y h:i A') }}
                </p>
                @if($purchaseRequest->rejection_reason)
                    <p class="text-gray-300 mt-2">
                        <strong>Reason:</strong> {{ $purchaseRequest->rejection_reason }}
                    </p>
                @endif
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('purchase_requests.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-4">
                <a href="{{ route('purchase_requests.edit', $purchaseRequest->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <a href="{{ route('purchase_requests.print', $purchaseRequest->id) }}" target="_blank" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Purchase Request</h3>
        <form action="{{ route('purchase_requests.reject', $purchaseRequest->id) }}" method="POST">
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
