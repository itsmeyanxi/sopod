@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gray-900 rounded-xl shadow-lg border border-gray-800">
        <!-- Header -->
        <div class="bg-gray-800 px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    🔄 Receiving Report Details
                </h2>
                <p class="text-sm text-gray-500 mt-1">RR Number: <span class="text-blue-700 font-mono">{{ $receivingReport->rr_number }}</span></p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('receiving-reports.print', $receivingReport->id) }}" target="_blank"
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print
                </a>
                <a href="{{ route('receiving-reports.index') }}"
                   class="bg-gray-700 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>

        <div class="p-6 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <!-- Status Badge -->
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 rounded-lg text-sm font-semibold
                    {{ $receivingReport->status == 'Received' ? 'bg-green-100 text-green-700 border border-green-200' : '' }}
                    {{ $receivingReport->status == 'Processing' ? 'bg-yellow-100 text-yellow-700 border border-yellow-700' : '' }}
                    {{ $receivingReport->status == 'Completed' ? 'bg-blue-100 text-blue-700 border border-blue-700' : '' }}">
                    {{ $receivingReport->status }}
                </span>
                <span class="px-4 py-2 rounded-lg text-sm font-semibold bg-orange-100 text-orange-700 border border-orange-700">
                    {{ $receivingReport->delivery_type }} Backload
                </span>
            </div>

            <!-- Sales Order Information -->
            <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Sales Order Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sales Order Number</label>
                        <p class="text-gray-200 font-mono">{{ $receivingReport->sales_order_number }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Customer Code</label>
                        <p class="text-gray-200">{{ $receivingReport->customer_code ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Customer Name</label>
                        <p class="text-gray-200">{{ $receivingReport->customer_name ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">TIN</label>
                        <p class="text-gray-200">{{ $receivingReport->tin_no ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Branch</label>
                        <p class="text-gray-200">{{ $receivingReport->branch ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">PO Number</label>
                        <p class="text-gray-200">{{ $receivingReport->po_number ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sales Representative</label>
                        <p class="text-gray-200">{{ $receivingReport->sales_representative ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sales Executive</label>
                        <p class="text-gray-200">{{ $receivingReport->sales_executive ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Delivery Batch (DR No.)</label>
                        <div class="flex items-center gap-2">
                            <p class="text-gray-200" id="drDisplay">{{ $receivingReport->delivery_batch ?? '—' }}</p>
                            <button type="button" onclick="document.getElementById('drEditForm').classList.toggle('hidden'); document.getElementById('drDisplay').classList.toggle('hidden'); this.classList.toggle('hidden');"
                                class="text-blue-600 hover:text-blue-800 text-xs" id="drEditBtn">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <form action="{{ route('receiving-reports.updateDr', $receivingReport->id) }}" method="POST" id="drEditForm" class="hidden mt-1 flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="delivery_batch" value="{{ $receivingReport->delivery_batch }}"
                                class="bg-gray-800 border border-gray-600 rounded px-2 py-1 text-sm text-white focus:ring-2 focus:ring-blue-500 focus:outline-none w-40">
                            <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded text-xs hover:bg-blue-700">Save</button>
                            <button type="button" onclick="document.getElementById('drEditForm').classList.add('hidden'); document.getElementById('drDisplay').classList.remove('hidden'); document.getElementById('drEditBtn').classList.remove('hidden');"
                                class="text-gray-500 hover:text-gray-200 text-xs">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Receiving Details -->
            <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Receiving Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Received Date</label>
                        <p class="text-gray-200">{{ $receivingReport->received_date ? $receivingReport->received_date->format('M d, Y h:i A') : '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Request Delivery Date</label>
                        <p class="text-gray-200">{{ $receivingReport->request_delivery_date ? \Carbon\Carbon::parse($receivingReport->request_delivery_date)->format('M d, Y') : '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Received By</label>
                        <p class="text-gray-200">{{ $receivingReport->received_by ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Plate No</label>
                        <p class="text-gray-200">{{ $receivingReport->plate_no ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sales Invoice No</label>
                        <p class="text-gray-200">{{ $receivingReport->sales_invoice_no ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Created By</label>
                        <p class="text-gray-200">{{ $receivingReport->created_by ?? '—' }}</p>
                    </div>
                </div>

                @if($receivingReport->additional_instructions)
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <label class="block text-xs text-gray-500 mb-2">Additional Instructions</label>
                    <p class="text-gray-200 bg-gray-900/50 p-3 rounded">{{ $receivingReport->additional_instructions }}</p>
                </div>
                @endif

                @if($receivingReport->attachment)
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <label class="block text-xs text-gray-500 mb-2">Attachment</label>
                    <a href="{{ asset('receiving_report_attachments/' . $receivingReport->attachment) }}" target="_blank" 
                       class="inline-flex items-center gap-2 text-blue-700 hover:text-blue-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        View Attachment
                    </a>
                </div>
                @endif
            </div>

            <!-- Items Table -->
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-800 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Received Items
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Item Code</th>
                                <th class="px-4 py-3 text-left">Description</th>
                                <th class="px-4 py-3 text-left">Brand</th>
                                <th class="px-4 py-3 text-left">Category</th>
                                <th class="px-4 py-3 text-right">Original Qty</th>
                                <th class="px-4 py-3 text-right">Received Qty</th>
                                <th class="px-4 py-3 text-center">UOM</th>
                                <th class="px-4 py-3 text-right">Unit Price</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @php $totalAmount = 0; @endphp
                            @forelse($receivingReport->items as $index => $item)
                            <tr class="text-gray-500 hover:bg-gray-800/50">
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-mono text-blue-700">{{ $item->item_code }}</td>
                                <td class="px-4 py-3">{{ $item->item_description ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $item->brand ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $item->item_category ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-blue-700">{{ number_format($item->original_quantity ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-green-700">{{ number_format($item->quantity, 2) }}</td>
                                <td class="px-4 py-3 text-center">{{ $item->uom ?? 'Kgs' }}</td>
                                <td class="px-4 py-3 text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">₱{{ number_format($item->total_amount, 2) }}</td>
                            </tr>
                            @php $totalAmount += $item->total_amount; @endphp

                            @if($item->notes)
                            <tr class="bg-gray-900/30">
                                <td colspan="10" class="px-4 py-2">
                                    <span class="text-xs text-gray-500">Note:</span>
                                    <span class="text-xs text-gray-500">{{ $item->notes }}</span>
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                    No items found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-900 font-semibold">
                            <tr>
                                <td colspan="9" class="px-4 py-3 text-right text-gray-500">Grand Total:</td>
                                <td class="px-4 py-3 text-right text-green-700 text-lg">₱{{ number_format($totalAmount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Linked AR Adjustments -->
            @if($receivingReport->arAdjustments && $receivingReport->arAdjustments->count() > 0)
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-800 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-exchange-alt text-purple-600"></i>
                        Linked AR Adjustments
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">{{ $receivingReport->arAdjustments->count() }}</span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Reference #</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Customer</th>
                                <th class="px-4 py-3 text-left">DR No.</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-center">Date</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($receivingReport->arAdjustments as $adj)
                            <tr class="hover:bg-gray-900">
                                <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ $adj->reference_number }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700">{{ $adj->formatted_type }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-200">{{ $adj->customer_name }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-300">{{ $adj->dr_no }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $adj->is_decrease ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $adj->is_decrease ? '-' : '+' }}₱{{ number_format(abs($adj->amount), 2) }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-300">{{ $adj->transaction_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('ar_adjustments.show', $adj->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-900 font-semibold">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-gray-500">Total Adjustments:</td>
                                <td class="px-4 py-3 text-right text-purple-700">
                                    ₱{{ number_format($receivingReport->arAdjustments->sum('amount'), 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            <!-- Timestamps -->
            <div class="flex justify-between text-xs text-gray-500 pt-4 border-t border-gray-700">
                <div>
                    <span>Created:</span>
                    <span class="text-gray-500">{{ $receivingReport->created_at->format('M d, Y h:i A') }}</span>
                </div>
                @if($receivingReport->updated_at != $receivingReport->created_at)
                <div>
                    <span>Last Updated:</span>
                    <span class="text-gray-500">{{ $receivingReport->updated_at->format('M d, Y h:i A') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection