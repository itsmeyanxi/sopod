@extends('layouts.app')

@section('title', 'Live Chicken Record')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">🐔 Live Chicken Record #{{ $record->id }}</h1>
            <div class="flex gap-2">
                @if(auth()->user()->canManageLiveChicken())
                    <a href="{{ route('live_chickens.edit', $record->id) }}"
                       class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-sm font-semibold">
                        Edit
                    </a>
                    <form action="{{ route('live_chickens.destroy', $record->id) }}" method="POST"
                          onsubmit="return confirm('Delete this record?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded text-sm font-semibold">
                            Delete
                        </button>
                    </form>
                @endif
                <a href="{{ route('live_chickens.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded text-sm">
                    ← Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        @php
            $colors = \App\Models\LiveChicken::STATUS_COLORS;
            $color  = $colors[$record->status] ?? 'gray';
        @endphp

        {{-- Status badge --}}
        <div class="mb-5">
            <span class="px-3 py-1 rounded text-sm font-semibold
                @if($color === 'green') bg-green-700 text-green-100
                @elseif($color === 'blue') bg-blue-700 text-blue-100
                @elseif($color === 'yellow') bg-yellow-700 text-yellow-100
                @else bg-red-700 text-red-100
                @endif">
                {{ $record->status }}
            </span>
        </div>

        {{-- Core Details --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Date</div>
                <div class="text-white font-semibold">{{ $record->date->format('F d, Y') }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">PO Number</div>
                <div class="text-purple-300 font-mono font-semibold">{{ $record->po_no ?? '—' }}</div>
                @if($record->po_no)
                    @php $poQty = $record->getPoQty(); @endphp
                    <div class="text-xs mt-1 {{ $record->isPoQtyMet() ? 'text-green-400' : 'text-red-400' }}">
                        PO Qty: {{ number_format($poQty, 2) }}
                        — {{ $record->isPoQtyMet() ? '✓ Met' : '⚠ Not Met (SRR blocked)' }}
                    </div>
                @endif
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Supplier</div>
                <div class="text-white">{{ $record->supplier }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Brand</div>
                <div class="text-white">{{ $record->brand ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4 md:col-span-2">
                <div class="text-xs text-gray-400 mb-1">Items</div>
                <div class="text-white">{{ $record->items }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Price</div>
                <div class="text-white">{{ number_format($record->price, 2) }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Actual Qty</div>
                <div class="text-white font-semibold">{{ number_format($record->actual_qty, 2) }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Amount</div>
                <div class="text-white font-semibold text-lg">{{ number_format($record->amount, 2) }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Delivery Date</div>
                <div class="text-white">{{ $record->delivery_date?->format('F d, Y') ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Delivery Week No.</div>
                <div class="text-white">{{ $record->delivery_week_no ?? '—' }}</div>
            </div>
        </div>

        {{-- Docs --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-900 rounded p-4 border border-gray-700">
                <div class="text-sm font-semibold text-gray-300 mb-2">Docs Required</div>
                @if($record->docs_required_type === 'file' && $record->docs_required_file)
                    <a href="{{ Storage::url($record->docs_required_file) }}" target="_blank"
                       class="text-blue-400 hover:underline text-sm">📎 View Attachment</a>
                @elseif($record->docs_required_type === 'date' && $record->docs_required_date)
                    <span class="text-white text-sm">📅 {{ $record->docs_required_date->format('F d, Y') }}</span>
                @else
                    <span class="text-gray-500 text-sm">—</span>
                @endif
            </div>
            <div class="bg-gray-900 rounded p-4 border border-gray-700">
                <div class="text-sm font-semibold text-gray-300 mb-2">Docs Transmitted</div>
                @if($record->docs_transmitted_type === 'file' && $record->docs_transmitted_file)
                    <a href="{{ Storage::url($record->docs_transmitted_file) }}" target="_blank"
                       class="text-blue-400 hover:underline text-sm">📎 View Attachment</a>
                @elseif($record->docs_transmitted_type === 'date' && $record->docs_transmitted_date)
                    <span class="text-white text-sm">📅 {{ $record->docs_transmitted_date->format('F d, Y') }}</span>
                @else
                    <span class="text-gray-500 text-sm">—</span>
                @endif
            </div>
        </div>

        {{-- PO Items (if linked) --}}
        @if($record->purchaseOrder && $record->purchaseOrder->items->isNotEmpty())
            <div class="mt-4">
                <div class="text-sm font-semibold text-gray-300 mb-2">Linked PO Items</div>
                <table class="w-full text-sm bg-gray-900 rounded overflow-hidden">
                    <thead class="bg-gray-700 text-gray-300 text-xs uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left">Item Code</th>
                            <th class="px-3 py-2 text-left">Description</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($record->purchaseOrder->items as $item)
                            <tr>
                                <td class="px-3 py-2 font-mono text-purple-300">{{ $item->item_code }}</td>
                                <td class="px-3 py-2 text-gray-200">{{ $item->description }}</td>
                                <td class="px-3 py-2 text-right text-white">{{ number_format($item->qty, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-800">
                            <td colspan="2" class="px-3 py-2 text-right text-gray-400 font-semibold">Total PO Qty:</td>
                            <td class="px-3 py-2 text-right text-white font-bold">{{ number_format($record->purchaseOrder->items->sum('qty'), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <div class="mt-5 text-xs text-gray-500">
            Created by {{ $record->creator?->name ?? 'N/A' }} on {{ $record->created_at->format('F d, Y h:i A') }}
        </div>

    </div>
</div>
@endsection
