@extends('layouts.app')

@section('title', 'Currency Exchange Rates')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">Currency Exchange Rates</h1>
            <a href="{{ route('currencies.history') }}" class="bg-purple-700 text-white px-4 py-2 rounded hover:bg-purple-600 text-sm">View Full History</a>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        @php $currentHour = (int) now()->format('H'); $isLocked = $currentHour >= 17; @endphp
        @if($isLocked)
        <div class="bg-yellow-700 text-yellow-100 px-4 py-3 rounded mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <span>Currency updates are locked after <strong>5:00 PM</strong>. Updates will be available again tomorrow morning.</span>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($currencies as $currency)
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-bold text-purple-400">{{ $currency->symbol }}</span>
                        <div>
                            <div class="font-bold text-white text-lg">{{ $currency->code }}</div>
                            <div class="text-gray-300 text-sm">{{ $currency->name }}</div>
                        </div>
                    </div>
                    @if($currency->code === 'PHP')
                        <span class="px-2 py-1 bg-green-900 text-green-400 rounded text-xs font-semibold">BASE</span>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="text-gray-400 text-xs mb-1">Current Rate (1 {{ $currency->code }} = ? PHP)</div>
                    <div class="text-white text-xl font-bold">
                        ₱{{ number_format($currency->rate_to_php, 4) }}
                    </div>
                    @if($currency->updater)
                        <div class="text-gray-400 text-xs mt-1">
                            Updated by {{ $currency->updater->name }} on {{ $currency->updated_at->format('M d, Y h:i A') }}
                        </div>
                    @else
                        <div class="text-gray-400 text-xs mt-1">
                            Last updated: {{ $currency->updated_at->format('M d, Y') }}
                        </div>
                    @endif
                </div>

                @if($currency->code !== 'PHP')
                <form action="{{ route('currencies.update', $currency->id) }}" method="POST" class="flex gap-2 items-end"
                      @if($isLocked) onsubmit="return false;" @endif>
                    @csrf
                    @method('PUT')
                    <div class="flex-1">
                        <label class="block text-gray-400 text-xs mb-1">New Rate (₱ per 1 {{ $currency->code }})</label>
                        <input type="number"
                               name="rate_to_php"
                               step="0.0001"
                               min="0.0001"
                               value="{{ number_format($currency->rate_to_php, 4, '.', '') }}"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm {{ $isLocked ? 'opacity-50 cursor-not-allowed' : '' }}"
                               {{ $isLocked ? 'disabled' : '' }}
                               required>
                    </div>
                    <button type="submit"
                            class="px-4 py-2 rounded text-sm whitespace-nowrap {{ $isLocked ? 'bg-gray-600 text-gray-400 cursor-not-allowed' : 'bg-purple-600 text-white hover:bg-purple-700 transition' }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        {{ $isLocked ? 'Locked' : 'Update' }}
                    </button>
                </form>
                @endif

                {{-- Update History for this currency --}}
                @if(isset($logs[$currency->code]) && $logs[$currency->code]->count() > 0)
                <div class="mt-4 border-t border-gray-700 pt-3">
                    <div class="text-gray-400 text-xs font-semibold mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        Update History
                    </div>
                    <div class="space-y-1 max-h-36 overflow-y-auto pr-1">
                        @foreach($logs[$currency->code]->take(10) as $log)
                        <div class="text-xs bg-gray-800 rounded px-2 py-1">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">{{ $log->created_at->format('M d, Y h:i A') }}</span>
                                <span class="text-gray-400">{{ $log->updater->name ?? 'System' }}</span>
                            </div>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="text-red-400">₱{{ number_format($log->old_rate, 4) }}</span>
                                <span class="text-gray-500">→</span>
                                <span class="text-green-400">₱{{ number_format($log->new_rate, 4) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Quick Reference Table -->
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-white mb-3">Quick Reference</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="border border-gray-600 px-4 py-2 text-left">Currency</th>
                            <th class="border border-gray-600 px-4 py-2 text-right">1 Unit = PHP</th>
                            <th class="border border-gray-600 px-4 py-2 text-right">100 Units = PHP</th>
                            <th class="border border-gray-600 px-4 py-2 text-right">1,000 Units = PHP</th>
                            <th class="border border-gray-600 px-4 py-2 text-left">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-200">
                        @foreach($currencies as $currency)
                        <tr class="hover:bg-gray-700/30">
                            <td class="border border-gray-700 px-4 py-2">
                                <span class="font-bold text-purple-400">{{ $currency->code }}</span>
                                <span class="text-gray-300 ml-2">{{ $currency->name }}</span>
                            </td>
                            <td class="border border-gray-700 px-4 py-2 text-right">₱{{ number_format($currency->rate_to_php, 4) }}</td>
                            <td class="border border-gray-700 px-4 py-2 text-right">₱{{ number_format($currency->rate_to_php * 100, 2) }}</td>
                            <td class="border border-gray-700 px-4 py-2 text-right">₱{{ number_format($currency->rate_to_php * 1000, 2) }}</td>
                            <td class="border border-gray-700 px-4 py-2 text-gray-400 text-xs">{{ $currency->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
