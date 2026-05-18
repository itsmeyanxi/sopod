@extends('layouts.app')
@section('title', 'Currency Rate History')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">Currency Rate History</h1>
            <a href="{{ route('currencies.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 text-sm">← Back to Rates</a>
        </div>

        <form method="GET" class="flex gap-3 mb-5 flex-wrap">
            <div>
                <label class="block text-gray-400 text-xs mb-1">Currency</label>
                <select name="code" class="bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm">
                    <option value="">All</option>
                    @foreach(['USD','EUR','GBP','AUD'] as $c)
                    <option value="{{ $c }}" {{ request('code')===$c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-400 text-xs mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm">
            </div>
            <div>
                <label class="block text-gray-400 text-xs mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 text-sm">Filter</button>
                <a href="{{ route('currencies.history') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 text-sm">Clear</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-600 px-4 py-2 text-left">Date & Time</th>
                        <th class="border border-gray-600 px-4 py-2 text-center">Currency</th>
                        <th class="border border-gray-600 px-4 py-2 text-right">Old Rate</th>
                        <th class="border border-gray-600 px-4 py-2 text-right">New Rate</th>
                        <th class="border border-gray-600 px-4 py-2 text-right">Change</th>
                        <th class="border border-gray-600 px-4 py-2 text-left">Updated By</th>
                    </tr>
                </thead>
                <tbody class="text-gray-200">
                    @forelse($logs as $log)
                    @php $diff = $log->new_rate - $log->old_rate; @endphp
                    <tr class="hover:bg-gray-700/30">
                        <td class="border border-gray-700 px-4 py-2 text-gray-300 text-xs">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-center font-bold text-purple-400">{{ $log->currency_code }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-right text-red-400">₱{{ number_format($log->old_rate,4) }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-right text-green-400">₱{{ number_format($log->new_rate,4) }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-right {{ $diff>0 ? 'text-green-400' : ($diff<0 ? 'text-red-400' : 'text-gray-400') }}">
                            {{ $diff>0 ? '+' : '' }}{{ number_format($diff,4) }}
                        </td>
                        <td class="border border-gray-700 px-4 py-2 text-gray-300">{{ $log->updater->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-gray-500 py-6">No history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
