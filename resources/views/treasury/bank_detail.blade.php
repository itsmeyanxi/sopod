@extends('layouts.app')

@section('title', $account->short_name ?: $account->bank_name)

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; }
.stat-val { font-size:1.5rem; font-weight:800; color:#111827; line-height:1.1; margin:.1rem 0; }

.txn-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.txn-table thead th { background:#1e3a5f; color:#fff; padding:.5rem .75rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.txn-table thead th.r { text-align:right; }
.txn-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.txn-table tbody tr:hover { background:#f8fbff; }
.txn-table tbody td { padding:.5rem .75rem; color:#374151; vertical-align:middle; }
.txn-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }

.search-input { padding:.38rem .65rem; border:1px solid #d1d5db; border-radius:.375rem; font-size:.83rem; color:#111827; }
.search-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }

.badge-type { display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.65rem; font-weight:700; }
.badge-deposit    { background:#dcfce7; color:#15803d; }
.badge-withdrawal { background:#fee2e2; color:#991b1b; }
.badge-transfer   { background:#dbeafe; color:#1d4ed8; }
.badge-fee        { background:#fef3c7; color:#92400e; }
.badge-interest   { background:#f0fdf4; color:#166534; }
.badge-adjustment { background:#f3f4f6; color:#374151; }

.info-row { display:flex; gap:.5rem; align-items:center; font-size:.82rem; }
.info-label { font-weight:600; color:#6b7280; min-width:110px; }
.info-value { color:#111827; font-weight:500; }
</style>

@php
    $sym = $account->currency === 'PHP' ? '₱' : '$';
    $colors = [
        'BDO'=>'#0052a0','UNIONBANK'=>'#f97316','SECURITY BANK'=>'#7c3aed','PBB'=>'#dc2626',
        'AUB'=>'#059669','METROBANK'=>'#7c3aed','PBCOM'=>'#0e7490','BPI'=>'#dc2626',
        'BOC'=>'#b45309','CHINABANK'=>'#be123c','RCBC'=>'#1d4ed8','MAYBANK'=>'#f59e0b',
    ];
    $color = $colors[$account->bank_name] ?? '#6b7280';
@endphp

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <div style="width:48px;height:48px;border-radius:.5rem;background:{{ $color }};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.8rem;">
            {{ strtoupper(substr($account->bank_name, 0, 3)) }}
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">{{ $account->short_name ?: $account->bank_name }}</h2>
            <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $account->account_number }} · {{ $account->bank_name }} · {{ $account->account_type ?: 'N/A' }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('treasury.banks', $account->currency === 'PHP' ? 'peso' : 'dollar') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm bg-gray-700 text-gray-200 rounded-md hover:bg-gray-600 font-semibold border border-gray-600">
            <i class="fas fa-arrow-left"></i> Back to {{ $account->currency === 'PHP' ? 'Peso' : 'Dollar' }} Accounts
        </a>
        <button onclick="document.getElementById('add-txn-modal').classList.remove('hidden')"
                class="flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold shadow-sm">
            <i class="fas fa-plus"></i> Add Transaction
        </button>
    </div>
</div>

@if(session('success'))
<div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
</div>
@endif

<!-- ACCOUNT INFO + STATS -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
    <div class="stat-card md:col-span-1" style="border-color:{{ $color }};background:linear-gradient(135deg,#f8fafc,#fff);">
        <div class="stat-lbl" style="color:{{ $color }};">Current Balance</div>
        <div class="stat-val" style="font-size:1.3rem;">{{ $sym }} {{ number_format($account->display_balance ?? $account->cash_balance, 2) }}</div>
        @if($account->balance_as_of)
        <div class="text-xs text-gray-400 mt-0.5">as of {{ $account->balance_as_of->format('M d, Y') }}</div>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Total Deposits</div>
        <div class="stat-val text-green-700" style="font-size:1.15rem;">{{ $sym }} {{ number_format($stats['total_deposits'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Total Withdrawals</div>
        <div class="stat-val text-red-700" style="font-size:1.15rem;">{{ $sym }} {{ number_format($stats['total_withdrawals'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Transactions</div>
        <div class="stat-val">{{ $stats['txn_count'] }}</div>
    </div>
</div>

<!-- GL ACCOUNT LINK -->
@if($account->glAccount)
<div class="b mb-5 px-4 py-3">
    <div class="flex items-center gap-3">
        <i class="fas fa-link text-blue-500"></i>
        <div>
            <span class="text-xs font-bold text-gray-500 uppercase">Linked G/L Account</span>
            <div class="text-sm font-semibold text-white">{{ $account->glAccount->account_code }} — {{ $account->glAccount->account_name }}</div>
        </div>
        <a href="{{ route('gl_accounts.show', $account->glAccount->id) }}" target="_blank" class="ml-auto text-xs text-blue-600 hover:underline font-semibold">
            <i class="fas fa-external-link-alt mr-0.5"></i> View GL Account
        </a>
    </div>
</div>
@endif

<!-- TRANSACTIONS TABLE -->
<div class="b">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 gap-3 flex-wrap">
        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="search-input" style="width:140px;">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="search-input" style="width:140px;">
            <select name="type" class="search-input" style="width:140px;">
                <option value="">All Types</option>
                @foreach(['Deposit','Withdrawal','Transfer','Fee','Interest','Adjustment'] as $t)
                <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-xs font-semibold text-gray-200 hover:bg-gray-600">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['date_from','date_to','type']))
            <a href="{{ route('treasury.bank.show', $account->id) }}" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-200">Clear</a>
            @endif
        </form>
        <span class="text-xs text-gray-500">{{ $transactions->total() }} transaction{{ $transactions->total() != 1 ? 's' : '' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="txn-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Payee / Source</th>
                    <th class="r">Debit (In)</th>
                    <th class="r">Credit (Out)</th>
                    <th class="r">Running Balance</th>
                    <th>Logged By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($transactions as $txn)
                @php
                    $badgeClass = 'badge-' . strtolower($txn->type);
                @endphp
                <tr>
                    <td class="text-gray-400 text-xs">{{ $txn->id }}</td>
                    <td class="text-xs">{{ $txn->txn_date->format('M d, Y') }}</td>
                    <td><span class="badge-type {{ $badgeClass }}">{{ $txn->type }}</span></td>
                    <td class="font-mono text-xs">{{ $txn->reference ?: '—' }}</td>
                    <td>{{ $txn->description ?: '—' }}</td>
                    <td class="text-xs text-gray-500">{{ $txn->payee_or_source ?: '—' }}</td>
                    <td class="r font-semibold text-green-700">{{ $txn->debit > 0 ? $sym.' '.number_format($txn->debit, 2) : '' }}</td>
                    <td class="r font-semibold text-red-600">{{ $txn->credit > 0 ? $sym.' '.number_format($txn->credit, 2) : '' }}</td>
                    <td class="r font-bold">{{ $sym }} {{ number_format($txn->running_balance, 2) }}</td>
                    <td class="text-xs text-gray-500">{{ $txn->logged_by ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center py-10 text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                    No transactions recorded yet. Click "Add Transaction" to log one.
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $transactions->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- ADD TRANSACTION MODAL -->
<div id="add-txn-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.4);">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-700">
            <h3 class="font-bold text-white">Add Transaction — {{ $account->short_name }}</h3>
            <button onclick="document.getElementById('add-txn-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('treasury.bank.addTransaction', $account->id) }}" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Date *</label>
                    <input type="date" name="txn_date" value="{{ date('Y-m-d') }}" required class="search-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Type *</label>
                    <select name="type" required class="search-input w-full">
                        <option value="Deposit">Deposit</option>
                        <option value="Withdrawal">Withdrawal</option>
                        <option value="Transfer">Transfer</option>
                        <option value="Fee">Fee</option>
                        <option value="Interest">Interest</option>
                        <option value="Adjustment">Adjustment</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required class="search-input w-full" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Reference</label>
                    <input type="text" name="reference" class="search-input w-full" placeholder="Check #, transfer ref...">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Payee / Source</label>
                <input type="text" name="payee_or_source" class="search-input w-full" placeholder="Who paid or received?">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Description</label>
                <input type="text" name="description" class="search-input w-full" placeholder="Brief description...">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('add-txn-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm bg-gray-700 text-gray-200 rounded-md border border-gray-600 font-semibold hover:bg-gray-600">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 text-sm bg-blue-700 text-white rounded-md font-semibold hover:bg-blue-800">
                    <i class="fas fa-save mr-1"></i> Save Transaction
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
