@extends('layouts.app')

@section('title', 'View Supply Receiving Report')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">SUPPLY RECEIVING REPORT</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-500">SRR CODE:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $report->srr_code }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($report->status === 'draft') bg-gray-600 text-white
                    @elseif($report->status === 'saved') bg-teal-600 text-white
                    @elseif($report->status === 'pending') bg-yellow-600 text-white
                    @elseif($report->status === 'approved') bg-green-600 text-white
                    @elseif($report->status === 'rejected') bg-red-600 text-white
                    @endif">
                    {{ ucfirst($report->status) }}
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

        @if($report->status === 'approved' && $report->approver)
            <div class="p-4 bg-green-50 border border-green-200 rounded mb-4">
                <p class="text-green-700">
                    <i class="fas fa-check-circle mr-1"></i>
                    Approved by {{ $report->approver->name }}
                    on {{ $report->approved_at->format('M d, Y h:i A') }}
                </p>
            </div>
        @endif

        @if($report->status === 'rejected' && $report->approver)
            <div class="p-4 bg-red-50 border border-red-200 rounded mb-4">
                <p class="text-red-700">
                    <i class="fas fa-times-circle mr-1"></i>
                    Rejected by {{ $report->approver->name }}
                    on {{ $report->approved_at->format('M d, Y h:i A') }}
                </p>
                @if($report->rejection_reason)
                    <p class="text-gray-500 mt-2">
                        <strong>Reason:</strong> {{ $report->rejection_reason }}
                    </p>
                @endif
            </div>
        @endif

        <!-- Report Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-3">
                <div>
                    <label class="block font-semibold text-gray-500 text-sm">DATE:</label>
                    <p class="text-gray-200">{{ $report->report_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 text-sm">SUPPLY:</label>
                    <p class="text-gray-200">{{ $report->supplier_name }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 text-sm">CV NO:</label>
                    <p class="text-gray-200">{{ $report->cv_no ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 text-sm">CREATED BY:</label>
                    <p class="text-gray-200">{{ $report->creator->name ?? 'N/A' }} - {{ $report->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block font-semibold text-gray-500 text-sm">STORAGE:</label>
                    <p class="text-gray-200">{{ $report->storage ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 text-sm">PO NO:</label>
                    <p class="text-gray-200">{{ $report->po_no ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 text-sm">TYPE:</label>
                    <span class="px-3 py-1 rounded text-sm bg-blue-100/40 border border-blue-700 text-blue-700">
                        {{ ucfirst(str_replace('_', ' ', $report->report_type)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <h3 class="font-semibold text-lg text-gray-500 mb-3">ITEMS</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-700">
                    <thead class="bg-gray-700 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="border border-gray-700 px-3 py-2">#</th>
                            <th class="border border-gray-700 px-3 py-2">ITEM CODE</th>
                            <th class="border border-gray-700 px-3 py-2">ITEM DESCRIPTION</th>
                            <th class="border border-gray-700 px-3 py-2">BRAND</th>
                            <th class="border border-gray-700 px-3 py-2">NO. OF BOXES</th>
                            <th class="border border-gray-700 px-3 py-2">NET WEIGHT</th>
                            <th class="border border-gray-700 px-3 py-2">PRODUCTION DATE</th>
                            <th class="border border-gray-700 px-3 py-2">EXPIRATION DATE</th>
                            <th class="border border-gray-700 px-3 py-2">PALLET NO.</th>
                            <th class="border border-gray-700 px-3 py-2">REMARKS</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-500">
                        @foreach($report->items as $item)
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-3 py-2 text-center">{{ $item->item_no }}</td>
                                <td class="border border-gray-700 px-3 py-2">{{ $item->item_code ?? '-' }}</td>
                                <td class="border border-gray-700 px-3 py-2">{{ $item->item_description }}</td>
                                <td class="border border-gray-700 px-3 py-2">{{ $item->brand ?? '-' }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-center">{{ $item->no_of_boxes }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-center">{{ number_format($item->net_weight, 2) }}</td>
                                <td class="border border-gray-700 px-3 py-2 text-center">{{ $item->pd ? $item->pd->format('M d, Y') : '-' }}</td>
                                <td class="border border-gray-700 px-3 py-2">{{ $item->expiry_date ? $item->expiry_date->format('M d, Y') : '-' }}</td>
                                <td class="border border-gray-700 px-3 py-2">{{ $item->pallet_no ?? '-' }}</td>
                                <td class="border border-gray-700 px-3 py-2">{{ $item->remarks ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-700">
                        <tr>
                            <td colspan="4" class="border border-gray-700 px-4 py-2 text-right font-semibold text-gray-500">TOTALS:</td>
                            <td class="border border-gray-700 px-4 py-2 text-center font-semibold text-white">{{ $report->items->sum('no_of_boxes') }}</td>
                            <td class="border border-gray-700 px-4 py-2 text-center font-semibold text-white">{{ number_format($report->items->sum('net_weight'), 2) }}</td>
                            <td colspan="4" class="border border-gray-700"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Note -->
        @if($report->note)
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 text-sm mb-1">NOTE:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $report->note }}</p>
            </div>
        @endif

        <!-- Signature Fields -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 text-sm">PREPARED BY:</label>
                <p class="text-gray-200">{{ $report->prepared_by ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 text-sm">CHECKED BY:</label>
                <p class="text-gray-200">{{ $report->checked_by ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 text-sm">RECEIVED BY:</label>
                <p class="text-gray-200">{{ $report->received_by ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 text-sm">VERIFIED BY:</label>
                <p class="text-gray-200">{{ $report->verified_by ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center border-t border-gray-700 pt-4">
            <a href="{{ route('supplier_receiving_reports.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-3">
                <a href="{{ route('supplier_receiving_reports.print', $report->id) }}" target="_blank" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
                @if(!$report->isApproved())
                    <a href="{{ route('supplier_receiving_reports.edit', $report->id) }}" class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-yellow-700 transition">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
                @if($report->isDraft())
                    <form action="{{ route('supplier_receiving_reports.destroy', $report->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this report?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
