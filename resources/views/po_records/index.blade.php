@extends('layouts.app')

@section('title', 'PO Records')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">PO RECORDS</h1>
            <a href="{{ route('po_records.export', ['type' => $type, 'status' => $status, 'from' => $from, 'to' => $to]) }}"
               class="bg-gradient-to-r from-green-600 to-green-700 text-white px-4 py-2 rounded hover:from-green-700 hover:to-green-800 transition">
                <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
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

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-700 pb-4">
            @if(in_array('purchase_requests', $allowedTabs))
            <a href="{{ route('po_records.index', ['type' => 'purchase_requests', 'status' => $status, 'from' => $from, 'to' => $to]) }}"
               class="px-4 py-2 rounded-t text-sm font-semibold transition
                {{ $type === 'purchase_requests' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-500 hover:bg-gray-700' }}">
                Purchase Requests
            </a>
            @endif

            @if(in_array('purchase_orders', $allowedTabs))
            <a href="{{ route('po_records.index', ['type' => 'purchase_orders', 'status' => $status, 'from' => $from, 'to' => $to]) }}"
               class="px-4 py-2 rounded-t text-sm font-semibold transition
                {{ $type === 'purchase_orders' ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-500 hover:bg-gray-700' }}">
                Purchase Orders
            </a>
            @endif

            @if(in_array('rfp', $allowedTabs))
            <a href="{{ route('po_records.index', ['type' => 'rfp', 'status' => $status, 'from' => $from, 'to' => $to]) }}"
               class="px-4 py-2 rounded-t text-sm font-semibold transition
                {{ $type === 'rfp' ? 'bg-orange-600 text-white' : 'bg-gray-700 text-gray-500 hover:bg-gray-700' }}">
                Request for Payment
            </a>
            @endif

            @if(in_array('apv', $allowedTabs))
            <a href="{{ route('po_records.index', ['type' => 'apv', 'status' => $status, 'from' => $from, 'to' => $to]) }}"
               class="px-4 py-2 rounded-t text-sm font-semibold transition
                {{ $type === 'apv' ? 'bg-teal-600 text-white' : 'bg-gray-700 text-gray-500 hover:bg-gray-700' }}">
                AP Voucher
            </a>
            @endif

            @if(in_array('check_vouchers', $allowedTabs))
            <a href="{{ route('po_records.index', ['type' => 'check_vouchers', 'status' => $status, 'from' => $from, 'to' => $to]) }}"
               class="px-4 py-2 rounded-t text-sm font-semibold transition
                {{ $type === 'check_vouchers' ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-500 hover:bg-gray-700' }}">
                Check Vouchers
            </a>
            @endif
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('po_records.index') }}" class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Status</label>
                    <select name="status" class="bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-500 text-sm mb-1">From Date</label>
                    <input type="date" name="from" value="{{ $from }}"
                           class="bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-gray-500 text-sm mb-1">To Date</label>
                    <input type="date" name="to" value="{{ $to }}"
                           class="bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
                <div>
                    <a href="{{ route('po_records.index', ['type' => $type]) }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-600 transition inline-block">
                        <i class="fas fa-times mr-1"></i> Clear
                    </a>
                </div>
            </div>
        </form>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-gray-900 border border-gray-700">
                <thead>
                    <tr class="bg-gray-700">
                        @if($type === 'purchase_requests')
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">PR No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Company</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Requisitioner</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Supplier</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Date of Request</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Status</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Created By</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Actions</th>
                        @elseif($type === 'purchase_orders')
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">PO No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">PR No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Company</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Supplier</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Order Date</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Status</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Created By</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Actions</th>
                        @elseif($type === 'rfp')
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">RFP No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">PO No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Company</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Payee</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-right text-gray-500">Amount</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Date</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Status</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Created By</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Actions</th>
                        @elseif($type === 'apv')
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">APV No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">RFP No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Vendor</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Payment Type</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-right text-gray-500">Grand Total</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Status</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Created By</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Actions</th>
                        @elseif($type === 'check_vouchers')
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">CV No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">APV No</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Supplier</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Bank</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-right text-gray-500">Check Amount</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Status</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-500">Created By</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-500">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-700 transition">
                            @if($type === 'purchase_requests')
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->pr_no }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->company }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->requisitioner }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->supplier->supplier_name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->date_of_request }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    @include('po_records._status_badge', ['status' => $record->status])
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->creator->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('purchase_requests.show', $record->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">View</a>
                                        @if($record->status === 'approved')
                                            <a href="{{ route('purchase_requests.print', $record->id) }}" target="_blank" class="bg-gray-600 text-white px-3 py-1 rounded text-xs hover:bg-gray-600 transition"><i class="fas fa-print"></i> Print</a>
                                        @else
                                            <span class="bg-gray-700 text-gray-500 px-3 py-1 rounded text-xs cursor-not-allowed" title="Must be approved to print"><i class="fas fa-print"></i> Print</span>
                                        @endif
                                    </div>
                                </td>
                            @elseif($type === 'purchase_orders')
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->po_no }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->pr_no ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->company }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->supplier ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->order_date->format('M d, Y') }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    @include('po_records._status_badge', ['status' => $record->status])
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->creator->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('purchase_orders.show', $record->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">View</a>
                                        @if($record->status === 'approved')
                                            <a href="{{ route('purchase_orders.print', $record->id) }}" target="_blank" class="bg-gray-600 text-white px-3 py-1 rounded text-xs hover:bg-gray-600 transition"><i class="fas fa-print"></i> Print</a>
                                        @else
                                            <span class="bg-gray-700 text-gray-500 px-3 py-1 rounded text-xs cursor-not-allowed" title="Must be approved to print"><i class="fas fa-print"></i> Print</span>
                                        @endif
                                    </div>
                                </td>
                            @elseif($type === 'rfp')
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->rfp_no }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->purchaseOrder->po_no ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->company }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->payee }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-right text-gray-500">{{ number_format($record->amount, 2) }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->date }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    @include('po_records._status_badge', ['status' => $record->status])
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->creator->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('request_for_payments.show', $record->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">View</a>
                                        <button onclick="window.open('{{ route('request_for_payments.show', $record->id) }}', '_blank', 'width=900,height=700')" class="bg-gray-600 text-white px-3 py-1 rounded text-xs hover:bg-gray-600 transition"><i class="fas fa-print"></i> Print</button>
                                    </div>
                                </td>
                            @elseif($type === 'apv')
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->apv_no }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->requestForPayment->rfp_no ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->vendor_name }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">
                                    @if($record->payment_type === 'downpayment')
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Downpayment</span>
                                    @else
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Full Payment</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-right text-gray-500">{{ $record->currency }} {{ number_format($record->grand_total, 2) }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    @include('po_records._status_badge', ['status' => $record->status])
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->creator->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('accounts_payable_invoices.show', $record->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">View</a>
                                        <button onclick="window.open('{{ route('accounts_payable_invoices.show', $record->id) }}', '_blank', 'width=900,height=700')" class="bg-gray-600 text-white px-3 py-1 rounded text-xs hover:bg-gray-600 transition"><i class="fas fa-print"></i> Print</button>
                                    </div>
                                </td>
                            @elseif($type === 'check_vouchers')
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->cv_no }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">
                                    @if($record->apv_no)
                                        <span class="text-blue-700">{{ $record->apv_no }}</span>
                                    @else
                                        <span class="text-gray-300">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->supplier_name }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->bank ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-right text-gray-500">{{ number_format($record->check_amount, 2) }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    @include('po_records._status_badge', ['status' => $record->status])
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-500">{{ $record->creator->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('check_vouchers.show', $record->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">View</a>
                                        <button onclick="window.open('{{ route('check_vouchers.show', $record->id) }}', '_blank', 'width=900,height=700')" class="bg-gray-600 text-white px-3 py-1 rounded text-xs hover:bg-gray-600 transition"><i class="fas fa-print"></i> Print</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                No records found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $records->appends(['type' => $type, 'status' => $status, 'from' => $from, 'to' => $to])->links() }}
        </div>
    </div>
</div>
@endsection
