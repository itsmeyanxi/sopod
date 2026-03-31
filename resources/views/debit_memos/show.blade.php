@extends('layouts.app')

@section('title', 'Debit Memo - ' . $debit_memo->dm_no)

@section('content')
<div class="container mx-auto max-w-3xl">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">{{ $debit_memo->dm_no }}</h1>
            <div class="flex gap-2">
                <a href="{{ route('debit_memos.edit', $debit_memo) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <a href="{{ route('debit_memos.index') }}" class="text-gray-300 hover:text-gray-200 text-sm px-4 py-2">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-300 uppercase mb-3">Memo Details</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-gray-300 text-xs">DM Number</span>
                        <p class="text-white font-semibold">{{ $debit_memo->dm_no }}</p>
                    </div>
                    <div>
                        <span class="text-gray-300 text-xs">Date</span>
                        <p class="text-white">{{ $debit_memo->memo_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-300 text-xs">Amount</span>
                        <p class="text-white text-xl font-bold">{{ number_format($debit_memo->amount, 2) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-300 text-xs">Status</span>
                        <p>
                            @if($debit_memo->status === 'Open')
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm font-semibold">Open</span>
                            @elseif($debit_memo->status === 'Applied')
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm font-semibold">Applied</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm font-semibold">Voided</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-300 uppercase mb-3">References</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-gray-300 text-xs">Supplier</span>
                        <p class="text-white font-semibold">{{ $debit_memo->supplier_name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-300 text-xs">Invoice (APV)</span>
                        <p class="text-white">{{ $debit_memo->invoice_no ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-300 text-xs">Reason</span>
                        <p class="text-white">{{ $debit_memo->reason ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-300 text-xs">Created By</span>
                        <p class="text-white">{{ $debit_memo->creator->name ?? 'System' }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if($debit_memo->notes)
        <div class="mt-6 pt-4 border-t border-gray-700">
            <h3 class="text-sm font-semibold text-gray-300 uppercase mb-2">Notes</h3>
            <p class="text-gray-200 text-sm">{{ $debit_memo->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
