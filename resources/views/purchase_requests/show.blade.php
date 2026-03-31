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
                    <label class="font-semibold text-gray-500">PR NO:</label>
                    @if($purchaseRequest->status === 'approved')
                        <a href="{{ route('purchase_requests.go_to_po', $purchaseRequest->id) }}" class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-blue-600 rounded hover:underline font-semibold" title="Click to go to PO">{{ $purchaseRequest->pr_no }}</a>
                    @else
                        <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $purchaseRequest->pr_no }}</span>
                    @endif
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

        @if($purchaseRequest->bom_id)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 text-blue-700 rounded-full w-10 h-10 flex items-center justify-center">
                        <span class="text-lg">🐔</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-blue-800 text-sm">BOM-Linked Purchase Request</h3>
                        <p class="text-xs text-blue-600 mt-0.5">
                            Cycle: <strong>{{ $purchaseRequest->bom_cycle_ref }}</strong>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($purchaseRequest->bom_total_cost)
                    <div class="text-right">
                        <div class="text-xs text-blue-500 font-semibold uppercase">BOM Total Cost</div>
                        <div class="text-lg font-bold text-blue-800">PHP {{ number_format($purchaseRequest->bom_total_cost, 2) }}</div>
                    </div>
                    @endif
                    <a href="{{ route('inhouse_bom.show', $purchaseRequest->bom_id) }}"
                       class="px-4 py-2 text-sm border border-blue-300 rounded-md hover:bg-blue-100 text-blue-700 font-medium">
                        <i class="fas fa-clipboard-list mr-1"></i> View BOM
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Company -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-2">COMPANY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->company }}</p>
        </div>

        <!-- Form Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">REQUISITIONER:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->requisitioner }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">DEPARTMENT:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->department ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">TERMS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->terms ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">DELIVERY ADDRESS:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->delivery_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">CONTACT PERSON:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->contact_person ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">DATE OF REQUEST:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->date_of_request->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">DATE NEEDED:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->date_needed ? $purchaseRequest->date_needed->format('F d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">TYPE OF REQUEST:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->type_of_request ? ucfirst($purchaseRequest->type_of_request) : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">WITH BUDGET:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->with_budget ? ucfirst($purchaseRequest->with_budget) : 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">CHARGE TO:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->charge_to ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">CONTACT NUMBER:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->contact_number ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">CREATED BY:</label>
                    <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $purchaseRequest->creator->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white mb-2">Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-700">
                    <thead class="bg-gray-700 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="border border-gray-700 px-4 py-3">NO.</th>
                            <th class="border border-gray-700 px-4 py-3">ITEM CODE</th>
                            <th class="border border-gray-700 px-4 py-3">DATE NEEDED</th>
                            <th class="border border-gray-700 px-4 py-3">QTY</th>
                            <th class="border border-gray-700 px-4 py-3">UOM</th>
                            <th class="border border-gray-700 px-4 py-3">DESCRIPTION</th>
                            <th class="border border-gray-700 px-4 py-3">SUPPLIER</th>
                            <th class="border border-gray-700 px-4 py-3">UNIT PRICE</th>
                            <th class="border border-gray-700 px-4 py-3">AMOUNT</th>
                            <th class="border border-gray-700 px-4 py-3">REMARKS/SPECIFICATIONS</th>
                            <th class="border border-gray-700 px-4 py-3">NOTE</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-500 divide-y divide-gray-700">
                        @foreach($purchaseRequest->items as $item)
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-4 py-3 text-center">{{ $item->item_no }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->item_code ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->date_needed ? \Carbon\Carbon::parse($item->date_needed)->format('M d, Y') : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ number_format($item->qty, 2) }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->uom }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->description }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->supplier_name ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->unit_price ? '₱' . number_format($item->unit_price, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">{{ $item->amount ? '₱' . number_format($item->amount, 2) : 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->remarks ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->note ?? '' }}</td>
                            </tr>
                        @endforeach
                        @if($purchaseRequest->items->count() > 0)
                            <tr class="font-semibold bg-gray-700">
                                <td colspan="8" class="border border-gray-700 px-4 py-3 text-right">TOTAL:</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">₱{{ number_format($purchaseRequest->items->sum('amount'), 2) }}</td>
                                <td colspan="2" class="border border-gray-700 px-4 py-3"></td>
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
                <table class="w-full" style="table-layout: fixed;">
                    <colgroup>
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Prepared By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Noted By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm" colspan="3">Approved By:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseRequest->creator->name ?? '' }}</span>
                                @if($purchaseRequest->creator && $purchaseRequest->created_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $purchaseRequest->created_at->format('d M Y | H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseRequest->departmentHeadApprover->name ?? '' }}</span>
                                @if($purchaseRequest->departmentHeadApprover && $purchaseRequest->department_head_approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $purchaseRequest->department_head_approved_at->format('d M Y | H:i') }}
                                        @if($purchaseRequest->department_head_approved_latitude && $purchaseRequest->department_head_approved_longitude)
                                            <br>Coords: {{ $purchaseRequest->department_head_approved_latitude }}, {{ $purchaseRequest->department_head_approved_longitude }}
                                            @if($purchaseRequest->department_head_approved_location)
                                                ({{ $purchaseRequest->department_head_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseRequest->managementApprover->name ?? '' }}</span>
                                @if($purchaseRequest->managementApprover && $purchaseRequest->management_approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $purchaseRequest->management_approved_at->format('d M Y | H:i') }}
                                        @if($purchaseRequest->management_approved_latitude && $purchaseRequest->management_approved_longitude)
                                            <br>Coords: {{ $purchaseRequest->management_approved_latitude }}, {{ $purchaseRequest->management_approved_longitude }}
                                            @if($purchaseRequest->management_approved_location)
                                                ({{ $purchaseRequest->management_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom"></td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $purchaseRequest->approver->name ?? '' }}</span>
                                @if($purchaseRequest->approver && $purchaseRequest->approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $purchaseRequest->approved_at->format('d M Y | H:i') }}
                                        @if($purchaseRequest->approved_latitude && $purchaseRequest->approved_longitude)
                                            <br>Coords: {{ $purchaseRequest->approved_latitude }}, {{ $purchaseRequest->approved_longitude }}
                                            @if($purchaseRequest->approved_location)
                                                ({{ $purchaseRequest->approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-gray-700 text-gray-500 text-xs italic">
                            <td class="border border-gray-700 px-4 py-2 text-center">Requisitioner</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Department Head</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">General Manager</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">CFO</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Vice-President/President</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Trail -->
        <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
            <h3 class="text-lg font-semibold text-white mb-4">Approval Trail</h3>
            <div class="space-y-3">
                <!-- Department Head Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($purchaseRequest->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Department Head Approval</span>
                            @if($purchaseRequest->department_head_approved_by && $purchaseRequest->departmentHeadApprover)
                                <span class="text-green-700">✓ Approved</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $purchaseRequest->departmentHeadApprover->name }}
                                    on {{ $purchaseRequest->department_head_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @else
                                <span class="text-yellow-700">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Management Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($purchaseRequest->management_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($purchaseRequest->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
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
                            <span class="font-semibold">Management Approval (GM)</span>
                            @if($purchaseRequest->management_approved_by && $purchaseRequest->managementApprover)
                                <span class="text-green-700">✓ Approved</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $purchaseRequest->managementApprover->name }}
                                    on {{ $purchaseRequest->management_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @elseif($purchaseRequest->department_head_approved_by)
                                <span class="text-yellow-700">Pending</span>
                            @else
                                <span class="text-gray-300">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Executive Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($purchaseRequest->status === 'approved' && $purchaseRequest->approver)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($purchaseRequest->management_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
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
                            <span class="font-semibold">Executive Approval (President/VP)</span>
                            @if($purchaseRequest->status === 'approved' && $purchaseRequest->approver)
                                <span class="text-green-700">✓ Approved</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $purchaseRequest->approver->name }}
                                    on {{ $purchaseRequest->approved_at->format('M d, Y h:i A') }}
                                </small>
                            @elseif($purchaseRequest->management_approved_by)
                                <span class="text-yellow-700">Pending</span>
                            @else
                                <span class="text-gray-300">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($purchaseRequest->status === 'rejected')
                    <div class="flex items-start gap-4 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-700">
                                <span class="font-semibold">Rejected</span>
                                @if($purchaseRequest->approver)
                                    <br>
                                    <small class="text-red-700">
                                        {{ $purchaseRequest->approver->name }}
                                        on {{ $purchaseRequest->approved_at->format('M d, Y h:i A') }}
                                    </small>
                                @endif
                            </p>
                            @if($purchaseRequest->rejection_reason)
                                <p class="text-gray-500 mt-2">
                                    <strong>Reason:</strong> {{ $purchaseRequest->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Stage-Aware Approval Actions -->
        @if($purchaseRequest->status === 'pending')
            <!-- Department Head Approval -->
            @if($purchaseRequest->approval_stage === 'pending_dh' && auth()->user()->canApprovePurchaseRequestsAsDH())
                <div class="flex gap-3 mb-4 mt-6">
                    <button type="button" onclick="showApproveDHModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Department Head
                    </button>
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                </div>
            @endif

            <!-- Management Approval -->
            @php
                $canApproveManagement = auth()->user()->canApprovePurchaseRequestsAsManagement();
                // PMAI requires CFO specifically at management level
                if ($canApproveManagement && str_contains(strtolower($purchaseRequest->company ?? ''), 'magalang')) {
                    $canApproveManagement = auth()->user()->isAdminUser() || auth()->user()->canApprovePurchaseRequestsAsManagement();
                }
            @endphp
            @if($purchaseRequest->approval_stage === 'pending_management' && $canApproveManagement)
                <div class="flex gap-3 mb-4 mt-6">
                    <button type="button" onclick="showApproveManagementModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as {{ str_contains(strtolower($purchaseRequest->company ?? ''), 'magalang') ? 'CFO' : 'Management' }}
                    </button>
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                </div>
            @endif

            <!-- Executive Approval -->
            @if($purchaseRequest->approval_stage === 'pending_executive' && auth()->user()->canApprovePurchaseRequestsAsExecutive())
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

        <!-- Form Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('purchase_requests.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-4">
                @if($purchaseRequest->status === 'approved')
                <a href="{{ route('purchase_requests.go_to_po', $purchaseRequest->id) }}" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-file-invoice mr-1"></i> Go to PO
                </a>
                <a href="{{ route('purchase_requests.edit', $purchaseRequest->id) }}" class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-yellow-700 transition">
                    <i class="fas fa-sticky-note mr-1"></i> Edit Notes
                </a>
                @elseif($purchaseRequest->status !== 'rejected')
                <a href="{{ route('purchase_requests.edit', $purchaseRequest->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                @endif
                <a href="{{ route('purchase_requests.print', $purchaseRequest->id) }}" target="_blank" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
                @if(auth()->id() === $purchaseRequest->created_by || auth()->user()->isAdminUser() || auth()->user()->canApprovePurchaseRequestsAsDH())
                    <button type="button" onclick="confirmDelete()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Department Head Approval Modal -->
<div id="approveDHModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Department Head</h3>
        <form action="{{ route('purchase_requests.approve_dh', $purchaseRequest->id) }}" method="POST" id="approveDHForm">
            @csrf
            <input type="hidden" name="latitude" id="dh_latitude">
            <input type="hidden" name="longitude" id="dh_longitude">
            <input type="hidden" name="location" id="dh_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="dh_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveDHModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
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
        <h3 class="text-xl font-bold text-white mb-4">Approve as {{ str_contains(strtolower($purchaseRequest->company ?? ''), 'magalang') ? 'CFO' : 'Management' }}</h3>
        <form action="{{ route('purchase_requests.approve_management', $purchaseRequest->id) }}" method="POST" id="approveManagementForm">
            @csrf
            <input type="hidden" name="latitude" id="mgmt_latitude">
            <input type="hidden" name="longitude" id="mgmt_longitude">
            <input type="hidden" name="location" id="mgmt_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="mgmt_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveManagementModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
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
        <form action="{{ route('purchase_requests.approve', $purchaseRequest->id) }}" method="POST" id="approveExecutiveForm">
            @csrf
            <input type="hidden" name="latitude" id="exec_latitude">
            <input type="hidden" name="longitude" id="exec_longitude">
            <input type="hidden" name="location" id="exec_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="exec_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveExecutiveModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
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
        <h3 class="text-xl font-bold text-white mb-4">Reject Purchase Request</h3>
        <form action="{{ route('purchase_requests.reject', $purchaseRequest->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-500 mb-2">Rejection Reason (Optional):</label>
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

<!-- Delete Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Delete Purchase Request</h3>
        <p class="text-gray-500 mb-6">Are you sure you want to delete this Purchase Request? This action cannot be undone.</p>
        <form action="{{ route('purchase_requests.destroy', $purchaseRequest->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeDeleteModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Department Head Approval
function showApproveDHModal() {
    document.getElementById('approveDHModal').classList.remove('hidden');
    captureGeolocation('dh');
}
function closeApproveDHModal() {
    document.getElementById('approveDHModal').classList.add('hidden');
}

// Management Approval
function showApproveManagementModal() {
    document.getElementById('approveManagementModal').classList.remove('hidden');
    captureGeolocation('mgmt');
}
function closeApproveManagementModal() {
    document.getElementById('approveManagementModal').classList.add('hidden');
}

// Executive Approval
function showApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.remove('hidden');
    captureGeolocation('exec');
}
function closeApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.add('hidden');
}

// Reject
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Delete
function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
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

// IP-based geolocation fallback
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
        // No browser geolocation, try IP-based
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

            // Try to get location name via reverse geocoding (optional)
            getLocationName(lat, lng, prefix);

            statusEl.textContent = 'Location captured: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
            statusEl.className = 'text-sm text-green-700';

            enableSubmitButton(submitBtn, true);
        },
        function(error) {
            // Browser geolocation failed, try IP-based fallback
            captureGeolocationByIP(prefix, statusEl, submitBtn);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

// Get location name from coordinates (using OpenStreetMap Nominatim)
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
            // Silently fail if reverse geocoding doesn't work
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
