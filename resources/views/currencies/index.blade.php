@extends('layouts.app')

@section('title', 'Currency Exchange Rates')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">Currency Exchange Rates</h1>
            <p class="text-gray-500 text-sm">Rates are used to convert foreign currencies to Philippine Peso (PHP) in Purchase Orders.</p>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($currencies as $currency)
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-bold text-purple-700">{{ $currency->symbol }}</span>
                        <div>
                            <div class="font-bold text-gray-800 text-lg">{{ $currency->code }}</div>
                            <div class="text-gray-500 text-sm">{{ $currency->name }}</div>
                        </div>
                    </div>
                    @if($currency->code === 'PHP')
                        <span class="px-2 py-1 bg-green-800 text-green-700 rounded text-xs font-semibold">BASE</span>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="text-gray-500 text-xs mb-1">Current Rate (1 {{ $currency->code }} = ? PHP)</div>
                    <div class="text-gray-800 text-xl font-bold">
                        ₱{{ number_format($currency->rate_to_php, 4) }}
                    </div>
                    @if($currency->updater)
                        <div class="text-gray-500 text-xs mt-1">
                            Updated by {{ $currency->updater->name }} on {{ $currency->updated_at->format('M d, Y h:i A') }}
                        </div>
                    @else
                        <div class="text-gray-500 text-xs mt-1">
                            Last updated: {{ $currency->updated_at->format('M d, Y') }}
                        </div>
                    @endif
                </div>

                @if($currency->code !== 'PHP')
                <form action="{{ route('currencies.update', $currency->id) }}" method="POST" class="flex gap-2 items-end">
                    @csrf
                    @method('PUT')
                    <div class="flex-1">
                        <label class="block text-gray-500 text-xs mb-1">New Rate (₱ per 1 {{ $currency->code }})</label>
                        <input type="number"
                               name="rate_to_php"
                               step="0.0001"
                               min="0.0001"
                               value="{{ number_format($currency->rate_to_php, 4, '.', '') }}"
                               class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                               required>
                    </div>
                    <button type="submit"
                            class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition text-sm whitespace-nowrap">
                        Update
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Quick Reference Table -->
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Quick Reference</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead class="bg-gray-100 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="border border-gray-300 px-4 py-2 text-left">Currency</th>
                            <th class="border border-gray-300 px-4 py-2 text-right">1 Unit = PHP</th>
                            <th class="border border-gray-300 px-4 py-2 text-right">100 Units = PHP</th>
                            <th class="border border-gray-300 px-4 py-2 text-right">1,000 Units = PHP</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-500">
                        @foreach($currencies as $currency)
                        <tr class="hover:bg-gray-100/30">
                            <td class="border border-gray-200 px-4 py-2">
                                <span class="font-bold text-purple-700">{{ $currency->code }}</span>
                                <span class="text-gray-500 ml-2">{{ $currency->name }}</span>
                            </td>
                            <td class="border border-gray-200 px-4 py-2 text-right">₱{{ number_format($currency->rate_to_php, 4) }}</td>
                            <td class="border border-gray-200 px-4 py-2 text-right">₱{{ number_format($currency->rate_to_php * 100, 2) }}</td>
                            <td class="border border-gray-200 px-4 py-2 text-right">₱{{ number_format($currency->rate_to_php * 1000, 2) }}</td>
                            <td class="border border-gray-200 px-4 py-2 text-gray-500 text-xs">{{ $currency->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
