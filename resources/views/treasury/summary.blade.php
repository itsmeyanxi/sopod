@extends('layouts.app')

@section('title', 'Bank')

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; }
.stat-val { font-size:1.5rem; font-weight:800; color:#111827; line-height:1.1; margin:.1rem 0; }

.ts-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.ts-table thead th { background:#1e3a5f; color:#fff; padding:.5rem .75rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.ts-table thead th.r { text-align:right; }
.ts-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.ts-table tbody tr:hover { background:#f8fbff; }
.ts-table tbody td { padding:.5rem .75rem; color:#374151; vertical-align:middle; }
.ts-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }
.ts-table tfoot td { padding:.6rem .75rem; font-weight:700; background:#f9fafb; border-top:2px solid #e5e7eb; }

.search-input { padding:.38rem .65rem; border:1px solid #d1d5db; border-radius:.375rem; font-size:.83rem; color:#111827; }
.search-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }

.badge-confirmed { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; background:#dcfce7; color:#15803d; border:1px solid #86efac; }
.credit-row { background:linear-gradient(135deg,#fefce8,#fff) !important; }
.credit-row td { color:#854d0e !important; font-weight:600; }

.currency-card {
    display:flex; align-items:center; justify-content:space-between;
    background:#fff; border:2px solid #e5e7eb; border-radius:.6rem;
    padding:1rem 1.5rem; cursor:pointer; transition:all .18s;
    text-decoration:none;
}
.currency-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.08); }
.currency-card.peso { border-color:#3b82f6; background:linear-gradient(135deg,#eff6ff,#fff); }
.currency-card.peso:hover { border-color:#2563eb; box-shadow:0 4px 12px rgba(59,130,246,.15); }
.currency-card.dollar { border-color:#22c55e; background:linear-gradient(135deg,#f0fdf4,#fff); }
.currency-card.dollar:hover { border-color:#16a34a; box-shadow:0 4px 12px rgba(34,197,94,.15); }
.currency-card .cc-icon { width:48px; height:48px; border-radius:.5rem; display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:#fff; font-weight:800; flex-shrink:0; }
.currency-card .cc-title { font-size:1rem; font-weight:800; color:#1f2937; }
.currency-card .cc-sub { font-size:.72rem; color:#6b7280; }
.currency-card .cc-balance { font-size:1.2rem; font-weight:800; text-align:right; }
.currency-card .cc-chevron { font-size:.8rem; color:#9ca3af; margin-left:.75rem; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-white">Bank</h2>
        <p class="text-xs text-gray-500 mt-0.5">Bank — Overview of confirmed payments & credit balance</p>
    </div>
    <a href="{{ route('treasury.confirmation') }}"
       class="flex items-center gap-1.5 px-4 py-2 text-sm bg-amber-500 text-white rounded-md hover:bg-amber-600 font-semibold shadow-sm">
        <i class="fas fa-clipboard-check"></i> Payment Confirmation
    </a>
</div>

<!-- STAT CARDS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="stat-card" style="border-color:#86efac;background:linear-gradient(135deg,#f0fdf4,#fff);">
        <div class="stat-lbl" style="color:#15803d;">Total Confirmed</div>
        <div class="stat-val text-green-700">{{ $stats['total_count'] }}</div>
        <div class="text-xs text-green-600 mt-0.5">PHP {{ number_format($stats['total_amount'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Total Net Amount</div>
        <div class="stat-val">PHP {{ number_format($stats['total_net'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Confirmed Today</div>
        <div class="stat-val text-blue-700">{{ $stats['today_count'] }}</div>
        <div class="text-xs text-blue-500 mt-0.5">PHP {{ number_format($stats['today_amount'], 2) }}</div>
    </div>
    <div class="stat-card" style="border-color:#fbbf24;background:linear-gradient(135deg,#fefce8,#fff);">
        <div class="stat-lbl" style="color:#b45309;">Remaining Credit Balance</div>
        <div class="stat-val text-amber-700">PHP {{ number_format($stats['remaining_credit'], 2) }}</div>
        <div class="text-xs text-amber-500 mt-0.5">Overpayments: PHP {{ number_format($stats['total_overpayment'], 2) }}</div>
    </div>
</div>

<!-- BANK ACCOUNT CATEGORIES -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    <a href="{{ route('treasury.banks', 'peso') }}" class="currency-card peso">
        <div class="flex items-center gap-3">
            <div class="cc-icon" style="background:#2563eb;">₱</div>
            <div>
                <div class="cc-title">PESO ACCOUNTS</div>
                <div class="cc-sub">{{ $bankStats['pesoCount'] }} active bank account{{ $bankStats['pesoCount'] != 1 ? 's' : '' }}</div>
            </div>
        </div>
        <div class="flex items-center">
            <div>
                <div class="cc-balance text-blue-800">₱ {{ number_format($bankStats['pesoTotal'], 2) }}</div>
                <div class="cc-sub text-right">Total Cash Balance</div>
            </div>
            <div class="cc-chevron"><i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
    <a href="{{ route('treasury.banks', 'dollar') }}" class="currency-card dollar">
        <div class="flex items-center gap-3">
            <div class="cc-icon" style="background:#16a34a;">$</div>
            <div>
                <div class="cc-title">DOLLAR ACCOUNTS</div>
                <div class="cc-sub">{{ $bankStats['dollarCount'] }} active bank account{{ $bankStats['dollarCount'] != 1 ? 's' : '' }}</div>
            </div>
        </div>
        <div class="flex items-center">
            <div>
                <div class="cc-balance text-green-800">$ {{ number_format($bankStats['dollarTotal'], 2) }}</div>
                <div class="cc-sub text-right">Total Cash Balance</div>
            </div>
            <div class="cc-chevron"><i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
</div>

<!-- TABLE CARD -->
<div class="b">
    <!-- Toolbar -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 gap-3 flex-wrap">
        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" class="search-input" style="width:220px;" placeholder="Search customer, CR#, invoice...">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="search-input" style="width:140px;" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="search-input" style="width:140px;" placeholder="To">
            <button type="submit" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-xs font-semibold text-gray-200 hover:bg-gray-600">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['search','date_from','date_to']))
            <a href="{{ route('treasury.summary') }}" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-200">Clear</a>
            @endif
        </form>
        <span class="text-xs text-gray-500">{{ $payments->total() }} confirmed payment{{ $payments->total() != 1 ? 's' : '' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>CR Number</th>
                    <th>Customer</th>
                    <th>Invoice #</th>
                    <th>DR #</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th class="r">Amount</th>
                    <th class="r">Tax</th>
                    <th class="r">Net</th>
                    <th class="r">Overpayment</th>
                    <th class="r">Credit Applied</th>
                    <th>Confirmed By</th>
                    <th>Confirmed At</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                <tr @if($payment->overpayment > 0) class="credit-row" @endif>
                    <td class="font-semibold text-blue-700">{{ $payment->collection_receipt_number ?: '—' }}</td>
                    <td>{{ $payment->customer_name ?: '—' }}</td>
                    <td class="text-gray-500 text-xs">{{ $payment->invoice_no ?: '—' }}</td>
                    <td class="text-gray-500 text-xs">{{ $payment->dr_no ?: '—' }}</td>
                    <td class="text-gray-500 text-xs">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                    <td class="text-xs">{{ ucfirst($payment->payment_method ?? '—') }}</td>
                    <td class="r font-semibold">PHP {{ number_format($payment->amount, 2) }}</td>
                    <td class="r text-gray-500">{{ number_format($payment->tax, 2) }}</td>
                    <td class="r font-semibold text-white">PHP {{ number_format($payment->net ?? $payment->amount, 2) }}</td>
                    <td class="r">
                        @if($payment->overpayment > 0)
                            <span class="text-amber-600 font-bold">PHP {{ number_format($payment->overpayment, 2) }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="r">
                        @if($payment->credit_applied > 0)
                            <span class="text-green-600 font-semibold">PHP {{ number_format($payment->credit_applied, 2) }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-xs text-gray-500">{{ $payment->confirmed_by ?: '—' }}</td>
                    <td class="text-xs text-gray-500">{{ $payment->confirmed_at ? \Carbon\Carbon::parse($payment->confirmed_at)->format('M d, Y h:i A') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-center py-10 text-gray-400">No confirmed payments yet.</td></tr>
            @endforelse
            </tbody>

            @if($payments->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right text-gray-300">Page Totals:</td>
                    <td class="r">PHP {{ number_format($payments->sum('amount'), 2) }}</td>
                    <td class="r">{{ number_format($payments->sum('tax'), 2) }}</td>
                    <td class="r">PHP {{ number_format($payments->sum(fn($p) => $p->net ?? $p->amount), 2) }}</td>
                    <td class="r text-amber-600 font-bold">PHP {{ number_format($payments->sum('overpayment'), 2) }}</td>
                    <td class="r text-green-600 font-bold">PHP {{ number_format($payments->sum('credit_applied'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <!-- Credit Balance Summary Row -->
                <tr class="credit-row">
                    <td colspan="6" class="text-right">
                        <i class="fas fa-coins text-amber-500 mr-1"></i> Overall Credit / Overage Balance:
                    </td>
                    <td colspan="7" class="text-left" style="font-size:.9rem;">
                        <span class="text-amber-700 font-bold">PHP {{ number_format($stats['remaining_credit'], 2) }}</span>
                        <span class="text-xs text-gray-500 ml-2">(Total Overpayments: PHP {{ number_format($stats['total_overpayment'], 2) }} — Credits Used: PHP {{ number_format($stats['total_overpayment'] - $stats['remaining_credit'], 2) }})</span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $payments->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
