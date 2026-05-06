@extends('layouts.app')

@section('title', $account->short_name ?: $account->bank_name)

@section('content')
<style>
.b { background:#162030; border:1px solid #2a3f55; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.3); }
.stat-card { background:#162030; border:1px solid #2a3f55; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#4d6880; }
.stat-val { font-size:1.5rem; font-weight:800; color:#e2eaf4; line-height:1.1; margin:.1rem 0; }

.txn-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.txn-table thead th { background:#1e3a5f; color:#fff; padding:.45rem .6rem; font-size:.68rem; font-weight:600; text-align:left; white-space:nowrap; }
.txn-table thead th.r { text-align:right; }
.txn-table tbody tr { border-bottom:1px solid #1e2d3d; transition:background .1s; }
.txn-table tbody tr:hover { background:#1e2d3d; }
.txn-table tbody td { padding:.45rem .6rem; color:#c0cfe0; vertical-align:middle; white-space:nowrap; }
.txn-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }

.search-input { padding:.38rem .65rem; border:1px solid #3a5570; border-radius:.375rem; font-size:.83rem; color:#e2eaf4; background:#1e2d3d; }
.search-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.search-input option { background:#1e2d3d; }

.badge-type { display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.65rem; font-weight:700; }
.badge-deposit    { background:#dcfce7; color:#15803d; }
.badge-withdrawal { background:#fee2e2; color:#991b1b; }
.badge-transfer   { background:#dbeafe; color:#1d4ed8; }
.badge-fee        { background:#fef3c7; color:#92400e; }
.badge-interest   { background:#f0fdf4; color:#166534; }
.badge-adjustment { background:#f3f4f6; color:#374151; }

.tab-btn { padding:.45rem 1.1rem; font-size:.82rem; font-weight:600; border-radius:.375rem; cursor:pointer; transition:background .15s,color .15s; }
.tab-btn.active-in  { background:#16a34a; color:#fff; }
.tab-btn.active-out { background:#dc2626; color:#fff; }
.tab-btn.inactive   { background:#374151; color:#d1d5db; }
.tab-btn.inactive:hover { background:#4b5563; color:#fff; }

.modal-input { padding:.38rem .6rem; border:1px solid #4b5563; border-radius:.375rem; font-size:.82rem; color:#fff; background:#374151; width:100%; }
.modal-input:focus { outline:none; border-color:#3b82f6; }
.modal-label { display:block; font-size:.72rem; font-weight:600; color:#9ca3af; margin-bottom:.25rem; text-transform:uppercase; letter-spacing:.03em; }
</style>

@php
    $sym = $account->currency === 'PHP' ? '₱' : '$';
    $colors = [
        'BDO'=>'#0052a0','UNIONBANK'=>'#f97316','SECURITY BANK'=>'#7c3aed','PBB'=>'#dc2626',
        'AUB'=>'#059669','METROBANK'=>'#7c3aed','PBCOM'=>'#0e7490','BPI'=>'#dc2626',
        'BOC'=>'#b45309','CHINABANK'=>'#be123c','RCBC'=>'#1d4ed8','MAYBANK'=>'#f59e0b',
    ];
    $color = $colors[$account->bank_name] ?? '#6b7280';
    $activeTab = request('tab', 'in');
@endphp

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <div style="width:48px;height:48px;border-radius:.5rem;background:{{ $color }};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.8rem;">
            {{ strtoupper(substr($account->bank_name, 0, 3)) }}
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">{{ $account->short_name ?: $account->bank_name }}</h2>
            <p class="text-xs text-gray-300 mt-0.5 font-mono">{{ $account->account_number }} · {{ $account->bank_name }} · {{ $account->account_type ?: 'N/A' }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('treasury.banks', $account->currency === 'PHP' ? 'peso' : 'dollar') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm bg-gray-700 text-gray-200 rounded-md hover:bg-gray-600 font-semibold border border-gray-600">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <button onclick="openAddModal('in')"
                class="flex items-center gap-1.5 px-3 py-2 text-sm bg-green-700 text-white rounded-md hover:bg-green-800 font-semibold shadow-sm">
            <i class="fas fa-arrow-down"></i> Incoming
        </button>
        <button onclick="openAddModal('out')"
                class="flex items-center gap-1.5 px-3 py-2 text-sm bg-red-700 text-white rounded-md hover:bg-red-800 font-semibold shadow-sm">
            <i class="fas fa-arrow-up"></i> Outgoing
        </button>
    </div>
</div>

@if(session('success'))
<div class="flex items-center gap-2 bg-green-900 border border-green-700 text-green-300 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-check-circle text-green-400"></i> {{ session('success') }}
</div>
@endif

<!-- ACCOUNT STATS -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
    <div class="stat-card md:col-span-1" style="border-color:{{ $color }};background:linear-gradient(135deg,#1a2a3a,#162030);">
        <div class="stat-lbl" style="color:{{ $color }};">Current Balance</div>
        <div class="stat-val" style="font-size:1.3rem;color:#e2eaf4;">{{ $sym }} {{ number_format($account->display_balance ?? $account->cash_balance, 2) }}</div>
        @if($account->balance_as_of)
        <div class="text-xs text-gray-400 mt-0.5">as of {{ $account->balance_as_of->format('M d, Y') }}</div>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Total Incoming</div>
        <div class="stat-val text-green-700" style="font-size:1.15rem;">{{ $sym }} {{ number_format($stats['total_deposits'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Total Outgoing</div>
        <div class="stat-val text-red-700" style="font-size:1.15rem;">{{ $sym }} {{ number_format($stats['total_withdrawals'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Transactions</div>
        <div class="stat-val">{{ $stats['txn_count'] }}</div>
    </div>
</div>

@if($account->glAccount)
<div class="b mb-5 px-4 py-3">
    <div class="flex items-center gap-3">
        <i class="fas fa-link text-blue-400"></i>
        <div>
            <span class="text-xs font-bold text-gray-400 uppercase">Linked G/L Account</span>
            <div class="text-sm font-semibold text-gray-100">{{ $account->glAccount->account_code }} — {{ $account->glAccount->account_name }}</div>
        </div>
        <a href="{{ route('gl_accounts.show', $account->glAccount->id) }}" target="_blank" class="ml-auto text-xs text-blue-400 hover:underline font-semibold">
            <i class="fas fa-external-link-alt mr-0.5"></i> View GL Account
        </a>
    </div>
</div>
@endif

<!-- TABS + FILTER -->
<div class="b">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700 gap-3 flex-wrap">
        <!-- Tab switcher -->
        <div class="flex gap-2">
            <a href="{{ route('treasury.bank.show', $account->id) }}?tab=in&{{ http_build_query(request()->except(['tab'])) }}"
               class="tab-btn {{ $activeTab === 'in' ? 'active-in' : 'inactive' }}">
                <i class="fas fa-arrow-down mr-1"></i> Incoming
            </a>
            <a href="{{ route('treasury.bank.show', $account->id) }}?tab=out&{{ http_build_query(request()->except(['tab'])) }}"
               class="tab-btn {{ $activeTab === 'out' ? 'active-out' : 'inactive' }}">
                <i class="fas fa-arrow-up mr-1"></i> Outgoing
            </a>
        </div>

        <!-- Date + search filters -->
        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="search-input" style="width:130px;">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="search-input" style="width:130px;">
            <button type="submit" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-xs font-semibold text-gray-200 hover:bg-gray-600">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['date_from','date_to']))
            <a href="{{ route('treasury.bank.show', $account->id) }}?tab={{ $activeTab }}" class="px-2 py-1.5 text-xs text-gray-300 hover:text-gray-100">Clear</a>
            @endif
        </form>

        <span class="text-xs text-gray-400">{{ $transactions->total() }} record{{ $transactions->total() != 1 ? 's' : '' }}</span>
    </div>

    <div class="overflow-x-auto">
    @if($activeTab === 'in')
    <!-- INCOMING TABLE -->
    <table class="txn-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Bank</th>
                <th>Code</th>
                <th>Mos</th>
                <th>Week</th>
                <th>Customer Name</th>
                <th>Description</th>
                <th>Type</th>
                <th>Check No.</th>
                <th>Curr.</th>
                <th class="r">Amount</th>
                <th class="r">Rate</th>
                <th class="r">Amount PHP</th>
                <th>Dr. Account</th>
                <th>Cr. Account</th>
                <th class="r">Running Balance</th>
                <th>Logged By</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($transactions as $txn)
            @php $mos = $txn->txn_date->format('M'); $wk = $txn->txn_date->weekOfYear; @endphp
            <tr id="txn-row-{{ $txn->id }}">
                <td class="text-xs font-mono">{{ $txn->txn_date->format('m/d/Y') }}</td>
                <td class="text-xs font-semibold">{{ $account->bank_name }}</td>
                <td class="text-xs font-mono text-gray-400">{{ $account->account_number }}</td>
                <td class="text-xs">{{ $mos }}</td>
                <td class="text-xs text-center">{{ $wk }}</td>
                <td class="font-medium">{{ $txn->payee_or_source ?: '—' }}</td>
                <td class="text-xs text-gray-400">{{ $txn->description ?: '—' }}</td>
                <td><span class="badge-type badge-{{ strtolower($txn->type) }}">{{ $txn->type }}</span></td>
                <td class="font-mono text-xs">{{ $txn->check_number ?: '—' }}</td>
                <td class="text-xs">{{ $txn->currency ?: 'PHP' }}</td>
                <td class="r font-semibold text-green-700">{{ $sym }} {{ number_format($txn->debit, 2) }}</td>
                <td class="r text-xs">{{ number_format($txn->exchange_rate ?? 1, 4) }}</td>
                <td class="r font-semibold">{{ $sym }} {{ number_format($txn->amount_php ?? $txn->debit, 2) }}</td>
                <td class="text-xs text-gray-400">{{ $txn->dr_account ?: '—' }}</td>
                <td class="text-xs text-gray-400">{{ $txn->cr_account ?: '—' }}</td>
                <td class="r font-bold">{{ $sym }} {{ number_format($txn->running_balance, 2) }}</td>
                <td class="text-xs text-gray-400">{{ $txn->logged_by ?: '—' }}</td>
                <td><button onclick="deleteTxn({{ $txn->id }})" style="padding:.2rem .45rem;border-radius:.3rem;font-size:.65rem;background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;cursor:pointer;" title="Delete"><i class="fas fa-trash"></i></button></td>
            </tr>
        @empty
            <tr><td colspan="17" class="text-center py-10 text-gray-400">
                <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                No incoming transactions found.
            </td></tr>
        @endforelse
        </tbody>
    </table>
    @else
    <!-- OUTGOING TABLE -->
    <table class="txn-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Bank</th>
                <th>Code</th>
                <th>Mos</th>
                <th>Week</th>
                <th>Payee</th>
                <th>Description</th>
                <th>Type</th>
                <th>Check No.</th>
                <th>Curr.</th>
                <th class="r">Withdrawal Amount</th>
                <th class="r">Rate</th>
                <th class="r">Amount PHP</th>
                <th class="r">Running Balance</th>
                <th>Logged By</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($transactions as $txn)
            @php $mos = $txn->txn_date->format('M'); $wk = $txn->txn_date->weekOfYear; @endphp
            <tr id="txn-row-{{ $txn->id }}">
                <td class="text-xs font-mono">{{ $txn->txn_date->format('m/d/Y') }}</td>
                <td class="text-xs font-semibold">{{ $account->bank_name }}</td>
                <td class="text-xs font-mono text-gray-400">{{ $account->account_number }}</td>
                <td class="text-xs">{{ $mos }}</td>
                <td class="text-xs text-center">{{ $wk }}</td>
                <td class="font-medium">{{ $txn->payee_or_source ?: '—' }}</td>
                <td class="text-xs text-gray-400">{{ $txn->description ?: '—' }}</td>
                <td><span class="badge-type badge-{{ strtolower($txn->type) }}">{{ $txn->type }}</span></td>
                <td class="font-mono text-xs">{{ $txn->check_number ?: '—' }}</td>
                <td class="text-xs">{{ $txn->currency ?: 'PHP' }}</td>
                <td class="r font-semibold text-red-600">{{ $sym }} {{ number_format($txn->credit, 2) }}</td>
                <td class="r text-xs">{{ number_format($txn->exchange_rate ?? 1, 4) }}</td>
                <td class="r font-semibold">{{ $sym }} {{ number_format($txn->amount_php ?? $txn->credit, 2) }}</td>
                <td class="r font-bold">{{ $sym }} {{ number_format($txn->running_balance, 2) }}</td>
                <td class="text-xs text-gray-400">{{ $txn->logged_by ?: '—' }}</td>
                <td><button onclick="deleteTxn({{ $txn->id }})" style="padding:.2rem .45rem;border-radius:.3rem;font-size:.65rem;background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;cursor:pointer;" title="Delete"><i class="fas fa-trash"></i></button></td>
            </tr>
        @empty
            <tr><td colspan="15" class="text-center py-10 text-gray-400">
                <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                No outgoing transactions found.
            </td></tr>
        @endforelse
        </tbody>
    </table>
    @endif
    </div>

    @if($transactions->hasPages())
    <div class="px-4 py-3 border-t border-gray-700">
        {{ $transactions->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- ADD TRANSACTION MODAL -->
<div id="add-txn-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.55);">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
        <!-- Modal header with tabs -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-700">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-white text-sm">Add Transaction — {{ $account->short_name }}</h3>
                <div class="flex gap-1 ml-2">
                    <button id="modal-tab-in"  onclick="switchModalTab('in')"
                            class="px-3 py-1 rounded text-xs font-bold transition-colors">
                        <i class="fas fa-arrow-down mr-1"></i> Incoming
                    </button>
                    <button id="modal-tab-out" onclick="switchModalTab('out')"
                            class="px-3 py-1 rounded text-xs font-bold transition-colors">
                        <i class="fas fa-arrow-up mr-1"></i> Outgoing
                    </button>
                </div>
            </div>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-300 ml-4">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('treasury.bank.addTransaction', $account->id) }}" class="p-5">
            @csrf
            <input type="hidden" name="type" id="modal-type-hidden" value="Deposit">

            <!-- Row 1: Date + Currency + Rate -->
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="modal-label">Date *</label>
                    <input type="date" name="txn_date" value="{{ date('Y-m-d') }}" required class="modal-input">
                </div>
                <div>
                    <label class="modal-label">Currency</label>
                    <select name="currency" id="modal-currency" onchange="updateRate()" class="modal-input">
                        <option value="PHP">PHP — Peso</option>
                        <option value="USD">USD — Dollar</option>
                    </select>
                </div>
                <div>
                    <label class="modal-label">Exchange Rate</label>
                    <input type="number" name="exchange_rate" id="modal-rate" value="1" step="0.0001" min="0" class="modal-input" placeholder="1.0000">
                </div>
            </div>

            <!-- Row 2: Name + Check No. + Amount -->
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="col-span-1">
                    <label class="modal-label" id="label-payee">Customer Name *</label>
                    <input type="text" name="payee_or_source" required class="modal-input" placeholder="Customer or payee name">
                </div>
                <div>
                    <label class="modal-label">Check No.</label>
                    <input type="text" name="check_number" class="modal-input" placeholder="Check number...">
                </div>
                <div>
                    <label class="modal-label">Amount *</label>
                    <input type="number" name="amount" id="modal-amount" step="0.01" min="0.01" required class="modal-input" placeholder="0.00" oninput="calcAmountPhp()">
                </div>
            </div>

            <!-- Row 3: Type + Amount PHP (auto) -->
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="modal-label" id="label-type">Transaction Type</label>
                    <select name="type" id="modal-type-select" class="modal-input" onchange="document.getElementById('modal-type-hidden').value=this.value">
                        <option value="Deposit">Deposit</option>
                        <option value="Interest">Interest</option>
                        <option value="Adjustment">Adjustment</option>
                    </select>
                </div>
                <div>
                    <label class="modal-label">Amount PHP (auto)</label>
                    <input type="text" id="modal-amount-php-display" class="modal-input" readonly placeholder="Auto-calculated" style="background:#1f2937;color:#6ee7b7;">
                </div>
            </div>

            <!-- Row 4: Description -->
            <div class="mb-3">
                <label class="modal-label">Description</label>
                <input type="text" name="description" class="modal-input" placeholder="Brief description...">
            </div>

            <!-- Row 5: Dr. Account + Cr. Account (Incoming only) -->
            <div id="drcr-row" class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="modal-label">Dr. Account</label>
                    <input type="text" name="dr_account" class="modal-input" placeholder="Debit account...">
                </div>
                <div>
                    <label class="modal-label">Cr. Account</label>
                    <input type="text" name="cr_account" class="modal-input" placeholder="Credit account...">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 text-sm bg-gray-700 text-gray-200 rounded-md border border-gray-600 font-semibold hover:bg-gray-600">Cancel</button>
                <button type="submit" id="modal-submit-btn"
                        class="px-5 py-2 text-sm text-white rounded-md font-semibold">
                    <i class="fas fa-save mr-1"></i> <span id="modal-submit-label">Save Incoming</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal(dir) {
    switchModalTab(dir);
    document.getElementById('add-txn-modal').classList.remove('hidden');
}
function closeAddModal() {
    document.getElementById('add-txn-modal').classList.add('hidden');
}

function switchModalTab(dir) {
    const isIn = dir === 'in';
    const tabIn  = document.getElementById('modal-tab-in');
    const tabOut = document.getElementById('modal-tab-out');
    const typeSelect = document.getElementById('modal-type-select');
    const typeHidden = document.getElementById('modal-type-hidden');
    const submitBtn  = document.getElementById('modal-submit-btn');
    const submitLbl  = document.getElementById('modal-submit-label');
    const drcrRow    = document.getElementById('drcr-row');
    const labelPayee = document.getElementById('label-payee');

    if (isIn) {
        tabIn.className  = 'px-3 py-1 rounded text-xs font-bold bg-green-600 text-white';
        tabOut.className = 'px-3 py-1 rounded text-xs font-bold bg-gray-700 text-gray-300';
        submitBtn.className = 'px-5 py-2 text-sm text-white rounded-md font-semibold bg-green-700 hover:bg-green-800';
        submitLbl.textContent = 'Save Incoming';
        labelPayee.textContent = 'Customer Name *';
        drcrRow.style.display = 'grid';
        typeSelect.innerHTML = `
            <option value="Deposit">Deposit</option>
            <option value="Interest">Interest</option>
            <option value="Adjustment">Adjustment</option>
        `;
        typeSelect.value = 'Deposit';
    } else {
        tabIn.className  = 'px-3 py-1 rounded text-xs font-bold bg-gray-700 text-gray-300';
        tabOut.className = 'px-3 py-1 rounded text-xs font-bold bg-red-600 text-white';
        submitBtn.className = 'px-5 py-2 text-sm text-white rounded-md font-semibold bg-red-700 hover:bg-red-800';
        submitLbl.textContent = 'Save Outgoing';
        labelPayee.textContent = 'Payee *';
        drcrRow.style.display = 'none';
        typeSelect.innerHTML = `
            <option value="Withdrawal">Withdrawal</option>
            <option value="Transfer">Transfer</option>
            <option value="Fee">Fee</option>
        `;
        typeSelect.value = 'Withdrawal';
    }
    typeHidden.value = typeSelect.value;
}

function calcAmountPhp() {
    const amount = parseFloat(document.getElementById('modal-amount').value) || 0;
    const rate   = parseFloat(document.getElementById('modal-rate').value) || 1;
    const cur    = document.getElementById('modal-currency').value;
    const php    = cur === 'PHP' ? amount : amount * rate;
    document.getElementById('modal-amount-php-display').value = php > 0 ? php.toFixed(2) : '';
}

function updateRate() {
    calcAmountPhp();
}

// Default state on load
switchModalTab('in');

function deleteTxn(id) {
    if (!confirm('Delete this transaction? The balance will be reversed.')) return;
    fetch(`/treasury/bank/transaction/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('txn-row-' + id)?.remove();
            setTimeout(() => location.reload(), 500);
        } else {
            alert(d.message || 'Delete failed.');
        }
    });
}
</script>
@endsection
