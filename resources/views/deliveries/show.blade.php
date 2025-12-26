@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-10 bg-gray-900 min-h-screen text-gray-100">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">Delivery Details</h1>
        <div class="flex gap-3">
            <a href="{{ route('deliveries.print', $delivery->id) }}"
               target="_blank"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
               🖨️ Print Delivery
            </a>
            <button type="button"
                    onclick="exportExcel({{ $delivery->id }})"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded transition">
                     📥 Export Excel
            </button>
            <a href="{{ route('deliveries.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
               ← Back
            </a>
        </div>
    </div>

    @php
        // Get proper data with fallbacks
        $so = $delivery->salesOrder;
        $customer = $so?->customer;
        
        $customerCode = $delivery->customer_code ?? $so?->customer_code ?? $customer?->customer_code ?? '—';
        $customerName = $delivery->customer_name ?? $customer?->customer_name ?? $so?->client_name ?? '—';
        $tinNo = $delivery->tin_no ?? $customer?->tin_no ?? '-';
        $branch = $delivery->branch ?? $so?->branch ?? '—';
        $salesRep = $delivery->sales_rep ?? $delivery->sales_representative ?? $so?->sales_rep ?? '—';
        $salesExec = $delivery->sales_executive ?? $so?->sales_executive ?? '—';
        $poNumber = $delivery->po_number ?? $so?->po_number ?? '—';
        $additionalInstructions = $delivery->additional_instructions ?? $so?->additional_instructions ?? '—';
        $approvedBy = $delivery->approved_by ?? $so?->approved_by ?? '—';
        $notes = $so?->notes ?? '—';
        
        // Request Delivery Date
        $requestDeliveryDate = '—';
        if ($delivery->request_delivery_date) {
            $requestDeliveryDate = \Carbon\Carbon::parse($delivery->request_delivery_date)->format('m/d/Y');
        } elseif ($so?->request_delivery_date) {
            $requestDeliveryDate = \Carbon\Carbon::parse($so->request_delivery_date)->format('m/d/Y');
        }
        
        // Status badge
        $statusColors = [
            'Delivered' => 'bg-green-600/20 text-green-400 border-green-600',
            'Partial' => 'bg-orange-600/20 text-orange-400 border-orange-600',
            'Cancelled' => 'bg-red-600/20 text-red-400 border-red-600',
        ];
        $statusColor = $statusColors[$delivery->status] ?? 'bg-gray-600/20 text-gray-400 border-gray-600';
    @endphp

    <!-- Approval Actions (Show only if Pending and user can approve) -->
    @if($delivery->approval_status === 'Pending' && auth()->user()->canApproveDeliveries())
    <div class="mb-6 bg-blue-900/30 border border-blue-600 p-6 rounded-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-start gap-3">
                <span class="text-3xl">⏳</span>
                <div>
                    <h4 class="text-blue-300 font-semibold text-lg mb-1">Approval Required</h4>
                    <p class="text-sm text-blue-200">
                        This delivery is awaiting your approval. Please review the details below and approve or reject.
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                <!-- Approve Button -->
                <button type="button"
                        onclick="showApprovalModal('approve')"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Approve
                </button>
                
                <!-- Reject Button -->
                <button type="button"
                        onclick="showApprovalModal('reject')"
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Reject
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Pulled Out Alert -->
    @if($delivery->is_pulled_out)
    <div class="mb-6 bg-orange-900/30 border border-orange-600 p-4 rounded-lg">
        <div class="flex items-start gap-3">
            <span class="text-2xl">🔒</span>
            <div class="flex-1">
                <h4 class="text-orange-300 font-semibold mb-1">Delivery Pulled Out</h4>
                <p class="text-sm text-orange-200 mb-2">
                    This delivery has been pulled out.
                </p>
                <div class="bg-orange-950/50 p-3 rounded">
                    <p class="text-xs text-orange-300 mb-1">
                        <strong>Pulled out by:</strong> {{ $delivery->pulled_out_by }}
                    </p>
                    <p class="text-xs text-orange-300 mb-1">
                        <strong>Date:</strong> {{ \Carbon\Carbon::parse($delivery->pulled_out_at)->format('M d, Y h:i A') }}
                    </p>
                    <p class="text-xs text-orange-300">
                        <strong>Reason:</strong> {{ $delivery->pullout_reason }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Rejected Alert -->
    @if($delivery->approval_status === 'Rejected')
    <div class="mb-6 bg-red-900/30 border border-red-600 p-4 rounded-lg">
        <div class="flex items-start gap-3">
            <span class="text-2xl">❌</span>
            <div class="flex-1">
                <h4 class="text-red-300 font-semibold mb-1">Delivery Rejected</h4>
                <p class="text-sm text-red-200 mb-2">
                    This delivery was rejected during the approval process.
                </p>
                <div class="bg-red-950/50 p-3 rounded">
                    <p class="text-xs text-red-300 mb-1">
                        <strong>Rejected by:</strong> {{ $delivery->approved_by_user }}
                    </p>
                    @if($delivery->approved_at)
                    <p class="text-xs text-red-300 mb-1">
                        <strong>Date:</strong> {{ \Carbon\Carbon::parse($delivery->approved_at)->format('M d, Y h:i A') }}
                    </p>
                    @endif
                    <p class="text-xs text-red-300">
                        <strong>Reason:</strong> {{ $delivery->rejection_reason }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Pending Approval Alert (for non-approvers) -->
    @if($delivery->approval_status === 'Pending' && !auth()->user()->canApproveDeliveries())
    <div class="mb-6 bg-yellow-900/30 border border-yellow-600 p-4 rounded-lg">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⏳</span>
            <div>
                <h4 class="text-yellow-300 font-semibold mb-1">Pending Approval</h4>
                <p class="text-sm text-yellow-200">
                    This delivery is awaiting approval from an authorized user.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Status Badge (if Partial) -->
    @if($delivery->status === 'Partial')
    <div class="mb-6 bg-orange-900/20 border border-orange-700 p-4 rounded-lg">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-orange-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <h4 class="text-orange-300 font-semibold mb-1">⚠️ Partial Delivery</h4>
                <p class="text-sm text-orange-200">This delivery was partially fulfilled. Remaining quantities need to be delivered separately.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Delivery Info -->
    <div class="bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- DR No -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">DR No</label>
                <input type="text" value="{{ $delivery->dr_no }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Order -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Order No</label>
                <input type="text" value="{{ $delivery->sales_order_number }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Delivery Batch -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Delivery Batch</label>
                @if($delivery->delivery_batch)
                    @php
                        $parts = explode('-', $delivery->delivery_batch);
                        $dateStr = end($parts);
                        try {
                            $batchDisplay = \Carbon\Carbon::parse($dateStr)->format('M d, Y');
                        } catch (\Exception $e) {
                            $batchDisplay = $delivery->delivery_batch;
                        }
                    @endphp
                    <div class="w-full px-4 py-2 rounded-lg bg-purple-900/30 border border-purple-700 text-purple-300 flex items-center gap-2">
                        <span class="text-lg">📦</span>
                        <span>{{ $batchDisplay }}</span>
                    </div>
                @else
                    <input type="text" value="Single Delivery" 
                           class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-400" readonly>
                @endif
            </div>

            <!-- Delivery Status -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Delivery Status</label>
                <div class="w-full px-4 py-2 rounded-lg border {{ $statusColor }} flex items-center gap-2">
                    @if($delivery->status === 'Delivered')
                        <span class="text-lg">✅</span>
                    @elseif($delivery->status === 'Partial')
                        <span class="text-lg">⚠️</span>
                    @elseif($delivery->status === 'Cancelled')
                        <span class="text-lg">❌</span>
                    @endif
                    <span class="font-semibold">{{ $delivery->status }}</span>
                </div>
            </div>
            
            <!-- Approval Status -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Approval Status</label>
                @php
                    $approvalColors = [
                        'Pending' => 'bg-yellow-900/30 border-yellow-700 text-yellow-300',
                        'Approved' => 'bg-green-900/30 border-green-700 text-green-300',
                        'Rejected' => 'bg-red-900/30 border-red-700 text-red-300',
                    ];
                    $approvalColor = $approvalColors[$delivery->approval_status] ?? 'bg-gray-600/20 text-gray-400 border-gray-600';
                @endphp
                <div class="w-full px-4 py-2 rounded-lg border {{ $approvalColor }} flex items-center gap-2">
                    @if($delivery->approval_status === 'Approved')
                        <span class="text-lg">✓</span>
                    @elseif($delivery->approval_status === 'Pending')
                        <span class="text-lg">⏳</span>
                    @elseif($delivery->approval_status === 'Rejected')
                        <span class="text-lg">✗</span>
                    @endif
                    <span class="font-semibold">{{ $delivery->approval_status }}</span>
                </div>
            </div>

            <!-- Customer Code -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Customer Code</label>
                <input type="text" value="{{ $customerCode }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Customer Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Customer Name</label>
                <input type="text" value="{{ $customerName }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- TIN  -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">TIN</label>
                <input type="text" value="{{ $tinNo }}"  readonly
                    class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" />
            </div>

            <!-- Branch -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Branch</label>
                <input type="text" value="{{ $branch }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Representative -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Representative</label>
                <input type="text" value="{{ $salesRep }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Executive -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Executive</label>
                <input type="text" value="{{ $salesExec }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Plate No -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Plate No</label>
                <input type="text" value="{{ $delivery->plate_no ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Sales Invoice -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Sales Invoice No</label>
                <input type="text" value="{{ $delivery->sales_invoice_no ?? '—' }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- PO Number -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">PO Number</label>
                <input type="text" value="{{ $poNumber }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Request Delivery Date -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Request Delivery Date</label>
                <input type="text" value="{{ $requestDeliveryDate }}"
                  class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>

            <!-- Approved By -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Approved By</label>
                <input type="text" value="{{ $approvedBy }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>
            
            <!-- Approval Details (if approved or rejected) -->
            @if($delivery->approved_by_user && $delivery->approval_status !== 'Pending')
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">
                    {{ $delivery->approval_status === 'Approved' ? 'Approved By' : 'Rejected By' }}
                </label>
                <div class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600">
                    <p class="text-gray-100">{{ $delivery->approved_by_user }}</p>
                    @if($delivery->approved_at)
                        <p class="text-xs text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($delivery->approved_at)->format('M d, Y h:i A') }}
                        </p>
                    @endif
                </div>
            </div>
            @endif
            
            <!-- Created By -->
            @if($delivery->created_by)
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Created By</label>
                <input type="text" value="{{ $delivery->created_by }}" 
                       class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100" readonly>
            </div>
            @endif

            <!-- ✅ NEW: PO Image from Sales Order -->
            @if($so && $so->po_image)
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-300 mb-2">📸 PO Proof / Order Evidence (from Sales Order)</label>
                <div class="bg-gray-700/50 border border-gray-600 rounded-lg p-4">
                    @if(Str::endsWith($so->po_image, '.pdf'))
                        {{-- PDF File --}}
                        <a href="{{ asset('po_images/' . $so->po_image) }}" 
                           target="_blank" 
                           class="text-blue-400 hover:text-blue-300 flex items-center gap-2 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="underline">View PO Document (PDF)</span>
                        </a>
                    @else
                        {{-- Image File --}}
                        <a href="{{ asset('po_images/' . $so->po_image) }}" 
                           target="_blank"
                           onclick="openImageModal('{{ asset('po_images/' . $so->po_image) }}'); return false;"
                           class="block">
                            <img src="{{ asset('po_images/' . $so->po_image) }}" 
                                 alt="PO Proof" 
                                 class="max-w-md w-full rounded border border-gray-600 hover:border-blue-500 transition-all hover:shadow-lg cursor-pointer">
                        </a>
                        <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Click to view full size
                        </p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Notes from Sales Order -->
            @if($notes !== '—')
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-300 mb-2">📝 Notes (from Sales Order)</label>
                <textarea class="w-full px-4 py-2 rounded-lg bg-blue-900/20 border border-blue-700 text-blue-200"
                        rows="2" readonly>{{ $notes }}</textarea>
            </div>
            @endif

            <!-- Additional Instructions -->
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Additional Instructions</label>
                <textarea class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100"
                        rows="3" readonly>{{ $additionalInstructions }}</textarea>
            </div>

            <!-- 📎 Attachment Display -->
           @if($delivery->attachment)
                <div class="md:col-span-2">
                    <h3 class="font-semibold mb-2">Attached Image:</h3>
                    <img 
                        src="{{ asset('delivery_images/' . $delivery->attachment) }}" 
                        alt="Delivery Attachment" 
                        class="rounded-lg shadow-md cursor-pointer hover:opacity-90 transition" 
                        style="max-width: 400px;"
                        onclick="openImageModal('{{ asset('delivery_images/' . $delivery->attachment) }}')"
                    >
                </div>
            @endif

        </div>

        <h3 class="text-lg font-semibold text-white mt-10 mb-4 border-b border-gray-700 pb-1">Delivery Items</h3>

        @php
            $items = $delivery->items;
            $grandTotal = 0;
            $hasPartialItems = false;
            
            // ✅ Create map of SO items for comparison
            $soItemsMap = collect();
            if ($so && $so->items) {
                foreach ($so->items as $soItem) {
                    $soItemsMap->put($soItem->item_code, $soItem);
                }
            }
            
            // ✅ FIXED: Calculate total delivered quantities across ALL batches INCLUDING this one
            $totalDeliveredMap = \App\Models\DeliveryItem::whereHas('delivery', function($q) use ($delivery) {
                    $q->where('sales_order_number', $delivery->sales_order_number)
                      ->where('status', 'Delivered'); // Only count delivered items
                })
                ->select('item_code', \DB::raw('SUM(quantity) as total_delivered'))
                ->groupBy('item_code')
                ->get()
                ->keyBy('item_code');
            
            foreach ($items as $item) {
                $grandTotal += $item->total_amount ?? 0;
            }
        @endphp

        <div class="overflow-x-auto mt-6">
            <table class="w-full border border-gray-700 rounded-md overflow-hidden text-sm">
                <thead class="bg-gray-700 text-gray-300 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Item Code</th>
                        <th class="px-4 py-2 text-left">Description</th>
                        <th class="px-4 py-2 text-left">Brand</th>
                        <th class="px-4 py-2 text-left">Category</th>
                        <th class="px-4 py-2 text-right">Original Qty<br><span class="text-xs text-gray-400">(SO Qty)</span></th>
                        <th class="px-4 py-2 text-right">This Batch Qty<br><span class="text-xs text-gray-400">(DR Qty)</span></th>
                        <th class="px-4 py-2 text-right">Total Delivered<br><span class="text-xs text-gray-400">(All Batches)</span></th>
                        <th class="px-4 py-2 text-right">Remaining</th>
                        <th class="px-4 py-2 text-center">UOM</th>
                        <th class="px-4 py-2 text-right">Unit Price</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-left">Notes</th>
                    </tr>
                </thead>

                <tbody class="bg-gray-900">
                  @forelse($items as $item)
                    @php
                        $soItem = $soItemsMap->get($item->item_code);
                        $originalQty = $item->original_quantity ?? $soItem?->quantity ?? $item->quantity;
                        $thisBatchQty = $item->quantity ?? 0;
                        
                        // ✅ SKIP items with zero quantity (removed items)
                        if ($thisBatchQty == 0) {
                            continue;
                        }
                        
                        // Get total delivered from ALL batches (including this one)
                        $totalDelivered = $totalDeliveredMap->get($item->item_code)?->total_delivered ?? $thisBatchQty;
                        
                        // Calculate remaining
                        $remaining = $originalQty - $totalDelivered;
                        
                        $isPartial = $remaining > 0;
                        
                        if ($isPartial) {
                            $hasPartialItems = true;
                        }
                    @endphp

                    <tr class="border-b border-gray-800 hover:bg-gray-800 
                        {{ $isPartial ? 'bg-orange-900/10' : '' }}">
                            <td class="px-4 py-2">{{ $item->item_code ?? '—' }}</td>
                            <td class="px-4 py-2">{{  $item->item_description ?? $item->item?->item_description ?? $item->salesOrderItem?->item_description ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $item->brand ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $item->item_category ?? '—' }}</td>

                            <!-- Original Qty (SO Qty) -->
                            <td class="px-4 py-2 text-right">
                                <span class="font-semibold text-blue-400">
                                    {{ number_format($originalQty, 2) }}
                                </span>
                            </td>

                            <!-- This Batch Qty (DR Qty) -->
                            <td class="px-4 py-2 text-right">
                                <span class="font-semibold text-green-400">
                                    {{ number_format($thisBatchQty, 2) }}
                                </span>
                            </td>

                            <!-- Total Delivered (All Batches) -->
                           <td class="px-4 py-2 text-right">
                                <div class="flex flex-col items-end">
                                    <span class="font-semibold text-purple-400">
                                        {{ number_format($totalDelivered, 2) }}
                                    </span>
                                    @if($totalDelivered > $thisBatchQty)
                                        <span class="text-xs text-gray-500">
                                            (Multi-batch)
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Remaining -->
                            <td class="px-4 py-2 text-right">
                                @if($remaining > 0)
                                    <span class="font-semibold text-orange-400">
                                        {{ number_format($remaining, 2) }}
                                    </span>
                                @elseif($remaining < 0)
                                    <span class="font-semibold text-red-400">
                                        OVER: {{ number_format(abs($remaining), 2) }}
                                    </span>
                                @else
                                    <span class="text-green-400 font-semibold">✓ Complete</span>
                                @endif
                            </td>

                            <td class="px-4 py-2 text-center">{{ $item->uom ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                ₱{{ number_format($item->unit_price ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                ₱{{ number_format($item->total_amount ?? 0, 2) }}
                            </td>
                            <!-- Notes Column -->
                            <td class="px-4 py-2 text-left">
                                @if($item->notes)
                                    <span class="text-gray-200">{{ $item->notes }}</span>
                                @else
                                    <span class="text-gray-500 italic">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                                No items found for this delivery
                            </td>
                        </tr>
                    @endforelse

                    @if($items->count() > 0)
                        <!-- Grand Total Row -->
                        <tr class="bg-gray-800 font-semibold">
                            <td colspan="10" class="px-4 py-3 text-right">Grand Total:</td>
                            <td class="px-4 py-3 text-right text-green-400">₱{{ number_format($grandTotal, 2) }}</td>
                            <td></td>
                        </tr>
                        
                    @endif
                </tbody>
            </table>
        </div>

        @if($hasPartialItems)
        <div class="mt-4 bg-orange-900/20 border border-orange-700 p-3 rounded-lg text-sm">
            <p class="text-orange-300">
                <strong>Note:</strong> Items highlighted in orange have remaining quantities that need to be delivered in a future delivery.
            </p>
        </div>
        @endif

        <!-- Back Button -->
        <div class="flex justify-end mt-8">
            <a href="{{ route('deliveries.index') }}" 
               class="bg-gray-600 hover:bg-gray-500 text-white px-6 py-2 rounded-lg transition">
                Back to List
            </a>
        </div>
    </div>
</div>

<!-- Approval/Rejection Modal -->
<div id="approvalModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 id="modalTitle" class="text-xl font-bold text-white mb-4"></h3>
        
        <form id="approvalForm" method="POST" action="{{ route('deliveries.approve', $delivery->id) }}">
            @csrf
            @method('POST')
            <input type="hidden" name="action" id="modalAction">
            
            <!-- Rejection Reason (shown only for reject) -->
            <div id="rejectionReasonDiv" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-300 mb-2">Rejection Reason <span class="text-red-400">*</span></label>
                <textarea name="rejection_reason" 
                          id="rejectionReason"
                          rows="4" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Please provide a reason for rejection..."></textarea>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" 
                        onclick="closeApprovalModal()"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
                    Cancel
                </button>
                <button type="submit" 
                        id="modalSubmitBtn"
                        class="px-4 py-2 rounded-lg transition text-white font-semibold">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 🖼️ Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-50" onclick="closeImageModal()">
    <div class="relative max-w-7xl max-h-screen p-4">
        <button onclick="closeImageModal()" 
                class="absolute top-4 right-4 bg-red-600 hover:bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center text-xl font-bold">
            ✕
        </button>
        <img id="modalImage" src="" alt="Full Size Image" class="max-w-full max-h-screen object-contain rounded-lg">
    </div>
</div>

<script>
function showApprovalModal(action) {
    const modal = document.getElementById('approvalModal');
    const title = document.getElementById('modalTitle');
    const actionInput = document.getElementById('modalAction');
    const submitBtn = document.getElementById('modalSubmitBtn');
    const rejectionDiv = document.getElementById('rejectionReasonDiv');
    const rejectionTextarea = document.getElementById('rejectionReason');
    
    if (action === 'approve') {
        title.textContent = '✓ Approve Delivery';
        actionInput.value = 'approve';
        submitBtn.textContent = 'Approve Delivery';
        submitBtn.className = 'bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition text-white font-semibold';
        rejectionDiv.classList.add('hidden');
        rejectionTextarea.required = false;
    } else {
        title.textContent = '✗ Reject Delivery';
        actionInput.value = 'reject';
        submitBtn.textContent = 'Reject Delivery';
        submitBtn.className = 'bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition text-white font-semibold';
        rejectionDiv.classList.remove('hidden');
        rejectionTextarea.required = true;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeApprovalModal() {
    const modal = document.getElementById('approvalModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('rejectionReason').value = '';
}

function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModal').classList.remove('hidden');
    document.getElementById('imageModal').classList.add('flex');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
    document.getElementById('imageModal').classList.remove('flex');
}

function exportExcel(deliveryId) {
    if (!deliveryId) return;
    let url = '{{ route("deliveries.exportDeliveryItemsExcel") }}?delivery_id=' + deliveryId;
    window.location.href = url;
}

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
        closeApprovalModal();
    }
});
</script>
@endsection