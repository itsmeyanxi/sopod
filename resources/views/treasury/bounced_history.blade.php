@extends('layouts.app')
@section('title', 'Bounced Payments')
@section('content')
<style>
.b { background:#162030; border:1px solid #2a3f55; border-radius:.5rem; }
.search-input { padding:.38rem .65rem; border:1px solid #3a5570; border-radius:.375rem; font-size:.83rem; color:#e2eaf4; background:#1e2d3d; }
.search-input:focus { outline:none; border-color:#3b82f6; }
.tbl { width:100%; border-collapse:collapse; font-size:.81rem; }
.tbl thead th { background:#1e3a5f; color:#fff; padding:.45rem .7rem; font-size:.68rem; font-weight:600; text-align:left; white-space:nowrap; }
.tbl tbody tr { border-bottom:1px solid #1e2d3d; transition:background .1s; }
.tbl tbody tr:hover { background:#1e2d3d; }
.tbl tbody td { padding:.45rem .7rem; color:#c0cfe0; vertical-align:middle; }
</style>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-white"><i class="fas fa-ban text-red-400 mr-2"></i>Bounced Payments</h2>
        <p class="text-xs text-gray-400 mt-0.5">Treasury — Rejected / bounced check history</p>
    </div>
    <a href="{{ route('treasury.confirmation') }}"
       class="flex items-center gap-1.5 px-4 py-2 text-sm bg-gray-700 text-gray-200 rounded-md hover:bg-gray-600 font-semibold border border-gray-600">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<div class="b">
    <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-700 flex-wrap">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" class="search-input" style="width:230px;" placeholder="Customer, CR#, DR#...">
            <button type="submit" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-xs font-semibold text-gray-200 hover:bg-gray-600">
                <i class="fas fa-search"></i> Search
            </button>
            @if(request('search'))
            <a href="{{ route('treasury.bounced-history') }}" class="text-xs text-gray-400 hover:text-gray-200">Clear</a>
            @endif
        </form>
        <span class="ml-auto text-xs text-gray-400">{{ $payments->total() }} record{{ $payments->total() != 1 ? 's' : '' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>CR Number</th>
                    <th>Customer</th>
                    <th>DR #</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Bounce Reason</th>
                    <th>Bounced By</th>
                    <th>Bounced At</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $p)
                <tr>
                    <td class="font-mono text-xs font-semibold text-blue-400">{{ $p->collection_receipt_number ?: '—' }}</td>
                    <td class="font-medium">{{ $p->customer_name ?: '—' }}</td>
                    <td class="font-mono text-xs">{{ $p->dr_no ?: '—' }}</td>
                    <td class="text-xs">{{ $p->payment_date ? $p->payment_date->format('M d, Y') : '—' }}</td>
                    <td class="text-xs">{{ $p->payment_method ?? $p->payment_option ?? '—' }}</td>
                    <td class="font-semibold text-red-400">PHP {{ number_format($p->gross_amount ?? $p->amount, 2) }}</td>
                    <td class="text-xs text-amber-300">{{ $p->bounce_reason ?: '—' }}</td>
                    <td class="text-xs text-gray-400">{{ $p->bounced_by ?: '—' }}</td>
                    <td class="text-xs text-gray-400">{{ $p->bounced_at ? $p->bounced_at->format('M d, Y H:i') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-10 text-gray-500">
                    <i class="fas fa-check-circle text-green-500 text-2xl mb-2 block"></i>
                    No bounced payments on record.
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-4 py-3 border-t border-gray-700">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
