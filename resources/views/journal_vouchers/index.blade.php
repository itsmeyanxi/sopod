@extends('layouts.app')
@section('title', 'Journal Vouchers')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">JOURNAL VOUCHERS</h1>
            <a href="{{ route('journal_vouchers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                <i class="fas fa-plus mr-1"></i> New Journal Voucher
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-3 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                <p class="text-xs text-blue-600 font-semibold">Total</p>
                <p class="text-xl font-bold text-blue-800">{{ number_format($summary['total']) }}</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
                <p class="text-xs text-yellow-600 font-semibold">Draft</p>
                <p class="text-xl font-bold text-yellow-800">{{ number_format($summary['draft']) }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                <p class="text-xs text-green-600 font-semibold">Posted</p>
                <p class="text-xl font-bold text-green-800">{{ number_format($summary['posted']) }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                <p class="text-xs text-red-600 font-semibold">Void</p>
                <p class="text-xl font-bold text-red-800">{{ number_format($summary['void']) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('journal_vouchers.index') }}" class="bg-gray-900 border border-gray-700 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search JV#, description..." class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <select name="type" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="bank_interest" {{ request('type') == 'bank_interest' ? 'selected' : '' }}>Bank Interest</option>
                        <option value="bank_charges" {{ request('type') == 'bank_charges' ? 'selected' : '' }}>Bank Charges</option>
                        <option value="reclassification" {{ request('type') == 'reclassification' ? 'selected' : '' }}>Reclassification</option>
                        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        <option value="correction" {{ request('type') == 'correction' ? 'selected' : '' }}>Correction</option>
                        <option value="accrual" {{ request('type') == 'accrual' ? 'selected' : '' }}>Accrual</option>
                        <option value="reversal" {{ request('type') == 'reversal' ? 'selected' : '' }}>Reversal</option>
                        <option value="depreciation" {{ request('type') == 'depreciation' ? 'selected' : '' }}>Depreciation</option>
                        <option value="tax_adjustment" {{ request('type') == 'tax_adjustment' ? 'selected' : '' }}>Tax Adjustment</option>
                        <option value="intercompany" {{ request('type') == 'intercompany' ? 'selected' : '' }}>Intercompany</option>
                        <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <select name="status" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                        <option value="Void" {{ request('status') == 'Void' ? 'selected' : '' }}>Void</option>
                    </select>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm" placeholder="From">
                </div>
                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm" placeholder="To">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex-1"><i class="fas fa-filter mr-1"></i> Filter</button>
                    <a href="{{ route('journal_vouchers.index') }}" class="bg-gray-200 text-gray-200 px-3 py-2 rounded text-sm hover:bg-gray-300"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-3 py-3 text-left">JV Number</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Date</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Type</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Description</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Reference</th>
                        <th class="border border-gray-700 px-3 py-3 text-right">Debit</th>
                        <th class="border border-gray-700 px-3 py-3 text-right">Credit</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Lines</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Status</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $jv)
                    <tr class="hover:bg-gray-900 border-b border-gray-100">
                        <td class="border border-gray-700 px-3 py-2">
                            <a href="{{ route('journal_vouchers.show', $jv->id) }}" class="text-blue-700 hover:underline font-mono font-bold text-xs">{{ $jv->jv_number }}</a>
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-center text-xs">{{ $jv->jv_date->format('M d, Y') }}</td>
                        <td class="border border-gray-700 px-3 py-2">
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-xs font-semibold">{{ $jv->formatted_type }}</span>
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-200 max-w-xs truncate" title="{{ $jv->description }}">{{ Str::limit($jv->description, 40) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-500 text-xs">{{ $jv->reference_no ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right font-semibold">{{ number_format($jv->total_debit, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right font-semibold">{{ number_format($jv->total_credit, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-center text-xs">{{ $jv->lines_count }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @if($jv->status === 'Posted')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Posted</span>
                            @elseif($jv->status === 'Void')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Void</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-semibold">Draft</span>
                            @endif
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('journal_vouchers.show', $jv->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs" title="View"><i class="fas fa-eye"></i></a>
                                @if($jv->status === 'Draft')
                                    <a href="{{ route('journal_vouchers.edit', $jv->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                @endif
                                <a href="{{ route('journal_vouchers.print', $jv->id) }}" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded text-xs" title="Print"><i class="fas fa-print"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-file-alt text-4xl mb-3 block"></i>
                            No journal vouchers found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $vouchers->appends(request()->query())->links('vendor.pagination.elegant') }}
        </div>
    </div>
</div>
@endsection
