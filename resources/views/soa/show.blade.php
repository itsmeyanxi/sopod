@extends('layouts.app')

@section('title', 'SOA — ' . $customerName)

@section('content')
<div class="p-6 bg-gray-900 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <div class="mb-4 flex justify-between items-center">
            <a href="{{ route('soa.index') }}" class="text-sm text-gray-500 hover:text-gray-200">
                <i class="fas fa-arrow-left mr-1"></i> Back to SOA List
            </a>
            <a href="{{ route('soa.export', $customerCode) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-medium text-sm transition">
                <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
        </div>

        <!-- SOA Card -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden" id="soa_card">
            <!-- Header -->
            <div class="px-8 pt-6 pb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-red-800 tracking-wide">MEATPLUS</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Trading Corporation</p>
                    </div>
                    <div class="text-right">
                        <h2 class="text-2xl font-bold text-red-800 italic">Statement of Account</h2>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-6">
                    <div>
                        <p class="text-xs text-gray-500 font-semibold mb-1">Bill to</p>
                        <p class="text-lg font-bold text-white">{{ $customerName }}</p>
                        <div class="mt-2 space-y-0.5">
                            <p class="text-xs text-gray-500"><strong>Receivable as of {{ $today->format('M d, Y') }}</strong></p>
                            <p class="text-xl font-bold text-red-800">₱{{ number_format($totalBalance, 2) }}</p>
                        </div>
                        @if($creditBalance > 0 && auth()->user()->isAdminUser())
                        <div class="mt-2 bg-green-50 border border-green-200 rounded px-3 py-2 inline-block">
                            <p class="text-xs text-green-700 font-semibold">Credit Balance / Overpayment</p>
                            <p class="text-lg font-bold text-green-700">₱{{ number_format($creditBalance, 2) }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="text-right space-y-1">
                        <div class="flex justify-end gap-4">
                            <span class="text-xs text-gray-500 font-semibold">Statement Date</span>
                            <span class="text-sm text-white">{{ $today->format('F d, Y') }}</span>
                        </div>
                        <div class="flex justify-end gap-4">
                            <span class="text-xs text-gray-500 font-semibold">Statement Period</span>
                            <span class="text-sm text-white">{{ $today->format('F Y') }}</span>
                        </div>
                        @if($branch)
                        <div class="flex justify-end gap-4">
                            <span class="text-xs text-gray-500 font-semibold">Branch</span>
                            <span class="text-sm text-white">{{ $branch }}</span>
                        </div>
                        @endif
                        @if($terms)
                        <div class="flex justify-end gap-4">
                            <span class="text-xs text-gray-500 font-semibold">Terms</span>
                            <span class="text-sm text-white">{{ $terms }} days</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="px-8 pb-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-red-800 text-white">
                                <th class="px-3 py-2.5 text-center text-xs font-semibold">Invoice Date</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold">Due Date</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold">SOA Date</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold">DR No.</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold">SI No.</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold">Number of Days Outstanding</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold">Current</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold">Past Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detailRows as $idx => $row)
                            <tr class="{{ $idx % 2 === 1 ? 'bg-red-50' : 'bg-gray-800' }} border-b border-gray-100">
                                <td class="px-3 py-2 text-center text-gray-200">{{ $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('n/j/Y') : '—' }}</td>
                                <td class="px-3 py-2 text-center text-gray-200">{{ $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('n/j/Y') : '—' }}</td>
                                <td class="px-3 py-2 text-center text-gray-200">{{ $today->format('n/j/Y') }}</td>
                                <td class="px-3 py-2 text-center text-white font-medium">{{ $row->dr_no ?? '—' }}</td>
                                <td class="px-3 py-2 text-center text-white font-medium">{{ $row->invoice_no ?? '—' }}</td>
                                <td class="px-3 py-2 text-center {{ $row->days_outstanding > 0 ? 'text-red-600 font-semibold' : 'text-green-700' }}">{{ $row->days_outstanding }}</td>
                                <td class="px-3 py-2 text-right text-white">{{ $row->current > 0 ? number_format($row->current, 2) : '' }}</td>
                                <td class="px-3 py-2 text-right text-red-700 font-semibold">{{ $row->past_due > 0 ? number_format($row->past_due, 2) : '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-900">
                                <td colspan="8" class="px-3 py-2 text-center text-gray-400 italic text-xs">—Nothing Follows—</td>
                            </tr>
                            <tr class="bg-red-800 text-white font-bold">
                                <td colspan="6" class="px-3 py-2.5 text-right text-sm">Current Balance:</td>
                                <td class="px-3 py-2.5 text-right text-sm">{{ number_format($totalCurrent, 2) }}</td>
                                <td class="px-3 py-2.5 text-right text-sm">{{ number_format($totalPastDue, 2) }}</td>
                            </tr>
                            @if($creditBalance > 0 && auth()->user()->isAdminUser())
                            <tr class="bg-green-50 border-t-2 border-green-300">
                                <td colspan="6" class="px-3 py-2.5 text-right text-sm font-bold text-green-800">
                                    <i class="fas fa-wallet mr-1"></i> Credit Balance / Overpayment:
                                </td>
                                <td colspan="2" class="px-3 py-2.5 text-right text-sm font-bold text-green-700">
                                    ₱{{ number_format($creditBalance, 2) }}
                                </td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-6 border-t border-gray-100">
                <div class="text-center space-y-0.5 mb-6">
                    <p class="text-xs text-gray-500">For any comments and questions about this statement of account, please contact</p>
                    <p class="text-xs font-bold text-gray-200">Meatplus Trading Corporation</p>
                    <p class="text-xs text-gray-500">Telephone: 244-4618 / 244-4619</p>
                    <p class="text-xs text-gray-500">Email: treasury@meatplus.ph</p>
                    <p class="text-xs text-gray-500">Suite 1207 Victoria Building, 429 U.N. Avenue, Ermita, Manila</p>
                </div>
                <p class="text-xs text-gray-400 italic text-center mb-6">
                    If we do not hear from you within three (3) days from receipt of this statement, we will assume that you are in agreement with the outstanding balance.
                </p>
                <div class="grid grid-cols-2 gap-8 mt-4">
                    <div>
                        <p class="text-xs font-bold text-gray-300 mb-4">Prepared by:</p>
                        <p class="text-sm text-white italic border-t border-gray-600 pt-1">{{ auth()->user()->name ?? 'Treasury' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-300 mb-4">Noted By:</p>
                        <p class="text-sm text-white italic border-t border-gray-600 pt-1">Fritz Daniel Martinez</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
