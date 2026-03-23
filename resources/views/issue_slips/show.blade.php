@extends('layouts.app')

@section('title', 'Issue Slip - ' . $issueSlip->issue_slip_number)

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">ISSUE SLIP</h1>
            <div class="flex gap-3">
                <a href="{{ route('issue_slips.print', $issueSlip->id) }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700" target="_blank">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('issue_slips.edit', $issueSlip->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <a href="{{ route('issue_slips.index') }}" class="bg-gray-100 text-gray-800 px-4 py-2 rounded hover:bg-gray-100">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <!-- Info Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-2">
                <p class="text-gray-400"><span class="font-semibold text-gray-500">Issue Slip No:</span> <span class="text-gray-800 font-bold">{{ $issueSlip->issue_slip_number }}</span></p>
                <p class="text-gray-400"><span class="font-semibold text-gray-500">Date:</span> {{ $issueSlip->date->format('M d, Y') }}</p>
                <p class="text-gray-400"><span class="font-semibold text-gray-500">Origin:</span> {{ $issueSlip->origin ?? 'N/A' }}</p>
                <p class="text-gray-400"><span class="font-semibold text-gray-500">Branch:</span> {{ $issueSlip->branch ?? optional($issueSlip->salesOrder)->branch ?? optional(optional($issueSlip->salesOrder)->customer)->branch ?? 'N/A' }}</p>
            </div>
            <div class="space-y-2">
                <p class="text-gray-400"><span class="font-semibold text-gray-500">Sales Order:</span>
                    <a href="{{ route('sales_orders.show', $issueSlip->sales_order_id) }}" class="text-blue-400 hover:underline">{{ $issueSlip->sales_order_number }}</a>
                </p>
                <p class="text-gray-400"><span class="font-semibold text-gray-500">Destination:</span> {{ $issueSlip->destination ?? $issueSlip->customer_name ?? 'N/A' }}</p>
                <p class="text-gray-400"><span class="font-semibold text-gray-500">Created By:</span> {{ $issueSlip->creator->name ?? 'N/A' }}</p>
            </div>
        </div>

        @if($issueSlip->salesOrder && $issueSlip->salesOrder->additional_instructions)
        <div class="mb-6">
            <p class="text-gray-400"><span class="font-semibold text-gray-300">Delivery Instructions:</span> {{ $issueSlip->salesOrder->additional_instructions }}</p>
        </div>
        @endif

        @if($issueSlip->remarks)
        <div class="mb-6">
            <p class="text-gray-400"><span class="font-semibold text-gray-500">Issue Slip Remarks:</span> {{ $issueSlip->remarks }}</p>
        </div>
        @endif

        <!-- Items Table -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-200">
                    <thead class="bg-red-700 text-white">
                        <tr>
                            <th class="border border-gray-200 px-2 py-2 w-12">NO.</th>
                            <th class="border border-gray-200 px-2 py-2">ITEM CODE</th>
                            <th class="border border-gray-200 px-2 py-2" style="min-width:200px">DESCRIPTION</th>
                            <th class="border border-gray-200 px-2 py-2">BRAND</th>
                            <th class="border border-gray-200 px-2 py-2">CATEGORY</th>
                            <th class="border border-gray-200 px-2 py-2">SO QTY</th>
                            <th class="border border-gray-200 px-2 py-2">NUMBER OF BOXES</th>
                            <th class="border border-gray-200 px-2 py-2">NET WEIGHT</th>
                            <th class="border border-gray-200 px-2 py-2">ACTUAL WEIGHT</th>
                            <th class="border border-gray-200 px-2 py-2">ORIGIN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($issueSlip->items as $index => $item)
                        <tr class="hover:bg-gray-100">
                            <td class="border border-gray-200 px-2 py-2 text-center text-gray-500">{{ $index + 1 }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-gray-500">{{ $item->item_code }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-gray-500">{{ $item->item_description }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-gray-500">{{ $item->brand }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-gray-500">{{ $item->item_category }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-center text-gray-500">{{ $item->so_quantity }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-center text-gray-800 font-semibold">{{ $item->number_of_boxes }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-center text-gray-800 font-semibold">{{ number_format($item->net_weight, 4) }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-center text-gray-800 font-semibold">{{ number_format($item->actual_weight, 4) }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-center text-gray-800 font-semibold">{{ $item->origin ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-400"><span class="font-semibold text-gray-500">Issued By:</span> {{ $issueSlip->issued_by ?? 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-gray-400"><span class="font-semibold text-gray-500">Transport:</span> {{ $issueSlip->transport ?? 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-gray-400"><span class="font-semibold text-gray-500">Service Providers Checker:</span> {{ $issueSlip->service_providers_checker ?? 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-gray-400"><span class="font-semibold text-gray-500">Received By:</span> {{ $issueSlip->received_by ?? 'Not set' }}</p>
                </div>
            </div>
        </div>

        <!-- Delete -->
        @if(auth()->user()->canDeleteIssueSlips())
        <div class="flex justify-end">
            <form action="{{ route('issue_slips.destroy', $issueSlip->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this issue slip?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
