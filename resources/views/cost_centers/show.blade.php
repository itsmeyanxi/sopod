@extends('layouts.app')
@section('title', 'Cost Center — ' . $costCenter->cost_center_code)
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">

        {{-- ── Header ──────────────────────────────────────────────────── --}}
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $costCenter->cost_center_code }}</h1>
                <p class="text-gray-400 text-sm mt-1">{{ $costCenter->cost_center_name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('cost_centers.edit', $costCenter->id) }}"
                   class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <a href="{{ route('cost_centers.index') }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        {{-- ── Info Cards ───────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            {{-- Left: Core details --}}
            <div class="bg-gray-900 border border-gray-700 rounded p-4 space-y-3">
                <p class="text-xs font-semibold text-purple-400 uppercase tracking-wide mb-2">Cost Center Details</p>

                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm font-semibold">CODE</span>
                    <span class="font-mono font-bold text-purple-400">{{ $costCenter->cost_center_code }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm font-semibold">NAME</span>
                    <span class="text-white">{{ $costCenter->cost_center_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm font-semibold">DIMENSION</span>
                    <span class="text-gray-200">{{ $costCenter->dimension ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm font-semibold">OWNER</span>
                    <span class="text-gray-200">{{ $costCenter->cost_center_owner ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm font-semibold">CREATED BY</span>
                    <span class="text-gray-200">{{ $costCenter->creator->name ?? '—' }}</span>
                </div>
            </div>

            {{-- Right: Classification --}}
            <div class="bg-gray-900 border border-gray-700 rounded p-4 space-y-3">
                <p class="text-xs font-semibold text-purple-400 uppercase tracking-wide mb-2">Classification</p>

                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm font-semibold">DIVISION</span>
                    <span class="text-gray-200">{{ $costCenter->division ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm font-semibold">DEPARTMENT</span>
                    <span class="text-gray-200">{{ $costCenter->department ?? '—' }}</span>
                </div>

                {{-- Divider --}}
                <div class="border-t border-gray-700 pt-3 mt-1 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400 text-sm font-semibold">GENERAL CC</span>
                        <span class="text-gray-200">{{ $costCenter->general_cc ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 text-sm font-semibold">CC</span>
                        <span class="text-gray-200">{{ $costCenter->cc ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── GL Accounts ──────────────────────────────────────────────── --}}
        <!-- <div class="bg-gray-900 border border-gray-700 rounded p-4 mb-6">
            <h3 class="font-semibold text-gray-300 mb-3 text-sm uppercase tracking-wide">GL Accounts</h3>

            @php $gls = is_array($costCenter->gl_accounts) ? $costCenter->gl_accounts : []; @endphp

            @if(count($gls))
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 uppercase border-b border-gray-700">
                                <th class="pb-2 pr-4 font-semibold">Account Code</th>
                                <th class="pb-2 font-semibold">Account Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gls as $gl)
                            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                                <td class="py-2 pr-4">
                                    <span class="font-mono text-purple-400 font-semibold">
                                        {{ $gl['account_code'] ?? $gl['code'] ?? '—' }}
                                    </span>
                                </td>
                                <td class="py-2 text-gray-200">
                                    {{ $gl['account_name'] ?? $gl['name'] ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-400 text-sm">No GL accounts linked.</p>
            @endif
        </div> -->

        <!-- {{-- ── Lookup & OPEX ────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-gray-300 mb-2 text-sm uppercase tracking-wide">Lookup</h3>
                <p class="text-gray-200 text-sm whitespace-pre-line">{{ $costCenter->lookup ?? '—' }}</p>
            </div>
            <div class="bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-gray-300 mb-2 text-sm uppercase tracking-wide">OPEX Mapping</h3>
                <p class="text-gray-200 text-sm whitespace-pre-line">{{ $costCenter->opex_mapping ?? '—' }}</p>
            </div>
        </div> -->

    </div>
</div>
@endsection