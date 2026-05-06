@extends('layouts.app')
@section('title', 'Document Mapping')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">DOCUMENT MAPPING</h1>
            <span class="text-xs text-gray-400">PR → PO → RFP → APV → SRR</span>
        </div>

        <form method="GET" class="mb-4 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search by PO, PR, RFP, APV, SRR number or supplier..."
                class="flex-1 bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            @if(request('search'))
                <a href="{{ route('mapping.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-xs">
                <thead class="bg-gray-700 text-gray-300 uppercase">
                    <tr>
                        <th class="border border-gray-600 px-3 py-2 text-left">SUPPLIER</th>
                        <th class="border border-gray-600 px-3 py-2 text-center">PR</th>
                        <th class="border border-gray-600 px-3 py-2 text-center">PO</th>
                        <th class="border border-gray-600 px-3 py-2 text-center">RFP</th>
                        <th class="border border-gray-600 px-3 py-2 text-center">APV</th>
                        <th class="border border-gray-600 px-3 py-2 text-center">SRR</th>
                        <th class="border border-gray-600 px-3 py-2 text-center">PO STATUS</th>
                        <th class="border border-gray-600 px-3 py-2 text-center">DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pos as $po)
                    <tr class="border-b border-gray-700 hover:bg-gray-700/40 align-top">
                        {{-- Supplier --}}
                        <td class="border border-gray-700 px-3 py-2 text-gray-200 max-w-xs">
                            {{ $po->supplierModel->supplier_name ?? $po->supplier ?? '—' }}
                        </td>

                        {{-- PR --}}
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @if($po->purchaseRequest)
                                <a href="{{ route('purchase_requests.show', $po->purchaseRequest->id) }}?from_mapping=1"
                                   class="text-blue-400 hover:underline font-mono">{{ $po->purchaseRequest->pr_no }}</a>
                            @elseif($po->pr_no)
                                <span class="text-gray-400 font-mono">{{ $po->pr_no }}</span>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>

                        {{-- PO --}}
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            <a href="{{ route('purchase_orders.show', $po->id) }}?from_mapping=1"
                               class="text-purple-400 hover:underline font-mono font-semibold">{{ $po->po_no }}</a>
                        </td>

                        {{-- RFP --}}
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @if($po->rfps->count())
                                <div class="space-y-1">
                                    @foreach($po->rfps as $rfp)
                                        <div>
                                            <a href="{{ route('request_for_payments.show', $rfp->id) }}?from_mapping=1"
                                               class="text-yellow-400 hover:underline font-mono">{{ $rfp->rfp_no }}</a>
                                            @php $badge = match($rfp->status ?? '') { 'approved' => 'bg-green-700', 'rejected' => 'bg-red-700', default => 'bg-gray-600' }; @endphp
                                            <span class="ml-1 px-1 rounded text-white text-xs {{ $badge }}">{{ $rfp->status ?? 'pending' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>

                        {{-- APV --}}
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @php
                                $allApvs = $po->rfps->flatMap(fn($r) => $r->apvs);
                            @endphp
                            @if($allApvs->count())
                                <div class="space-y-1">
                                    @foreach($allApvs as $apv)
                                        <div>
                                            <a href="{{ route('accounts_payable_invoices.show', $apv->id) }}?from_mapping=1"
                                               class="text-green-400 hover:underline font-mono">{{ $apv->apv_no }}</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>

                        {{-- SRR --}}
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @if($po->srrs->count())
                                <div class="space-y-1">
                                    @foreach($po->srrs as $srr)
                                        <div>
                                            <a href="{{ route('supplier_receiving_reports.show', $srr->id) }}?from_mapping=1"
                                               class="text-orange-400 hover:underline font-mono">{{ $srr->srr_code }}</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>

                        {{-- PO Status --}}
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @php
                                $sc = match($po->status) { 'approved' => 'bg-green-700', 'rejected' => 'bg-red-700', 'pending' => 'bg-yellow-700', default => 'bg-gray-600' };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-white text-xs {{ $sc }}">{{ $po->status }}</span>
                        </td>

                        {{-- Date --}}
                        <td class="border border-gray-700 px-3 py-2 text-center text-gray-400">
                            {{ $po->order_date ? $po->order_date->format('Y-m-d') : ($po->created_at ? $po->created_at->format('Y-m-d') : '—') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">No records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $pos->links() }}</div>
    </div>
</div>
@endsection
