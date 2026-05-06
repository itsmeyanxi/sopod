@extends('layouts.app')

@section('title', $label)

@section('content')
<style>
.b { background:#162030; border:1px solid #2a3f55; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.3); }
.stat-card { background:#162030; border:1px solid #2a3f55; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#4d6880; }
.stat-val { font-size:1.5rem; font-weight:800; color:#e2eaf4; line-height:1.1; margin:.1rem 0; }

.bank-card {
    background:#162030; border:1px solid #2a3f55; border-radius:.5rem;
    padding:1rem 1.25rem; cursor:pointer; transition:all .15s;
    display:flex; align-items:center; gap:1rem;
}
.bank-card:hover { border-color:#3b82f6; box-shadow:0 2px 8px rgba(59,130,246,.2); transform:translateY(-1px); }
.bank-icon {
    width:42px; height:42px; border-radius:.4rem; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:.7rem; color:#fff; flex-shrink:0;
}
.bank-card .acct-num { font-size:.72rem; color:#8fa8c0; font-family:monospace; }
.bank-card .bank-label { font-size:.88rem; font-weight:700; color:#e2eaf4; }
.bank-card .balance { font-size:1.05rem; font-weight:800; color:#e2eaf4; text-align:right; margin-left:auto; }
.bank-card .balance-date { font-size:.62rem; color:#4d6880; text-align:right; }

.bank-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.bank-table thead th { background:#1e3a5f; color:#fff; padding:.5rem .75rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.bank-table thead th.r { text-align:right; }
.bank-table tbody tr { border-bottom:1px solid #1e2d3d; transition:background .1s; cursor:pointer; }
.bank-table tbody tr:hover { background:#1e2d3d; }
.bank-table tbody td { padding:.55rem .75rem; color:#c0cfe0; vertical-align:middle; }
.bank-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }
.bank-table tfoot td { padding:.6rem .75rem; font-weight:700; background:#1e2d3d; border-top:2px solid #2a3f55; color:#e2eaf4; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-white">{{ $label }}</h2>
        <p class="text-xs text-gray-300 mt-0.5">Treasury — Bank accounts ({{ $cur === 'PHP' ? '₱ Peso' : '$ Dollar' }})</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('treasury.summary') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm bg-gray-700 text-gray-200 rounded-md hover:bg-gray-600 font-semibold border border-gray-600">
            <i class="fas fa-arrow-left"></i> Back
        </a>
            @if($cur === 'PHP')
        <a href="{{ route('treasury.banks', 'dollar') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold">
            <i class="fas fa-dollar-sign"></i> Dollar Accounts
        </a>
        @else
        <a href="{{ route('treasury.banks', 'peso') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold">
            <i class="fas fa-peso-sign"></i> Peso Accounts
        </a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="flex items-center gap-2 bg-green-900 border border-green-700 text-green-300 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-check-circle text-green-400"></i> {{ session('success') }}
</div>
@endif

<!-- TOTAL BALANCE CARD -->
<div class="stat-card mb-5" style="border-color:{{ $cur==='PHP' ? '#3b82f6' : '#22c55e' }};background:linear-gradient(135deg,{{ $cur==='PHP' ? '#1e3a5f22' : '#14532d22' }},#162030);">
    <div class="stat-lbl" style="color:{{ $cur==='PHP' ? '#60a5fa' : '#4ade80' }};">Total {{ $cur === 'PHP' ? 'Peso' : 'Dollar' }} Cash Balance</div>
    <div class="stat-val" style="color:{{ $cur==='PHP' ? '#93c5fd' : '#86efac' }};">
        {{ $cur === 'PHP' ? '₱' : '$' }} {{ number_format($totalBalance, 2) }}
    </div>
    <div class="text-xs text-gray-400 mt-0.5">{{ $accounts->count() }} active account{{ $accounts->count() != 1 ? 's' : '' }}</div>
</div>

<!-- BANK CARDS GRID -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
    @foreach($accounts as $acct)
    @php
        $colors = [
            'BDO'=>'#0052a0','UNIONBANK'=>'#f97316','SECURITY BANK'=>'#7c3aed','PBB'=>'#dc2626',
            'AUB'=>'#059669','METROBANK'=>'#7c3aed','PBCOM'=>'#0e7490','BPI'=>'#dc2626',
            'BOC'=>'#b45309','CHINABANK'=>'#be123c','RCBC'=>'#1d4ed8','MAYBANK'=>'#f59e0b',
        ];
        $color = $colors[$acct->bank_name] ?? '#6b7280';
    @endphp
    <a href="{{ route('treasury.bank.show', $acct->id) }}" class="bank-card">
        <div class="bank-icon" style="background:{{ $color }};">
            {{ strtoupper(substr($acct->bank_name, 0, 3)) }}
        </div>
        <div>
            <div class="bank-label">{{ $acct->short_name ?: $acct->bank_name }}</div>
            <div class="acct-num">{{ $acct->account_number }}</div>
        </div>
        <div style="margin-left:auto;text-align:right;">
            <div class="balance">{{ $cur === 'PHP' ? '₱' : '$' }} {{ number_format($acct->display_balance ?? $acct->cash_balance, 2) }}</div>
            @if($acct->balance_as_of)
            <div class="balance-date">as of {{ $acct->balance_as_of->format('M d, Y') }}</div>
            @endif
        </div>
    </a>
    @endforeach
</div>

<!-- TABLE VIEW -->
<div class="b">
    <div class="px-4 py-3 border-b border-gray-700">
        <span class="text-xs font-bold text-gray-300 uppercase tracking-wider">Detailed List</span>
    </div>
    <div class="overflow-x-auto">
        <table class="bank-table">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Account Number</th>
                    <th>Bank</th>
                    <th>Short Name</th>
                    <th>Type</th>
                    <th>GL Account</th>
                    <th class="r">Cash Balance</th>
                    <th>As Of</th>
                </tr>
            </thead>
            <tbody>
            @foreach($accounts as $i => $acct)
                <tr onclick="window.location='{{ route('treasury.bank.show', $acct->id) }}'">
                    <td class="text-gray-400">{{ $i + 1 }}</td>
                    <td class="font-semibold font-mono text-sm">{{ $acct->account_number }}</td>
                    <td>{{ $acct->bank_name }}</td>
                    <td class="font-semibold text-blue-400">{{ $acct->short_name ?: '—' }}</td>
                    <td>
                        @if($acct->account_type)
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold {{ $acct->account_type === 'SA' ? 'bg-blue-900 text-blue-300' : 'bg-purple-900 text-purple-300' }}">
                            {{ $acct->account_type }}
                        </span>
                        @else — @endif
                    </td>
                    <td class="text-xs text-gray-300">
                        @if($acct->glAccount)
                            {{ $acct->glAccount->account_code }} — {{ $acct->glAccount->account_name }}
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="r font-bold text-white">{{ $cur === 'PHP' ? '₱' : '$' }} {{ number_format($acct->display_balance ?? $acct->cash_balance, 2) }}</td>
                    <td class="text-xs text-gray-300">{{ $acct->balance_as_of ? $acct->balance_as_of->format('M d, Y') : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right text-gray-300">TOTAL:</td>
                    <td class="r text-white" style="font-size:.95rem;">{{ $cur === 'PHP' ? '₱' : '$' }} {{ number_format($totalBalance, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
