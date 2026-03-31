@extends('layouts.app')
@section('title', 'JV — ' . $voucher->jv_number)

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">JOURNAL VOUCHER</h1>
            <div class="flex items-center gap-3">
                @if($voucher->status === 'Posted')
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm font-semibold">Posted</span>
                @elseif($voucher->status === 'Void')
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm font-semibold">Void</span>
                @else
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-sm font-semibold">Draft</span>
                @endif
                <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-sm">{{ $voucher->formatted_type }}</span>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">JV Number:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded font-mono font-bold text-blue-700">{{ $voucher->jv_number }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">JV Date:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $voucher->jv_date->format('F d, Y') }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Reference No.:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $voucher->reference_no ?? 'N/A' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Description:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded min-h-[60px]">{{ $voucher->description }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Remarks:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded min-h-[60px]">{{ $voucher->remarks ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Journal Lines -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Journal Entry Lines</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="border border-gray-700 px-3 py-2 text-center w-10">#</th>
                            <th class="border border-gray-700 px-3 py-2 text-left">Account Code</th>
                            <th class="border border-gray-700 px-3 py-2 text-left">Account Name</th>
                            <th class="border border-gray-700 px-3 py-2 text-left">Description</th>
                            <th class="border border-gray-700 px-3 py-2 text-left">Cost Center</th>
                            <th class="border border-gray-700 px-3 py-2 text-right">Debit</th>
                            <th class="border border-gray-700 px-3 py-2 text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->lines as $i => $line)
                        <tr class="hover:bg-gray-900">
                            <td class="border border-gray-700 px-3 py-2 text-center text-gray-400">{{ $i + 1 }}</td>
                            <td class="border border-gray-700 px-3 py-2 font-mono text-blue-700 font-semibold">{{ $line->account_code }}</td>
                            <td class="border border-gray-700 px-3 py-2">{{ $line->account_name ?? '—' }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-gray-300">{{ $line->line_description ?? '—' }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-gray-300 text-xs">{{ $line->cost_center ?? '—' }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right font-semibold {{ $line->debit > 0 ? 'text-white' : 'text-gray-300' }}">
                                {{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}
                            </td>
                            <td class="border border-gray-700 px-3 py-2 text-right font-semibold {{ $line->credit > 0 ? 'text-white' : 'text-gray-300' }}">
                                {{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-800 text-white font-bold">
                            <td colspan="5" class="border border-gray-700 px-3 py-2 text-right">TOTALS:</td>
                            <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($voucher->total_debit, 2) }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($voucher->total_credit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if(!$voucher->is_balanced)
                <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                    <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Warning:</strong> Debit and Credit totals do not match. Difference: {{ number_format(abs($voucher->total_debit - $voucher->total_credit), 2) }}
                </div>
            @endif
        </div>

        <!-- Signatories -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Prepared By:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $voucher->prepared_by ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Checked By:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $voucher->checked_by ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">Approved By:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $voucher->approved_by ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Attachment -->
        @if($voucher->attachment_path)
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-1">Attachment:</label>
            <a href="{{ asset('storage/' . $voucher->attachment_path) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 border border-gray-700 rounded text-purple-700 hover:bg-purple-50 transition">
                <i class="fas fa-paperclip"></i> {{ $voucher->attachment_name }}
            </a>
        </div>
        @endif

        <!-- Audit -->
        <div class="bg-gray-700 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-300">
                <div>
                    <label class="font-semibold">Created By:</label>
                    <p>{{ $voucher->created_by ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="font-semibold">Created:</label>
                    <p>{{ $voucher->created_at->format('M d, Y h:i A') }}</p>
                </div>
                @if($voucher->posted_at)
                <div>
                    <label class="font-semibold">Posted:</label>
                    <p>{{ $voucher->posted_at->format('M d, Y h:i A') }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 flex-wrap">
            <a href="{{ route('journal_vouchers.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="{{ route('journal_vouchers.print', $voucher->id) }}" target="_blank" class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-print mr-1"></i> Print
            </a>

            @if($voucher->status === 'Draft')
                <a href="{{ route('journal_vouchers.edit', $voucher->id) }}" class="bg-yellow-500 text-white px-6 py-2 rounded hover:bg-yellow-600 transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                @if($voucher->is_balanced)
                    <form action="{{ route('journal_vouchers.post', $voucher->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Post this Journal Voucher? This cannot be undone.')">
                        @csrf
                        <button class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                            <i class="fas fa-check mr-1"></i> Post
                        </button>
                    </form>
                @endif
                <form action="{{ route('journal_vouchers.destroy', $voucher->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this JV?')">
                    @csrf @method('DELETE')
                    <button class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                </form>
            @elseif($voucher->status === 'Posted')
                <form action="{{ route('journal_vouchers.void', $voucher->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Void this Journal Voucher?')">
                    @csrf
                    <button class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-ban mr-1"></i> Void
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
