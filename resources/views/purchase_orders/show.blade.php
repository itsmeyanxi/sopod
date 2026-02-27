@extends('layouts.app')

@section('title', 'View Purchase Order')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">PURCHASE ORDER</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-300">PO NO:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $purchaseOrder->po_no }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($purchaseOrder->status === 'pending') bg-yellow-600 text-white
                    @elseif($purchaseOrder->status === 'approved') bg-green-600 text-white
                    @elseif($purchaseOrder->status === 'rejected') bg-red-600 text-white
                    @else bg-blue-600 text-white
                    @endif">
                    {{ ucfirst($purchaseOrder->status) }}
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

        <!-- Approval Trail -->
        <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
            <h3 class="text-lg font-semibold text-white mb-4">Approval Trail</h3>
            <div class="space-y-3">
                <!-- Department Head Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($purchaseOrder->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-300"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-300">
                            <span class="font-semibold">Department Head Approval</span>
                            @if($purchaseOrder->department_head_approved_by && $purchaseOrder->departmentHeadApprover)
                                <span class="text-green-400">✓ Approved</span>
                                <br>
                                <small class="text-gray-400">
                                    {{ $purchaseOrder->departmentHeadApprover->name }}
                                    on {{ $purchaseOrder->department_head_approved_at->format('M d, Y h:i A') }}
                                </small>
                                @if($purchaseOrder->department_head_approved_latitude && $purchaseOrder->department_head_approved_longitude)
                                    <br>
                                    <small class="text-gray-500">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Coordinates: {{ $purchaseOrder->department_head_approved_latitude }}, {{ $purchaseOrder->department_head_approved_longitude }}
                                        @if($purchaseOrder->department_head_approved_location)
                                            ({{ $purchaseOrder->department_head_approved_location }})
                                        @endif
                                    </small>
                                @endif
                            @else
                                <span class="text-yellow-400">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Management Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($purchaseOrder->management_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($purchaseOrder->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-300"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-700">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-300">
                            <span class="font-semibold">Management Approval (GM)</span>
                            @if($purchaseOrder->management_approved_by && $purchaseOrder->managementApprover)
                                <span class="text-green-400">✓ Approved</span>
                                <br>
                                <small class="text-gray-400">
                                    {{ $purchaseOrder->managementApprover->name }}
                                    on {{ $purchaseOrder->management_approved_at->format('M d, Y h:i A') }}
                                </small>
                                @if($purchaseOrder->management_approved_latitude && $purchaseOrder->management_approved_longitude)
                                    <br>
                                    <small class="text-gray-500">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Coordinates: {{ $purchaseOrder->management_approved_latitude }}, {{ $purchaseOrder->management_approved_longitude }}
                                        @if($purchaseOrder->management_approved_location)
                                            ({{ $purchaseOrder->management_approved_location }})
                                        @endif
                                    </small>
                                @endif
                            @elseif($purchaseOrder->department_head_approved_by)
                                <span class="text-yellow-400">Pending</span>
                            @else
                                <span class="text-gray-400">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Executive Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($purchaseOrder->status === 'approved' && $purchaseOrder->approver)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($purchaseOrder->management_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-300"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-700">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-300">
                            <span class="font-semibold">Executive Approval (President/VP)</span>
                            @if($purchaseOrder->status === 'approved' && $purchaseOrder->approver)
                                <span class="text-green-400">✓ Approved</span>
                                <br>
                                <small class="text-gray-400">
                                    {{ $purchaseOrder->approver->name }}
                                    on {{ $purchaseOrder->approved_at->format('M d, Y h:i A') }}
                                </small>
                                @if($purchaseOrder->approved_latitude && $purchaseOrder->approved_longitude)
                                    <br>
                                    <small class="text-gray-500">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Coordinates: {{ $purchaseOrder->approved_latitude }}, {{ $purchaseOrder->approved_longitude }}
                                        @if($purchaseOrder->approved_location)
                                            ({{ $purchaseOrder->approved_location }})
                                        @endif
                                    </small>
                                @endif
                            @elseif($purchaseOrder->management_approved_by)
                                <span class="text-yellow-400">Pending</span>
                            @else
                                <span class="text-gray-400">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($purchaseOrder->status === 'rejected')
                    <div class="flex items-start gap-4 p-3 bg-red-900/20 border border-red-700 rounded">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-400">
                                <span class="font-semibold">Rejected</span>
                                @if($purchaseOrder->approver)
                                    <br>
                                    <small class="text-red-300">
                                        {{ $purchaseOrder->approver->name }}
                                        on {{ $purchaseOrder->approved_at->format('M d, Y h:i A') }}
                                    </small>
                                @endif
                            </p>
                            @if($purchaseOrder->rejection_reason)
                                <p class="text-gray-300 mt-2">
                                    <strong>Reason:</strong> {{ $purchaseOrder->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Stage-Aware Approval Actions -->
        @if($purchaseOrder->status === 'pending')
            @if($purchaseOrder->approval_stage === 'pending_dh' && auth()->user()->canApprovePurchaseOrdersAsDH())
                <div class="flex gap-3 mb-4 mt-6">
                    <button type="button" onclick="showApproveDHModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Department Head
                    </button>
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                </div>
            @endif

            @php
                $canApproveManagementPO = auth()->user()->canApprovePurchaseOrdersAsManagement();
                if ($canApproveManagementPO && str_contains(strtolower($purchaseOrder->company ?? ''), 'magalang')) {
                    $canApproveManagementPO = auth()->user()->hasRole(['Admin', 'IT', 'CFO']);
                }
            @endphp
            @if($purchaseOrder->approval_stage === 'pending_management' && $canApproveManagementPO)
                <div class="flex gap-3 mb-4 mt-6">
                    <button type="button" onclick="showApproveManagementModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as {{ str_contains(strtolower($purchaseOrder->company ?? ''), 'magalang') ? 'CFO' : 'Management' }}
                    </button>
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                </div>
            @endif

            @if($purchaseOrder->approval_stage === 'pending_executive' && auth()->user()->canApprovePurchaseOrdersAsExecutive())
                <div class="flex gap-3 mb-4 mt-6">
                    <button type="button" onclick="showApproveExecutiveModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Executive
                    </button>
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                </div>
            @endif
        @endif

        <!-- Company -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-2">COMPANY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->company }}</p>
        </div>

        <!-- Form Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->consignee ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CONSIGNEE ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[60px]">{{ $purchaseOrder->consignee_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DELIVERY ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[60px]">{{ $purchaseOrder->delivery_address ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">ORDER DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->order_date->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">EXPECTED DELIVERY DATE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('F d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">PAYMENT TERMS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->payment_terms ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">LOCATION:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->location ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">HOUSE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->house ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">PR#:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">
                        @if($purchaseOrder->purchaseRequest)
                            <a href="{{ route('purchase_requests.show', $purchaseOrder->purchaseRequest->id) }}" class="text-purple-400 hover:text-purple-300">
                                {{ $purchaseOrder->pr_no }}
                            </a>
                        @else
                            {{ $purchaseOrder->pr_no ?? 'N/A' }}
                        @endif
                    </p>
                </div>
                @if($purchaseOrder->purchaseRequest && $purchaseOrder->purchaseRequest->reason_for_requisition)
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">REASON FOR REQUISITION:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->purchaseRequest->reason_for_requisition }}</p>
                </div>
                @endif
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">LC PRICE:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->lc_price ? '₱' . number_format($purchaseOrder->lc_price, 2) : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">CREATED BY:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseOrder->creator->name ?? 'N/A' }}</p>
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
                            <th class="border border-gray-700 px-4 py-3">ITEM CODE</th>
                            <th class="border border-gray-700 px-4 py-3">QTY</th>
                            <th class="border border-gray-700 px-4 py-3">UOM</th>
                            <th class="border border-gray-700 px-4 py-3">DESCRIPTION</th>
                            <th class="border border-gray-700 px-4 py-3">SUPPLIER</th>
                            <th class="border border-gray-700 px-4 py-3">DATE NEEDED</th>
                            <th class="border border-gray-700 px-4 py-3">UNIT PRICE</th>
                            <th class="border border-gray-700 px-4 py-3">TAX</th>
                            <th class="border border-gray-700 px-4 py-3">TOTAL</th>
                            <th class="border border-gray-700 px-4 py-3">NOTE</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-300 divide-y divide-gray-700">
                        @foreach($purchaseOrder->items as $item)
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-4 py-3 text-center">{{ $item->item_no }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->item_code ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ number_format($item->qty, 2) }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->uom }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->description }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->supplier_name ?? $purchaseOrder->supplier ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-center">
                                    {{ $item->date_needed ? \Carbon\Carbon::parse($item->date_needed)->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->unit_price ? '₱' . number_format($item->unit_price, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->tax ? '₱' . number_format($item->tax, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->total ? '₱' . number_format($item->total, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->note ?? '' }}</td>
                            </tr>
                        @endforeach
                        @if($purchaseOrder->items->count() > 0)
                            <tr class="font-semibold bg-gray-700">
                                <td colspan="9" class="border border-gray-700 px-4 py-3 text-right">GRAND TOTAL:</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">₱{{ number_format($purchaseOrder->items->sum('total'), 2) }}</td>
                                <td class="border border-gray-700 px-4 py-3"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Remarks -->
        <div class="mb-6">
            <label class="block font-semibold text-white mb-2">REMARKS:</label>
            <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[100px]">
                {{ $purchaseOrder->remarks ?? 'No remarks provided' }}
            </div>
        </div>

        <!-- Quotation -->
        <div class="mb-6">
            <label class="block font-semibold text-white mb-2">QUOTATION:</label>
            @if($purchaseOrder->quotation)
                <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded">
                    <a href="{{ asset('storage/' . $purchaseOrder->quotation) }}" target="_blank" class="text-blue-400 hover:text-blue-300 flex items-center gap-2">
                        <i class="fas fa-file-download"></i>
                        {{ basename($purchaseOrder->quotation) }}
                    </a>
                </div>
            @else
                <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-400">
                    No quotation file uploaded
                </div>
            @endif
        </div>

        <!-- Signature Section -->
        <div class="mb-6">
            <div class="border border-gray-700 rounded">
                <table class="w-full" style="table-layout: fixed;">
                    <colgroup>
                        <col style="width: 20%;"><col style="width: 20%;"><col style="width: 20%;"><col style="width: 20%;"><col style="width: 20%;">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Prepared By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Noted By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm" colspan="3">Approved By:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseOrder->creator->name ?? '' }}</span>
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseOrder->departmentHeadApprover->name ?? '' }}</span>
                                @if($purchaseOrder->departmentHeadApprover && $purchaseOrder->department_head_approved_at)
                                    <div class="text-xs text-gray-400 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $purchaseOrder->department_head_approved_at->format('d M Y | H:i') }}
                                        @if($purchaseOrder->department_head_approved_latitude && $purchaseOrder->department_head_approved_longitude)
                                            <br>Coords: {{ $purchaseOrder->department_head_approved_latitude }}, {{ $purchaseOrder->department_head_approved_longitude }}
                                            @if($purchaseOrder->department_head_approved_location) ({{ $purchaseOrder->department_head_approved_location }}) @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseOrder->managementApprover->name ?? '' }}</span>
                                @if($purchaseOrder->managementApprover && $purchaseOrder->management_approved_at)
                                    <div class="text-xs text-gray-400 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $purchaseOrder->management_approved_at->format('d M Y | H:i') }}
                                        @if($purchaseOrder->management_approved_latitude && $purchaseOrder->management_approved_longitude)
                                            <br>Coords: {{ $purchaseOrder->management_approved_latitude }}, {{ $purchaseOrder->management_approved_longitude }}
                                            @if($purchaseOrder->management_approved_location) ({{ $purchaseOrder->management_approved_location }}) @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom"></td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseOrder->approver->name ?? '' }}</span>
                                @if($purchaseOrder->approver && $purchaseOrder->approved_at)
                                    <div class="text-xs text-gray-400 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $purchaseOrder->approved_at->format('d M Y | H:i') }}
                                        @if($purchaseOrder->approved_latitude && $purchaseOrder->approved_longitude)
                                            <br>Coords: {{ $purchaseOrder->approved_latitude }}, {{ $purchaseOrder->approved_longitude }}
                                            @if($purchaseOrder->approved_location) ({{ $purchaseOrder->approved_location }}) @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-gray-700 text-gray-300 text-xs italic">
                            <td class="border border-gray-700 px-4 py-2 text-center">Requisitioner</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Department Head</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">GM</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">CFO</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Vice-President/President</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('purchase_orders.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-4">
                @if($purchaseOrder->status === 'approved' && $purchaseOrder->approved_at !== null)
                    <a href="{{ route('purchase_orders.edit', $purchaseOrder->id) }}" class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-yellow-700 transition">
                        <i class="fas fa-sticky-note mr-1"></i> Edit Notes
                    </a>
                @else
                    <a href="{{ route('purchase_orders.edit', $purchaseOrder->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
                <a href="{{ route('purchase_orders.print', $purchaseOrder->id) }}" target="_blank" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Department Head Approval Modal -->
<div id="approveDHModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Department Head</h3>
        <form action="{{ route('purchase_orders.approve_dh', $purchaseOrder->id) }}" method="POST" id="approveDHForm">
            @csrf
            <input type="hidden" name="latitude" id="dh_latitude">
            <input type="hidden" name="longitude" id="dh_longitude">
            <input type="hidden" name="location" id="dh_location">
            <div class="mb-4">
                <p class="text-gray-300 mb-2">Geolocation will be captured automatically.</p>
                <div id="dh_geolocation_status" class="text-sm text-gray-400">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveDHModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Cancel</button>
                <button type="submit" id="dh_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Management Approval Modal -->
<div id="approveManagementModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Management (GM)</h3>
        <form action="{{ route('purchase_orders.approve_management', $purchaseOrder->id) }}" method="POST" id="approveManagementForm">
            @csrf
            <input type="hidden" name="latitude" id="mgmt_latitude">
            <input type="hidden" name="longitude" id="mgmt_longitude">
            <input type="hidden" name="location" id="mgmt_location">
            <div class="mb-4">
                <p class="text-gray-300 mb-2">Geolocation will be captured automatically.</p>
                <div id="mgmt_geolocation_status" class="text-sm text-gray-400">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveManagementModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Cancel</button>
                <button type="submit" id="mgmt_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Executive Approval Modal -->
<div id="approveExecutiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Executive (President/VP)</h3>
        <form action="{{ route('purchase_orders.approve', $purchaseOrder->id) }}" method="POST" id="approveExecutiveForm">
            @csrf
            <input type="hidden" name="latitude" id="exec_latitude">
            <input type="hidden" name="longitude" id="exec_longitude">
            <input type="hidden" name="location" id="exec_location">
            <div class="mb-4">
                <p class="text-gray-300 mb-2">Geolocation will be captured automatically.</p>
                <div id="exec_geolocation_status" class="text-sm text-gray-400">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveExecutiveModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Cancel</button>
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
        <h3 class="text-xl font-bold text-white mb-4">Reject Purchase Order</h3>
        <form action="{{ route('purchase_orders.reject', $purchaseOrder->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" rows="4"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Cancel</button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function showApproveDHModal() {
    document.getElementById('approveDHModal').classList.remove('hidden');
    captureGeolocation('dh');
}
function closeApproveDHModal() {
    document.getElementById('approveDHModal').classList.add('hidden');
}
function showApproveManagementModal() {
    document.getElementById('approveManagementModal').classList.remove('hidden');
    captureGeolocation('mgmt');
}
function closeApproveManagementModal() {
    document.getElementById('approveManagementModal').classList.add('hidden');
}
function showApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.remove('hidden');
    captureGeolocation('exec');
}
function closeApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.add('hidden');
}
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

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
    statusEl.className = 'text-sm text-blue-400';
    fetch('https://ipapi.co/json/')
        .then(response => response.json())
        .then(data => {
            if (data.latitude && data.longitude) {
                document.getElementById(prefix + '_latitude').value = data.latitude;
                document.getElementById(prefix + '_longitude').value = data.longitude;
                document.getElementById(prefix + '_location').value = data.city || data.region || 'Unknown';
                statusEl.textContent = 'Location captured (IP-based): ' + data.latitude + ', ' + data.longitude + ' (' + (data.city || 'Unknown') + ')';
                statusEl.className = 'text-sm text-green-400';
                enableSubmitButton(submitBtn, true);
            } else {
                statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
                statusEl.className = 'text-sm text-yellow-400';
                enableSubmitButton(submitBtn, false);
            }
        })
        .catch(() => {
            statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
            statusEl.className = 'text-sm text-yellow-400';
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
    statusEl.className = 'text-sm text-blue-400';
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            document.getElementById(prefix + '_latitude').value = lat;
            document.getElementById(prefix + '_longitude').value = lng;
            getLocationName(lat, lng, prefix);
            statusEl.textContent = 'Location captured: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
            statusEl.className = 'text-sm text-green-400';
            enableSubmitButton(submitBtn, true);
        },
        function(error) {
            captureGeolocationByIP(prefix, statusEl, submitBtn);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function getLocationName(lat, lng, prefix) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
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
    .bg-gray-800, .bg-gray-700, .bg-blue-600, .bg-green-600, button, a {
        display: none !important;
    }
    .container {
        max-width: 100%;
    }
}
</style>
@endsection