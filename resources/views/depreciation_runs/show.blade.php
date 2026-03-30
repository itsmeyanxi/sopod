@extends('layouts.app')
@section('title', 'Depreciation Run — ' . $run->run_number)

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">DEPRECIATION RUN</h1>
                <p class="text-sm text-gray-500 mt-1 font-mono font-bold">{{ $run->run_number }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($run->status === 'Posted')
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm font-semibold">Posted</span>
                @elseif($run->status === 'Void')
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm font-semibold">Void</span>
                @else
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-sm font-semibold">Draft</span>
                @endif
                <a href="{{ route('depreciation_runs.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Run Details -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Period</label>
                <p class="px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm font-semibold">{{ $run->period_label }} ({{ $run->period }})</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Prepared By</label>
                <p class="px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm">{{ $run->prepared_by ?? '—' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Created At</label>
                <p class="px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm">{{ $run->created_at->format('M d, Y h:i A') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Posted At</label>
                <p class="px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm">{{ $run->posted_at ? $run->posted_at->format('M d, Y h:i A') : '—' }}</p>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Description</label>
            <p class="px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm">{{ $run->description ?? '—' }}</p>
        </div>

        @if($run->jv_number)
        <div class="bg-green-50 border border-green-200 rounded p-3 mb-6 text-sm text-green-700">
            <i class="fas fa-check-circle mr-1"></i>
            Journal Voucher <strong class="font-mono">{{ $run->jv_number }}</strong> was created upon posting.
            <a href="{{ route('journal_vouchers.index') }}?search={{ $run->jv_number }}" class="underline ml-1">View JV</a>
        </div>
        @endif

        <!-- Asset Groups -->
        @if($run->asset_groups)
        <div class="mb-6">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Asset Groups Included</label>
            <div class="flex flex-wrap gap-2">
                @foreach($run->asset_groups as $group)
                    <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-xs font-semibold">{{ $group }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Totals -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded p-4 text-center">
                <p class="text-xs text-blue-600 font-semibold mb-1">Total Depreciation Amount</p>
                <p class="text-2xl font-bold text-blue-800">{{ number_format($run->total_debit, 2) }}</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded p-4 text-center">
                <p class="text-xs text-gray-500 font-semibold mb-1">Number of Assets</p>
                <p class="text-2xl font-bold text-gray-700">{{ count($run->depreciation_entries ?? []) }}</p>
            </div>
        </div>

        <!-- Depreciation Entries Table -->
        <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Depreciation Entries</h2>
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-200 px-3 py-2 text-left">#</th>
                        <th class="border border-gray-200 px-3 py-2 text-left">Asset Code</th>
                        <th class="border border-gray-200 px-3 py-2 text-left">Description</th>
                        <th class="border border-gray-200 px-3 py-2 text-left">Group</th>
                        <th class="border border-gray-200 px-3 py-2 text-left">Dep. Account</th>
                        <th class="border border-gray-200 px-3 py-2 text-right">Accum. Before</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Rem. Months</th>
                        <th class="border border-gray-200 px-3 py-2 text-right">Monthly Dep.</th>
                    </tr>
                </thead>
                <tbody>
                    @php $entries = $run->depreciation_entries ?? []; @endphp
                    @forelse($entries as $i => $entry)
                    <tr class="hover:bg-gray-50 border-b border-gray-100">
                        <td class="border border-gray-200 px-3 py-2 text-gray-400 text-xs">{{ $i + 1 }}</td>
                        <td class="border border-gray-200 px-3 py-2 font-mono text-xs font-semibold text-blue-700">{{ $entry['asset_code'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-gray-700">{{ $entry['asset_description'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-xs">
                            <span class="bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">{{ $entry['asset_group'] ?? '—' }}</span>
                        </td>
                        <td class="border border-gray-200 px-3 py-2 font-mono text-xs text-gray-500">{{ $entry['depreciation_account'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-right text-xs">{{ number_format($entry['accumulated_before'] ?? 0, 2) }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-center text-xs">{{ $entry['remaining_before'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-right font-semibold text-blue-700">{{ number_format($entry['monthly_depreciation'] ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">No entries.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(!empty($entries))
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="7" class="border border-gray-200 px-3 py-2 text-right text-sm">Total Depreciation:</td>
                        <td class="border border-gray-200 px-3 py-2 text-right text-blue-700">{{ number_format($run->total_debit, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 border-t border-gray-200 pt-4">
            @if($run->status === 'Draft')
                <!-- Post -->
                <form method="POST" action="{{ route('depreciation_runs.post', $run->id) }}"
                      onsubmit="return confirm('Post this depreciation run? This will update all asset records and create a Journal Voucher. This cannot be undone.')">
                    @csrf
                    <input type="hidden" name="approved_by" value="{{ auth()->user()->name }}">
                    <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 text-sm">
                        <i class="fas fa-check-circle mr-1"></i> Post & Create JV
                    </button>
                </form>
                <!-- Void -->
                <form method="POST" action="{{ route('depreciation_runs.void', $run->id) }}"
                      onsubmit="return confirm('Void this depreciation run?')">
                    @csrf
                    <button type="submit" class="bg-orange-500 text-white px-5 py-2 rounded hover:bg-orange-600 text-sm">
                        <i class="fas fa-ban mr-1"></i> Void
                    </button>
                </form>
                <!-- Delete -->
                <form method="POST" action="{{ route('depreciation_runs.destroy', $run->id) }}"
                      onsubmit="return confirm('Delete this draft run permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="bg-red-600 text-white px-5 py-2 rounded hover:bg-red-700 text-sm">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
