@extends('layouts.app')

@section('title', $label ?? 'Bank Accounts')

@section('content')
<style>
/* ─── BASE ─────────────────────────────────────────────────────── */
.bm-wrap { color: #e2eaf4; }

/* ─── BUTTONS ───────────────────────────────────────────────────── */
.bm-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: .45rem .85rem; border-radius: 7px; font-size: .78rem;
    font-weight: 600; border: none; cursor: pointer; transition: all .15s;
    text-decoration: none;
}
.bm-btn-blue  { background: #3b82f6; color: #ffffff !important; }
.bm-btn-blue:hover { background: #1d4ed8; }
.bm-btn-gray  { background: #1e2d3d; color: #cbd5e1 !important; border: 1px solid #2a3f55; }
.bm-btn-gray:hover { background: #243447; color: #e2eaf4 !important; }
.bm-btn-green { background: #16a34a; color: #ffffff !important; }
.bm-btn-green:hover { background: #15803d; }
.bm-btn-red   { background: #7f1d1d; color: #fca5a5 !important; border: 1px solid #991b1b; }
.bm-btn-red:hover { background: #991b1b; }
.bm-btn-sm    { padding: .28rem .6rem; font-size: .71rem; }

/* ─── STAT CARDS ────────────────────────────────────────────────── */
.bm-stats { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1.25rem; }
.bm-stat  { background: #162030; border: 1px solid #2a3f55; border-radius: 9px; padding: .85rem 1rem; }
.bm-stat-lbl { font-size: .62rem; font-weight: 700; text-transform: uppercase;
               letter-spacing: .06em; color: #4d6880; margin-bottom: 4px; }
.bm-stat-val { font-size: 1.4rem; font-weight: 800; color: #e2eaf4; line-height: 1; }
.bm-stat-sub { font-size: .68rem; color: #4d6880; margin-top: 3px; }

/* ─── BANK CARDS ────────────────────────────────────────────────── */
.bm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: .75rem; margin-bottom: 1.25rem; }
.bm-card {
    background: #162030; border: 1px solid #2a3f55; border-radius: 10px;
    padding: 1rem 1.1rem; transition: all .15s; position: relative; display: block;
    text-decoration: none;
}
.bm-card:hover { border-color: #3b82f6; transform: translateY(-1px); }
.bm-card-top  { display: flex; align-items: center; gap: .75rem; }
.bm-icon {
    width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center;
    justify-content: center; font-weight: 800; font-size: .68rem; color: #fff; flex-shrink: 0;
}
.bm-bank-name  { font-size: .88rem; font-weight: 700; color: #e2eaf4; }
.bm-acct-num   { font-size: .7rem; color: #8fa8c0; font-family: monospace; margin-top: 2px; }
.bm-balance    { font-size: 1.05rem; font-weight: 800; color: #e2eaf4; margin-top: .65rem; }
.bm-bal-date   { font-size: .62rem; color: #4d6880; margin-top: 2px; }
.bm-card-actions {
    position: absolute; top: .65rem; right: .75rem;
    display: flex; gap: 5px; opacity: 0; transition: opacity .15s;
}
.bm-card:hover .bm-card-actions { opacity: 1; }

/* ─── BADGE ─────────────────────────────────────────────────────── */
.bm-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: .65rem; font-weight: 700; }
.bm-badge-sa { background: #1e3a5f; color: #93c5fd; }
.bm-badge-ca { background: #2d1b69; color: #c4b5fd; }

/* ─── TABLE ─────────────────────────────────────────────────────── */
.bm-table-wrap { background: #162030; border: 1px solid #2a3f55; border-radius: 10px; overflow: hidden; }
.bm-table-hdr  { padding: .7rem 1rem; border-bottom: 1px solid #2a3f55;
                 display: flex; align-items: center; justify-content: space-between; }
.bm-table-hdr span { font-size: .7rem; font-weight: 700; color: #4d6880;
                     text-transform: uppercase; letter-spacing: .06em; }
.bm-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.bm-table thead th {
    background: #1e3a5f; color: #93c5fd; padding: .5rem .8rem;
    font-size: .68rem; font-weight: 600; text-align: left; white-space: nowrap;
}
.bm-table thead th.r { text-align: right; }
.bm-table tbody tr { border-bottom: 1px solid #1e2d3d; transition: background .1s; }
.bm-table tbody tr:hover { background: #1e2d3d; }
.bm-table tbody td { padding: .5rem .8rem; color: #8fa8c0; vertical-align: middle; }
.bm-table tbody td.bm-td-bold { color: #e2eaf4; font-weight: 700; }
.bm-table tbody td.r { text-align: right; font-variant-numeric: tabular-nums;
                        color: #e2eaf4; font-weight: 700; }
.bm-table tbody td.bm-mono { font-family: monospace; font-weight: 600; font-size: .78rem; }
.bm-table tbody td.bm-blue  { color: #60a5fa; font-weight: 600; }
.bm-table tfoot td {
    padding: .55rem .8rem; font-weight: 700; background: #1e2d3d;
    border-top: 2px solid #2a3f55; color: #e2eaf4;
}
.bm-table tfoot td.gray-label { color: #4d6880; text-align: right; }

/* ─── MODAL OVERLAY ─────────────────────────────────────────────── */
.bm-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.75);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999; opacity: 0; pointer-events: none; transition: opacity .2s;
}
.bm-overlay.open { opacity: 1; pointer-events: all; }
.bm-modal {
    background: #162030; border: 1px solid #3a5570; border-radius: 14px;
    padding: 1.5rem; width: 480px; max-width: 95vw; max-height: 88vh;
    overflow-y: auto; transform: translateY(14px); transition: transform .2s;
}
.bm-overlay.open .bm-modal { transform: translateY(0); }
.bm-modal-title {
    font-size: 1rem; font-weight: 700; color: #e2eaf4;
    margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem;
}
.bm-modal-footer {
    display: flex; justify-content: flex-end; gap: .6rem;
    margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #2a3f55;
}

/* ─── FORM FIELDS ───────────────────────────────────────────────── */
.bm-form-row  { margin-bottom: .85rem; }
.bm-form-2col { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.bm-label {
    font-size: .72rem; font-weight: 600; color: #8fa8c0;
    text-transform: uppercase; letter-spacing: .04em;
    display: block; margin-bottom: .35rem;
}
.bm-input, .bm-select {
    width: 100%; background: #1e2d3d; border: 1px solid #3a5570;
    border-radius: 7px; padding: .5rem .75rem; font-size: .83rem;
    color: #e2eaf4; outline: none; transition: border-color .15s;
}
.bm-input:focus, .bm-select:focus { border-color: #3b82f6; }
.bm-input::placeholder { color: #4d6880; }
.bm-select option { background: #1e2d3d; color: #e2eaf4; }
.bm-color-row { display: flex; gap: .5rem; align-items: center; }
.bm-color-picker {
    width: 44px; height: 38px; padding: 2px; cursor: pointer;
    background: #1e2d3d; border: 1px solid #3a5570; border-radius: 7px;
}

/* ─── DELETE MODAL ──────────────────────────────────────────────── */
.bm-del-title { color: #fca5a5; font-size: .95rem; font-weight: 700; margin-bottom: .6rem; }
.bm-del-body  { color: #8fa8c0; font-size: .82rem; line-height: 1.6; }
.bm-del-footer {
    display: flex; justify-content: flex-end; gap: .6rem;
    margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #991b1b;
}

/* ─── ALERT ─────────────────────────────────────────────────────── */
.bm-alert {
    display: flex; align-items: center; gap: .5rem;
    background: #14532d; border: 1px solid #16a34a; color: #86efac;
    font-size: .82rem; border-radius: 8px; padding: .65rem 1rem; margin-bottom: 1rem;
}

/* ─── CURRENCY TABS ─────────────────────────────────────────────── */
.bm-tabs { display: flex; gap: .5rem; margin-bottom: 1.1rem; }
.bm-tab {
    padding: .4rem .9rem; border-radius: 7px; font-size: .78rem;
    font-weight: 600; cursor: pointer; border: 1px solid #2a3f55;
    background: #1e2d3d; color: #8fa8c0; transition: all .15s;
    text-decoration: none; display: inline-block;
}
.bm-tab-php { background: #1e3a5f; border-color: #3b82f6; color: #93c5fd; }
.bm-tab-usd { background: #14532d; border-color: #22c55e; color: #86efac; }
</style>

<div class="bm-wrap">

    {{-- ─── HEADER ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-xl font-bold text-white">{{ $label ?? 'Bank Accounts' }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                Treasury — Bank account management
                ({{ ($cur ?? 'PHP') === 'PHP' ? '₱ Peso' : '$ Dollar' }})
            </p>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <a href="{{ route('treasury.summary') }}" class="bm-btn bm-btn-gray">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            @if(($cur ?? 'PHP') === 'PHP')
            <a href="{{ route('treasury.banks', 'dollar') }}" class="bm-btn bm-btn-green">
                <i class="fas fa-dollar-sign"></i> Dollar Accounts
            </a>
            @else
            <a href="{{ route('treasury.banks', 'peso') }}" class="bm-btn bm-btn-blue">
                <i class="fas fa-peso-sign"></i> Peso Accounts
            </a>
            @endif
        </div>
    </div>

    {{-- ─── FLASH ─────────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="bm-alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    {{-- ─── STAT CARDS ────────────────────────────────────────────── --}}
    <div class="bm-stats">
        <div class="bm-stat"
             style="border-color:{{ ($cur ?? 'PHP') === 'PHP' ? '#3b82f6' : '#22c55e' }};
                    background:linear-gradient(135deg,{{ ($cur ?? 'PHP') === 'PHP' ? '#1e3a5f22' : '#14532d22' }},#162030);">
            <div class="bm-stat-lbl" style="color:{{ ($cur ?? 'PHP') === 'PHP' ? '#60a5fa' : '#4ade80' }};">
                Total {{ ($cur ?? 'PHP') === 'PHP' ? 'Peso' : 'Dollar' }} Cash Balance
            </div>
            <div class="bm-stat-val" style="color:{{ ($cur ?? 'PHP') === 'PHP' ? '#93c5fd' : '#86efac' }};">
                {{ ($cur ?? 'PHP') === 'PHP' ? '₱' : '$' }} {{ number_format($totalBalance ?? 0, 2) }}
            </div>
            <div class="bm-stat-sub">
                {{ $accounts->count() }} active account{{ $accounts->count() != 1 ? 's' : '' }}
            </div>
        </div>
        <div class="bm-stat">
            <div class="bm-stat-lbl">Currency</div>
            <div class="bm-stat-val" style="color:{{ ($cur ?? 'PHP') === 'PHP' ? '#60a5fa' : '#4ade80' }};">
                {{ ($cur ?? 'PHP') === 'PHP' ? 'Philippine Peso' : 'US Dollar' }}
            </div>
            <div class="bm-stat-sub">Symbol: {{ ($cur ?? 'PHP') === 'PHP' ? '₱' : '$' }}</div>
        </div>
    </div>

    {{-- ─── BANK CARDS GRID ────────────────────────────────────────── --}}
    <div class="bm-grid">
        @foreach($accounts as $acct)
        @php
            $bankColors = [
                'BDO' => '#0052a0', 'UNIONBANK' => '#f97316', 'SECURITY BANK' => '#7c3aed',
                'PBB' => '#dc2626', 'AUB' => '#059669', 'METROBANK' => '#7c3aed',
                'PBCOM' => '#0e7490', 'BPI' => '#dc2626', 'BOC' => '#b45309',
                'CHINABANK' => '#be123c', 'RCBC' => '#1d4ed8', 'MAYBANK' => '#f59e0b',
            ];
            $iconColor = $acct->icon_color ?? ($bankColors[$acct->bank_name] ?? '#6b7280');
        @endphp
        <div class="bm-card">
            <div class="bm-card-actions">
                <button type="button"
                    onclick="bmOpenEditModal({{ $acct->id }}, '{{ addslashes($acct->bank_name) }}', '{{ addslashes($acct->short_name ?? '') }}', '{{ $acct->account_number }}', '{{ $acct->account_type ?? '' }}', '{{ $acct->currency ?? ($cur ?? 'PHP') }}', {{ $acct->cash_balance ?? 0 }}, '{{ $acct->balance_as_of ? $acct->balance_as_of->format('Y-m-d') : '' }}', '{{ $iconColor }}')"
                    class="bm-btn bm-btn-gray bm-btn-sm">
                    <i class="fas fa-pencil-alt"></i> Edit
                </button>
                @if(auth()->user()->isAdminUser())
                <button type="button"
                    onclick="bmOpenDelModal({{ $acct->id }}, '{{ addslashes($acct->short_name ?? $acct->bank_name) }}')"
                    class="bm-btn bm-btn-red bm-btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>

            <a href="{{ route('treasury.bank.show', $acct->id) }}" style="text-decoration:none;display:block;">
                <div class="bm-card-top">
                    <div class="bm-icon" style="background:{{ $iconColor }};">
                        {{ strtoupper(substr($acct->bank_name, 0, 3)) }}
                    </div>
                    <div>
                        <div class="bm-bank-name">{{ $acct->short_name ?: $acct->bank_name }}</div>
                        <div class="bm-acct-num">{{ $acct->account_number }}</div>
                    </div>
                </div>
                <div class="bm-balance">
                    {{ ($acct->currency ?? ($cur ?? 'PHP')) === 'PHP' ? '₱' : '$' }}
                    {{ number_format($acct->display_balance ?? $acct->cash_balance ?? 0, 2) }}
                </div>
                <div class="bm-bal-date">
                    @if($acct->account_type)
                    <span class="bm-badge bm-badge-{{ strtolower($acct->account_type) }}">{{ $acct->account_type }}</span>
                    @endif
                    @if($acct->balance_as_of)
                    as of {{ $acct->balance_as_of->format('M d, Y') }}
                    @endif
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- ─── TABLE VIEW ─────────────────────────────────────────────── --}}
    <div class="bm-table-wrap">
        <div class="bm-table-hdr">
            <span>Detailed List</span>
            <span>{{ $accounts->count() }} records</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="bm-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Account Number</th>
                        <th>Bank</th>
                        <th>Short Name</th>
                        <th>Type</th>
                        <th class="r">Cash Balance</th>
                        <th>As Of</th>
                        @if(auth()->user()->isAdminUser())
                        <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @foreach($accounts as $i => $acct)
                @php
                    $iconColor2 = $acct->icon_color ?? ($bankColors[$acct->bank_name] ?? '#6b7280');
                @endphp
                    <tr>
                        <td style="color:#4d6880;">{{ $i + 1 }}</td>
                        <td class="bm-mono">{{ $acct->account_number }}</td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <span style="width:18px;height:18px;border-radius:4px;background:{{ $iconColor2 }};
                                    display:inline-flex;align-items:center;justify-content:center;
                                    font-size:.45rem;font-weight:800;color:#fff;">
                                    {{ strtoupper(substr($acct->bank_name, 0, 3)) }}
                                </span>
                                {{ $acct->bank_name }}
                            </span>
                        </td>
                        <td class="bm-blue">{{ $acct->short_name ?: '—' }}</td>
                        <td>
                            @if($acct->account_type)
                            <span class="bm-badge bm-badge-{{ strtolower($acct->account_type) }}">{{ $acct->account_type }}</span>
                            @else — @endif
                        </td>
                        <td class="r">
                            {{ ($acct->currency ?? ($cur ?? 'PHP')) === 'PHP' ? '₱' : '$' }}
                            {{ number_format($acct->display_balance ?? $acct->cash_balance ?? 0, 2) }}
                        </td>
                        <td style="font-size:.72rem;color:#4d6880;">
                            {{ $acct->balance_as_of ? $acct->balance_as_of->format('M d, Y') : '—' }}
                        </td>
                        @if(auth()->user()->isAdminUser())
                        <td style="white-space:nowrap;gap:4px;">
                            <button type="button"
                                onclick="bmOpenEditModal({{ $acct->id }}, '{{ addslashes($acct->bank_name) }}', '{{ addslashes($acct->short_name ?? '') }}', '{{ $acct->account_number }}', '{{ $acct->account_type ?? '' }}', '{{ $acct->currency ?? ($cur ?? 'PHP') }}', {{ $acct->cash_balance ?? 0 }}, '{{ $acct->balance_as_of ? $acct->balance_as_of->format('Y-m-d') : '' }}', '{{ $iconColor2 }}')"
                                class="bm-btn bm-btn-gray bm-btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                            <button type="button"
                                onclick="bmOpenDelModal({{ $acct->id }}, '{{ addslashes($acct->short_name ?? $acct->bank_name) }}')"
                                class="bm-btn bm-btn-red bm-btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="gray-label" style="color:#4d6880;text-align:right;">TOTAL:</td>
                        <td class="r" style="font-size:.95rem;">
                            {{ ($cur ?? 'PHP') === 'PHP' ? '₱' : '$' }}
                            {{ number_format($totalBalance ?? 0, 2) }}
                        </td>
                        <td colspan="{{ auth()->user()->isAdminUser() ? 2 : 1 }}"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>{{-- /bm-wrap --}}


{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- ADD / EDIT MODAL                                                --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="bm-overlay" id="bm-modal" onclick="bmBgClose(event,'bm-modal')">
    <div class="bm-modal">
        <div class="bm-modal-title" id="bm-modal-title">
            <i class="fas fa-university" style="color:#3b82f6;font-size:.9rem;"></i>
            Add Bank Account
        </div>

        <form id="bm-form" method="POST" action="{{ route('treasury.banks.store') }}">
            @csrf
            <input type="hidden" name="_method" id="bm-method" value="POST">
            <input type="hidden" name="id" id="bm-id">

            <div class="bm-form-2col">
                <div class="bm-form-row">
                    <label class="bm-label">Bank Name *</label>
                    <input type="text" name="bank_name" id="bm-f-bank"
                           class="bm-input" placeholder="e.g. BDO, BPI, AUB" required>
                </div>
                <div class="bm-form-row">
                    <label class="bm-label">Short Name</label>
                    <input type="text" name="short_name" id="bm-f-short"
                           class="bm-input" placeholder="e.g. BDO SA">
                </div>
            </div>

            <div class="bm-form-row">
                <label class="bm-label">Account Number *</label>
                <input type="text" name="account_number" id="bm-f-acct"
                       class="bm-input" placeholder="e.g. 0043-5002-4240" required>
            </div>

            <div class="bm-form-2col">
                <div class="bm-form-row">
                    <label class="bm-label">Account Type</label>
                    <select name="account_type" id="bm-f-type" class="bm-select">
                        <option value="">— Select —</option>
                        <option value="SA">SA — Savings Account</option>
                        <option value="CA">CA — Current Account</option>
                    </select>
                </div>
                {{-- ─── CURRENCY: free-text input with common suggestions ─── --}}
                <div class="bm-form-row">
                    <label class="bm-label">Currency</label>
                    <input type="text" name="currency" id="bm-f-cur"
                           class="bm-input" placeholder="e.g. PHP, USD, EUR"
                           list="bm-currency-list" maxlength="10" autocomplete="off">
                    <datalist id="bm-currency-list">
                        <option value="PHP">PHP — Philippine Peso (₱)</option>
                        <option value="USD">USD — US Dollar ($)</option>
                        <option value="EUR">EUR — Euro (€)</option>
                        <option value="JPY">JPY — Japanese Yen (¥)</option>
                        <option value="GBP">GBP — British Pound (£)</option>
                        <option value="SGD">SGD — Singapore Dollar (S$)</option>
                        <option value="AUD">AUD — Australian Dollar (A$)</option>
                        <option value="HKD">HKD — Hong Kong Dollar (HK$)</option>
                        <option value="CNY">CNY — Chinese Yuan (¥)</option>
                        <option value="CAD">CAD — Canadian Dollar (C$)</option>
                    </datalist>
                </div>
            </div>

            <div class="bm-form-2col">
                <div class="bm-form-row">
                    <label class="bm-label">Cash Balance</label>
                    <input type="number" name="cash_balance" id="bm-f-bal"
                           class="bm-input" placeholder="0.00" step="0.01" min="0">
                </div>
                <div class="bm-form-row">
                    <label class="bm-label">Balance As Of</label>
                    <input type="date" name="balance_as_of" id="bm-f-date" class="bm-input">
                </div>
            </div>

            <div class="bm-form-row">
                <label class="bm-label">Icon Color</label>
                <div class="bm-color-row">
                    <input type="color" id="bm-f-color" value="#3b82f6"
                           class="bm-color-picker" oninput="bmSyncColor(this.value)">
                    <input type="text" name="icon_color" id="bm-f-color-hex"
                           class="bm-input" placeholder="#3b82f6"
                           oninput="bmSyncHex(this.value)">
                </div>
            </div>

            <div class="bm-modal-footer">
                <button type="button" onclick="bmCloseModal()" class="bm-btn bm-btn-gray">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="bm-btn bm-btn-blue">
                    <i class="fas fa-save"></i> Save Account
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- DELETE CONFIRM MODAL (IT/Admin only)                            --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
@if(auth()->user()->isAdminUser())
<div class="bm-overlay" id="bm-del-modal" onclick="bmBgClose(event,'bm-del-modal')">
    <div class="bm-modal" style="width:380px;border-color:#991b1b;">
        <form id="bm-del-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="bm-del-title">
                <i class="fas fa-exclamation-triangle"></i> Delete Bank Account
            </div>
            <div class="bm-del-body" id="bm-del-msg">
                Are you sure you want to delete this bank account? This action cannot be undone.
            </div>
            <div class="bm-del-footer">
                <button type="button" onclick="bmCloseDelModal()" class="bm-btn bm-btn-gray">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="bm-btn bm-btn-red">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>
@endif


<script>
/* ─── MODAL HELPERS ──────────────────────────────────────────── */
function bmOpenModal()  { document.getElementById('bm-modal').classList.add('open'); }
function bmCloseModal() { document.getElementById('bm-modal').classList.remove('open'); }

function bmBgClose(e, id) {
    if (e.target === document.getElementById(id)) {
        document.getElementById(id).classList.remove('open');
    }
}

/* ─── ADD MODAL ──────────────────────────────────────────────── */
function bmOpenAddModal() {
    document.getElementById('bm-modal-title').innerHTML =
        '<i class="fas fa-university" style="color:#3b82f6;font-size:.9rem;"></i> Add Bank Account';
    document.getElementById('bm-method').value  = 'POST';
    document.getElementById('bm-form').action   = '{{ route("treasury.banks.store") }}';
    document.getElementById('bm-id').value      = '';
    document.getElementById('bm-f-bank').value  = '';
    document.getElementById('bm-f-short').value = '';
    document.getElementById('bm-f-acct').value  = '';
    document.getElementById('bm-f-type').value  = '';
    document.getElementById('bm-f-cur').value   = '{{ $cur ?? "PHP" }}';
    document.getElementById('bm-f-bal').value   = '';
    document.getElementById('bm-f-date').value  = new Date().toISOString().split('T')[0];
    document.getElementById('bm-f-color').value     = '#3b82f6';
    document.getElementById('bm-f-color-hex').value = '#3b82f6';
    bmOpenModal();
}

/* ─── EDIT MODAL ─────────────────────────────────────────────── */
function bmOpenEditModal(id, bank, shortName, acct, type, cur, bal, date, color) {
    document.getElementById('bm-modal-title').innerHTML =
        '<i class="fas fa-pencil-alt" style="color:#f59e0b;font-size:.9rem;"></i> Edit Bank Account';
    document.getElementById('bm-method').value  = 'PUT';
    document.getElementById('bm-form').action   = '/treasury/banks/' + id;
    document.getElementById('bm-id').value      = id;
    document.getElementById('bm-f-bank').value  = bank;
    document.getElementById('bm-f-short').value = shortName;
    document.getElementById('bm-f-acct').value  = acct;
    document.getElementById('bm-f-type').value  = type;
    document.getElementById('bm-f-cur').value   = cur;
    document.getElementById('bm-f-bal').value   = bal;
    document.getElementById('bm-f-date').value  = date;
    document.getElementById('bm-f-color').value     = color || '#3b82f6';
    document.getElementById('bm-f-color-hex').value = color || '#3b82f6';
    bmOpenModal();
}

/* ─── DELETE MODAL ───────────────────────────────────────────── */
function bmOpenDelModal(id, label) {
    document.getElementById('bm-del-form').action = '/treasury/banks/' + id;
    document.getElementById('bm-del-msg').textContent =
        'Delete "' + label + '"? This action cannot be undone.';
    document.getElementById('bm-del-modal').classList.add('open');
}
function bmCloseDelModal() {
    document.getElementById('bm-del-modal').classList.remove('open');
}

/* ─── COLOR SYNC ─────────────────────────────────────────────── */
function bmSyncColor(hex) {
    document.getElementById('bm-f-color-hex').value = hex;
}
function bmSyncHex(val) {
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        document.getElementById('bm-f-color').value = val;
    }
}

/* ─── CURRENCY SYMBOL HELPER ─────────────────────────────────── */
const bmCurrencySymbols = {
    PHP: '₱', USD: '$', EUR: '€', JPY: '¥', GBP: '£',
    SGD: 'S$', AUD: 'A$', HKD: 'HK$', CNY: '¥', CAD: 'C$',
};
function bmCurrencySymbol(code) {
    return bmCurrencySymbols[(code || '').toUpperCase()] ?? (code || '?');
}

/* ─── ESC KEY ────────────────────────────────────────────────── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        bmCloseModal();
        if (typeof bmCloseDelModal === 'function') bmCloseDelModal();
    }
});
</script>
@endsection