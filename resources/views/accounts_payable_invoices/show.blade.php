@extends('layouts.app')

@section('title', 'View Accounts Payable Invoice')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Action Buttons -->
        <div class="flex justify-between items-center mb-6">
            @if(request('from_mapping'))
                <a href="{{ route('mapping.index') }}" class="bg-purple-700 text-white px-4 py-2 rounded hover:bg-purple-800 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Mapping
                </a>
            @else
                <a href="{{ route('accounts_payable_invoices.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
            @endif
            <div class="flex gap-2">
                @if($invoice->status === 'pending')
                    <a href="{{ route('accounts_payable_invoices.edit', $invoice->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
                <a href="{{ route('accounts_payable_invoices.print', $invoice->id) }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition inline-block">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
                @if(auth()->user()->canApproveAPVAsDH() || auth()->user()->canApproveAPV())
                    <button type="button" onclick="confirmDelete()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                @endif
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

        <!-- Invoice Content -->
        <div id="printableInvoice" class="bg-gray-800 text-white p-8 rounded">
            <!-- Header -->
            <div class="border-b-2 border-gray-600 pb-4 mb-6">
                <div class="text-center mb-4">
                    <h1 class="text-3xl font-bold">ACCOUNTS PAYABLE VOUCHER</h1>
                    <p class="text-sm text-gray-300 mt-1">APV No: {{ $invoice->apv_no }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p><strong>APV Date:</strong> {{ $invoice->apv_date?->format('F d, Y') ?? '—' }}</p>
                        <p><strong>Document Date:</strong> {{ $invoice->document_date?->format('F d, Y') ?? '—' }}</p>
                        @if($invoice->due_date)
                            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('F d, Y') }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        @if($invoice->requestForPayment)
                            <p><strong>RFP No:</strong> {{ $invoice->requestForPayment->rfp_no }}</p>
                        @endif
                        @if($invoice->purchase_order_no)
                            <p><strong>PO No:</strong> {{ $invoice->purchase_order_no }}</p>
                        @endif
                        @if($invoice->reference_no)
                            <p><strong>Reference No:</strong> {{ $invoice->reference_no }}</p>
                        @endif
                        <p><strong>Payment Type:</strong>
                            <span class="px-2 py-1 {{ $invoice->payment_type === 'downpayment' ? 'bg-blue-700' : 'bg-green-700' }} rounded">
                                {{ $invoice->payment_type === 'downpayment' ? 'Downpayment' : 'Full Payment' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">VENDOR INFORMATION</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        @if($invoice->vendor_code)
                            <p><strong>Vendor Code:</strong> {{ $invoice->vendor_code }}</p>
                        @endif
                        <p><strong>Vendor Name:</strong> {{ $invoice->vendor_name }}</p>
                        @if($invoice->vendor_address)
                            <p><strong>Address:</strong> {{ $invoice->vendor_address }}</p>
                        @endif
                    </div>
                    <div>
                        @if($invoice->vendor_tin)
                            <p><strong>TIN:</strong> {{ $invoice->vendor_tin }}</p>
                        @endif
                        @if($invoice->payment_terms)
                            <p><strong>Payment Terms:</strong> {{ $invoice->payment_terms }}</p>
                        @endif
                        <p><strong>Currency:</strong> {{ $invoice->currency }}
                            @if($invoice->forex_rate && $invoice->currency !== 'PHP')
                                (Rate: {{ number_format($invoice->forex_rate, 4) }})
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- PO / GRPO Reference Items -->
            @if($grpos->isNotEmpty() || ($invoice->requestForPayment?->purchaseOrder?->items?->isNotEmpty()))
            @php
                $po       = $invoice->requestForPayment?->purchaseOrder ?? null;
                $useGrpo  = $grpos->isNotEmpty();
                $poItems  = $useGrpo
                    ? $grpos->map(fn($g,$i) => (object)['no'=>$i+1,'item_code'=>$g->grpo_no??'','description'=>$g->items??'','brand'=>$g->brand??'','qty'=>$g->actual_qty,'uom'=>$g->uom??'KG','unit_price'=>$g->price??0,'total'=>($g->actual_qty??0)*($g->price??0)])
                    : ($po?->items->map(fn($item,$i) => (object)['no'=>$item->item_no??$i+1,'item_code'=>$item->item_code??'','description'=>$item->description??'','brand'=>$item->brand??'','qty'=>$item->qty,'uom'=>$item->uom??'','unit_price'=>$item->unit_price,'total'=>$item->total]) ?? collect());
                $poNo = $po?->po_no ?? '';
                $currSym = ($invoice->currency ?? 'PHP') === 'USD' ? '$' : '₱';
            @endphp
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">
                    {{ $useGrpo ? 'GRPO ITEMS' : 'PURCHASE ORDER ITEMS' }}{{ $poNo ? ' — '.$poNo : '' }}
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-gray-700 text-white uppercase">
                            <tr>
                                <th class="border border-gray-600 px-2 py-2">#</th>
                                <th class="border border-gray-600 px-2 py-2">Item Code</th>
                                <th class="border border-gray-600 px-2 py-2">Description</th>
                                <th class="border border-gray-600 px-2 py-2">Brand</th>
                                <th class="border border-gray-600 px-2 py-2 text-center">Qty Received</th>
                                <th class="border border-gray-600 px-2 py-2 text-center">UOM</th>
                                <th class="border border-gray-600 px-2 py-2 text-right">Unit Price</th>
                                <th class="border border-gray-600 px-2 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($poItems as $pi)
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-600 px-2 py-1 text-center text-gray-400">{{ $pi->no }}</td>
                                <td class="border border-gray-600 px-2 py-1 font-mono">{{ $pi->item_code }}</td>
                                <td class="border border-gray-600 px-2 py-1">{{ $pi->description }}</td>
                                <td class="border border-gray-600 px-2 py-1">{{ $pi->brand }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-center">{{ number_format($pi->qty, 2) }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-center">{{ $pi->uom }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-right">{{ $currSym }}{{ number_format($pi->unit_price, 2) }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-right font-semibold">{{ $currSym }}{{ number_format($pi->total, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="bg-gray-700 font-bold">
                                <td colspan="7" class="border border-gray-600 px-2 py-1 text-right">Grand Total:</td>
                                <td class="border border-gray-600 px-2 py-1 text-right text-green-400">{{ $currSym }}{{ number_format($poItems->sum('total'), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Particulars & Accounting Items -->
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">PARTICULARS & ACCOUNTING</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-red-700 text-white uppercase">
                            <tr>
                                <th class="border border-gray-600 px-2 py-2">#</th>
                                <th class="border border-gray-600 px-2 py-2">PARTICULARS</th>
                                <th class="border border-gray-600 px-2 py-2">ITEM CODE</th>
                                <th class="border border-gray-600 px-2 py-2">DEPT</th>
                                <th class="border border-gray-600 px-2 py-2">DIVISION</th>
                                <th class="border border-gray-600 px-2 py-2">VAT</th>
                                <th class="border border-gray-600 px-2 py-2">TAX CODE</th>
                                <th class="border border-gray-600 px-2 py-2">ACCOUNT CODE</th>
                                <th class="border border-gray-600 px-2 py-2">ACCOUNT NAME</th>
                                <th class="border border-gray-600 px-2 py-2 text-right">DEBIT</th>
                                <th class="border border-gray-600 px-2 py-2 text-right">CREDIT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->items as $i => $item)
                            @php $amt = (float)$item->gross_amount; @endphp
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-600 px-2 py-1 text-center text-gray-400">{{ $i+1 }}</td>
                                <td class="border border-gray-600 px-2 py-1">{{ $item->particulars }}</td>
                                <td class="border border-gray-600 px-2 py-1 font-mono">{{ $item->item_code ?? '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1">{{ $ccNames[$item->department] ?? $item->department ?? '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1">{{ $ccNames[$item->division] ?? $item->division ?? '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-center">{{ $item->vat ? '✓' : '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-center font-mono">{{ $item->tax_code ?? '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1 font-mono">{{ $item->account_code ?? '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1">{{ $item->account_name ?? '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-right">{{ $amt > 0 ? number_format($amt, 2) : '—' }}</td>
                                <td class="border border-gray-600 px-2 py-1 text-right text-gray-400">{{ $amt < 0 ? number_format(abs($amt), 2) : '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="11" class="border border-gray-600 px-2 py-3 text-center text-gray-400">No items.</td></tr>
                            @endforelse
                            @if($invoice->w_tax_amount > 0)
                            <tr class="bg-gray-800/50">
                                <td class="border border-gray-600 px-2 py-1 text-center text-gray-400">—</td>
                                <td class="border border-gray-600 px-2 py-1 text-gray-400">EWT</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1 font-mono text-yellow-300">211300015</td>
                                <td class="border border-gray-600 px-2 py-1 text-yellow-300">Withholding Tax Payable - EWT</td>
                                <td class="border border-gray-600 px-2 py-1 text-right text-gray-400">—</td>
                                <td class="border border-gray-600 px-2 py-1 text-right text-yellow-300">{{ number_format($invoice->w_tax_amount, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="bg-gray-800/50">
                                <td class="border border-gray-600 px-2 py-1 text-center text-gray-400">—</td>
                                <td class="border border-gray-600 px-2 py-1 text-gray-400">AP</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1">—</td>
                                <td class="border border-gray-600 px-2 py-1 font-mono text-green-300">211100004</td>
                                <td class="border border-gray-600 px-2 py-1 text-green-300">Accounts Payable</td>
                                <td class="border border-gray-600 px-2 py-1 text-right text-gray-400">—</td>
                                <td class="border border-gray-600 px-2 py-1 text-right text-green-300">{{ number_format($invoice->total_before_vat, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Amount Summary -->
            <div class="mb-6 flex justify-end">
                <table class="text-sm w-72">
                    <tbody>
                        <tr><td class="py-1 pr-4 text-gray-300">Total Gross:</td><td class="py-1 text-right font-bold">{{ $invoice->currency }} {{ number_format($invoice->total_before_vat, 2) }}</td></tr>
                        <tr><td class="py-1 pr-4 text-gray-300">VAT (12%):</td><td class="py-1 text-right text-yellow-400">{{ $invoice->currency }} {{ number_format($invoice->vat_amount, 2) }}</td></tr>
                        <tr><td class="py-1 pr-4 text-gray-300">Net of VAT:</td><td class="py-1 text-right text-blue-400">{{ $invoice->currency }} {{ number_format($invoice->total_after_vat, 2) }}</td></tr>
                        <tr><td class="py-1 pr-4 text-gray-300">EWT:</td><td class="py-1 text-right text-red-400">({{ $invoice->currency }} {{ number_format($invoice->w_tax_amount, 2) }})</td></tr>
                        @if($invoice->payment_type === 'downpayment' && $invoice->downpayment_amount)
                        <tr><td class="py-1 pr-4 text-gray-300">Downpayment:</td><td class="py-1 text-right">{{ $invoice->currency }} {{ number_format($invoice->downpayment_amount, 2) }}</td></tr>
                        @endif
                        <tr class="border-t border-gray-500"><td class="pt-2 pr-4 font-bold text-white">Amount Due:</td><td class="pt-2 text-right font-bold text-green-400 text-base">{{ $invoice->currency }} {{ number_format($invoice->grand_total, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Remarks -->
            @if($invoice->remarks)
            <div class="mb-6">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-3">REMARKS</h2>
                <div class="bg-gray-900 p-4 rounded text-sm whitespace-pre-wrap">{{ $invoice->remarks }}</div>
            </div>
            @endif

            <!-- Signatures -->
            <div class="mt-8">
                <h2 class="text-lg font-bold border-b border-gray-400 pb-2 mb-4">APPROVALS</h2>
                <div class="grid grid-cols-3 gap-8 text-sm">
                    <div class="text-center">
                        <div class="border-b-2 border-black h-16 mb-2"></div>
                        <p class="font-semibold">{{ $invoice->creator->name ?? ($invoice->prepared_by ?? '___________________') }}</p>
                        <p class="text-xs text-gray-300">Prepared By</p>
                    </div>
                    <div class="text-center">
                        <div class="border-b-2 border-black h-16 mb-2"></div>
                        <p class="font-semibold">{{ $invoice->departmentHeadApprover->name ?? ($invoice->reviewed_by ?? '___________________') }}</p>
                        <p class="text-xs text-gray-300">Reviewed By (Dept Head)</p>
                        @if($invoice->departmentHeadApprover && $invoice->department_head_approved_at)
                            <p class="text-xs text-gray-300 italic mt-1">
                                @include('partials.esignature', ['signer' => $invoice->departmentHeadApprover])<br>
                                {{ $invoice->department_head_approved_at->format('d M Y | H:i') }}
                                @if($invoice->department_head_approved_latitude && $invoice->department_head_approved_longitude)
                                    <br>Coords: {{ $invoice->department_head_approved_latitude }}, {{ $invoice->department_head_approved_longitude }}
                                    @if($invoice->department_head_approved_location) ({{ $invoice->department_head_approved_location }}) @endif
                                @endif
                            </p>
                        @endif
                    </div>
                   <div class="text-center">
                    <div class="border-b-2 border-black h-16 mb-2 flex items-center justify-center">
                        @if($invoice->approver && $invoice->approver->esignature)
                            <img
                                src="{{ asset('storage/' . $invoice->approver->esignature) }}"
                                alt="E-Signature"
                                class="h-14 object-contain"
                            >
                        @endif
                    </div>

                    <p class="font-semibold">
                        {{ $invoice->approver->name ?? ($invoice->approved_by ?? '___________________') }}
                    </p>
                    <p class="text-xs text-gray-300">Approved By (Accounting Manager)</p>

                    @if($invoice->approver && $invoice->approved_at)
                        <p class="text-xs text-gray-300 italic mt-1">
                            @if(!$invoice->approver->esignature)
                                @include('partials.esignature', ['signer' => $invoice->approver])<br>
                            @endif
                            {{ $invoice->approved_at->format('d M Y | H:i') }}
                            @if($invoice->approved_latitude && $invoice->approved_longitude)
                                <br>Coords: {{ $invoice->approved_latitude }}, {{ $invoice->approved_longitude }}
                                @if($invoice->approved_location) ({{ $invoice->approved_location }}) @endif
                            @endif
                        </p>
                    @endif
                </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-4 border-t border-gray-600 text-xs text-gray-300">
                <div class="flex justify-between">
                    <div>
                        <p>Status:
                            <span class="font-semibold {{ $invoice->status === 'approved' ? 'text-green-600' : ($invoice->status === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        </p>
                        <p>Created by: {{ $invoice->creator->name ?? 'N/A' }} on {{ $invoice->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                    <div class="text-right">
                        @if($invoice->updated_by)
                            <p>Last updated by: {{ $invoice->updater->name ?? 'N/A' }}</p>
                            <p>on {{ $invoice->updated_at->format('F d, Y h:i A') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Trail -->
        <div class="mb-6 mt-6 p-4 bg-gray-900 border border-gray-700 rounded">
            <h3 class="text-lg font-semibold text-white mb-4">Approval Trail</h3>
            <div class="space-y-3">
                <!-- Department Head Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($invoice->department_head_approved_by)
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
                            <span class="font-semibold">Department Head Review</span>
                            @if($invoice->department_head_approved_by && $invoice->departmentHeadApprover)
                                <span class="text-green-700">✓ Reviewed</span>
                                <br>
                                <small class="text-gray-300">
                                    {{ $invoice->departmentHeadApprover->name }}
                                    on {{ $invoice->department_head_approved_at?->format('M d, Y h:i A') ?? '—' }}
                                </small>
                            @else
                                <span class="text-yellow-700">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($invoice->status === 'rejected')
                    <div class="flex items-start gap-4 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-700">
                                <span class="font-semibold">Rejected</span>
                            </p>
                            @if($invoice->rejection_reason)
                                <p class="text-gray-300 mt-2">
                                    <strong>Reason:</strong> {{ $invoice->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Approval Buttons -->
        @if($invoice->status === 'pending' && $invoice->approval_stage !== 'rejected')
            <div class="flex gap-3 mb-4">
                @if($invoice->approval_stage === 'pending_dh' && auth()->user()->canApproveAPVAsDH())
                    <button type="button" onclick="showApproveDHModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Department Head
                    </button>
                @endif

                @if(auth()->user()->canApproveAPVAsDH())
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Department Head Approval Modal -->
<div id="approveDHModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Department Head</h3>
        <form action="{{ route('accounts_payable_invoices.approve_dh', $invoice->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="dh_latitude">
            <input type="hidden" name="longitude" id="dh_longitude">
            <input type="hidden" name="location" id="dh_location">
            <div class="mb-4">
                <p class="text-gray-300 mb-2">Geolocation will be captured automatically.</p>
                <div id="dh_geolocation_status" class="text-sm text-gray-300">Waiting for location...</div>
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

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Invoice</h3>
        <form action="{{ route('accounts_payable_invoices.reject', $invoice->id) }}" method="POST">
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
// Department Head Approval
function showApproveDHModal() {
    document.getElementById('approveDHModal').classList.remove('hidden');
    captureGeolocation('dh');
}
function closeApproveDHModal() {
    document.getElementById('approveDHModal').classList.add('hidden');
}

// Reject
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
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
            getLocationName(lat, lng, prefix);
            statusEl.textContent = 'Location captured: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
            statusEl.className = 'text-sm text-green-700';
            enableSubmitButton(submitBtn, true);
        },
        function(error) {
            captureGeolocationByIP(prefix, statusEl, submitBtn);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function getLocationName(lat, lng, prefix) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
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
    body * {
        visibility: hidden;
    }
    #printableInvoice, #printableInvoice * {
        visibility: visible;
    }
    #printableInvoice {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
    }
    .bg-gray-800, .bg-gray-700, .bg-gray-600 {
        background-color: white !important;
    }
    button, .no-print {
        display: none !important;
    }
}
</style>
PMAI-20260601-0052-C1|2026-06-01 07:22:45
2
<!-- Delete Confirmation Modal -->
@if(auth()->user()->canApproveAPVAsDH() || auth()->user()->canApproveAPV())
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Delete Invoice</h3>
        <p class="text-gray-300 mb-4">Are you sure you want to delete invoice <strong>{{ $invoice->apv_no }}</strong>? This action cannot be undone.</p>
        <form action="{{ route('accounts_payable_invoices.destroy', $invoice->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeDeleteModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    <i class="fas fa-trash mr-1"></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
@endif
@endsection