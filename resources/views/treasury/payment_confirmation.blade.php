@extends('layouts.app')

@section('title', 'Cash Receipts')

@section('content')
<style>
.b { background:#162030; border:1px solid #2a3f55; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.3); }
.stat-card { background:#162030; border:1px solid #2a3f55; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#4d6880; }
.stat-val { font-size:1.5rem; font-weight:800; color:#e2eaf4; line-height:1.1; margin:.1rem 0; }

.pay-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.pay-table thead th { background:#1e3a5f; color:#fff; padding:.5rem .75rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.pay-table thead th.r { text-align:right; }
.pay-table tbody tr { border-bottom:1px solid #1e2d3d; transition:background .1s; }
.pay-table tbody tr:hover { background:#1e2d3d; }
.pay-table tbody td { padding:.5rem .75rem; color:#c0cfe0; vertical-align:middle; }
.pay-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }

.search-input { padding:.38rem .65rem; border:1px solid #3a5570; border-radius:.375rem; font-size:.83rem; color:#e2eaf4; background:#1e2d3d; }
.search-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.search-input option { background:#1e2d3d; }

.btn-confirm { padding:.25rem .6rem; border-radius:.3rem; font-size:.73rem; font-weight:600; background:#dcfce7; color:#15803d; border:1px solid #86efac; cursor:pointer; transition:all .12s; }
.btn-confirm:hover { background:#bbf7d0; }
.btn-bulk { padding:.4rem .8rem; border-radius:.35rem; font-size:.78rem; font-weight:600; background:#2563eb; color:#fff; border:none; cursor:pointer; transition:all .12s; }
.btn-bulk:hover { background:#1d4ed8; }
.btn-bulk:disabled { background:#9ca3af; cursor:not-allowed; }

.badge-posted { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; background:#fef3c7; color:#92400e; }
.badge-check { display:inline-block; padding:.15rem .5rem; border-radius:.25rem; font-size:.68rem; font-weight:600; background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
.badge-transfer { display:inline-block; padding:.15rem .5rem; border-radius:.25rem; font-size:.68rem; font-weight:600; background:#dcfce7; color:#15803d; border:1px solid #86efac; }
.badge-cash { display:inline-block; padding:.15rem .5rem; border-radius:.25rem; font-size:.68rem; font-weight:600; background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-white">Cash Receipts</h2>
        <p class="text-xs text-gray-300 mt-0.5">Treasury — Review and confirm clearing payments</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('treasury.bounced-history') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm bg-red-800 text-white rounded-md hover:bg-red-900 font-semibold shadow-sm">
            <i class="fas fa-ban"></i> Bounced History
        </a>
        <a href="{{ route('treasury.summary') }}"
           class="flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold shadow-sm">
            <i class="fas fa-chart-bar"></i> Bank
        </a>
    </div>
</div>

@if(session('success'))
<div class="flex items-center gap-2 bg-green-900 border border-green-700 text-green-300 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-check-circle text-green-400"></i> {{ session('success') }}
</div>
@endif

<!-- STAT CARDS -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-5">
    <div class="stat-card">
        <div class="stat-lbl">Pending Confirmation</div>
        <div class="stat-val text-amber-600">{{ $stats['pending_count'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Pending Total</div>
        <div class="stat-val">PHP {{ number_format($stats['pending_total'], 2) }}</div>
    </div>
    <div class="stat-card" style="border-color:#16a34a;background:linear-gradient(135deg,#14532d22,#162030);">
        <div class="stat-lbl" style="color:#4ade80;">Confirmed Today</div>
        <div class="stat-val" style="color:#86efac;">{{ $stats['confirmed_today'] }}</div>
    </div>
</div>

<!-- TABLE CARD -->
<div class="b">
    <!-- Toolbar -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700 gap-3 flex-wrap">
        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" class="search-input" style="width:220px;" placeholder="Search customer, CR#, invoice...">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="search-input" style="width:140px;">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="search-input" style="width:140px;">
            <button type="submit" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-xs font-semibold text-gray-200 hover:bg-gray-600">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['search','date_from','date_to']))
            <a href="{{ route('treasury.confirmation') }}" class="px-3 py-1.5 text-xs text-gray-300 hover:text-gray-200">Clear</a>
            @endif
        </form>
        <div class="flex items-center gap-2">
            <button type="button" class="btn-bulk" id="bulk-confirm-btn" disabled onclick="bulkConfirm()">
                <i class="fas fa-check-double mr-1"></i> Confirm Selected (<span id="selected-count">0</span>)
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="pay-table">
            <thead>
                <tr>
                    <th style="width:35px;">
                        <input type="checkbox" id="select-all" onchange="toggleAll(this)">
                    </th>
                    <th>CR Number</th>
                    <th>Customer</th>
                    <th>Invoice #</th>
                    <th>DR #</th>
                    <th>Payment Date</th>
                    <th>Payment Means</th>
                    <th>G/L Account</th>
                    <th class="r">Amount</th>
                    <th class="r">Net</th>
                    <th class="r">Overpayment</th>
                    <th style="width:65px;">Status</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                @php
                    $method = $payment->payment_method ?? $payment->payment_option ?? null;
                    $methodLabels = ['check' => 'Check', 'bank_transfer' => 'Bank Transfer', 'cash' => 'Cash'];
                    $methodLabel = $methodLabels[$method] ?? ($method ? ucfirst($method) : '—');
                    $badgeClass = match($method) {
                        'check' => 'badge-check',
                        'bank_transfer' => 'badge-transfer',
                        'cash' => 'badge-cash',
                        default => '',
                    };
                    $meansData = json_decode($payment->payment_means_data ?? '{}', true);
                    $glAccount = $meansData['gl_account_name'] ?? $meansData['gl_account'] ?? null;
                    $glAccountId = $meansData['gl_account_id'] ?? null;
                @endphp
                <tr id="row-{{ $payment->id }}">
                    <td><input type="checkbox" class="row-check" value="{{ $payment->id }}" onchange="updateCount()"></td>
                    <td class="font-semibold text-blue-400">{{ $payment->collection_receipt_number ?: '—' }}</td>
                    <td>{{ $payment->customer_name ?: '—' }}</td>
                    <td class="text-gray-300 text-xs">{{ $payment->invoice_no ?: '—' }}</td>
                    <td class="text-gray-300 text-xs">{{ $payment->dr_no ?: '—' }}</td>
                    <td class="text-gray-300 text-xs">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                    <td>
                        @if($badgeClass)
                            <span class="{{ $badgeClass }}">{{ $methodLabel }}</span>
                        @else
                            <span class="text-gray-400 text-xs">{{ $methodLabel }}</span>
                        @endif
                    </td>
                    <td class="text-xs">
                        @if($glAccount)
                            <span class="font-semibold text-gray-200">{{ $glAccount }}</span>
                        @else
                            <div>
                                <select class="bank-select text-xs border border-amber-600 bg-amber-950 text-amber-200 rounded px-1.5 py-1" data-payment-id="{{ $payment->id }}" style="max-width:180px;">
                                    <option value="">— Select Bank —</option>
                                    @foreach($bankAccounts as $ba)
                                        <option value="{{ $ba->id }}">{{ $ba->short_name ?: $ba->bank_name }} ({{ $ba->account_number }})</option>
                                    @endforeach
                                </select>
                                <div class="text-amber-500 mt-0.5" style="font-size:.6rem;">⚠ Old entry — no G/L saved</div>
                            </div>
                        @endif
                    </td>
                    <td class="r font-semibold">PHP {{ number_format($payment->gross_amount ?? $payment->amount, 2) }}</td>
                    <td class="r font-semibold text-white">PHP {{ number_format($payment->net ?? ($payment->gross_amount ?? $payment->amount), 2) }}</td>
                    <td class="r">
                        @if($payment->overpayment > 0)
                            <span class="text-amber-600 font-semibold">PHP {{ number_format($payment->overpayment, 2) }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge-posted">For Clearing</span></td>
                    <td style="white-space:nowrap;display:flex;gap:4px;flex-wrap:wrap;">
                        <button class="btn-confirm" onclick="openConfirmModal({{ $payment->id }})">
                            <i class="fas fa-check mr-0.5"></i> Confirm
                        </button>
                        <button style="padding:.25rem .6rem;border-radius:.3rem;font-size:.73rem;font-weight:600;background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;cursor:pointer;" onclick="openBounceModal({{ $payment->id }})">
                            <i class="fas fa-ban mr-0.5"></i> Bounce
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-center py-10 text-gray-400">No posted payments awaiting confirmation.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-4 py-3 border-t border-gray-700">
        {{ $payments->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- CONFIRM MODAL (with clearing date) -->
<div id="confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.55);">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-sm mx-4 p-5">
        <h3 class="font-bold text-white mb-4"><i class="fas fa-check-circle text-green-400 mr-1"></i> Confirm Payment</h3>
        <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Clearing Date <span class="text-red-400">*</span></label>
            <input type="date" id="clearing-date-input" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:border-blue-500 focus:outline-none">
            <p class="text-xs text-gray-400 mt-1">Actual date the payment entered the bank account.</p>
        </div>
        <div id="confirm-bank-select-wrap" class="mb-3 hidden">
            <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Bank Account <span class="text-red-400">*</span></label>
            <select id="confirm-bank-select" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm">
                <option value="">— Select Bank —</option>
                @foreach($bankAccounts as $ba)
                    <option value="{{ $ba->id }}">{{ $ba->short_name ?: $ba->bank_name }} ({{ $ba->account_number }})</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 justify-end">
            <button onclick="closeConfirmModal()" class="px-4 py-2 text-sm bg-gray-700 text-gray-200 rounded font-semibold hover:bg-gray-600">Cancel</button>
            <button onclick="submitConfirm()" class="px-4 py-2 text-sm bg-green-700 text-white rounded font-semibold hover:bg-green-800">
                <i class="fas fa-check mr-1"></i> Confirm
            </button>
        </div>
    </div>
</div>

<!-- BOUNCE MODAL -->
<div id="bounce-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.55);">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-sm mx-4 p-5">
        <h3 class="font-bold text-red-400 mb-4"><i class="fas fa-ban mr-1"></i> Bounce / Reject Check</h3>
        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Reason <span class="text-red-400">*</span></label>
            <textarea id="bounce-reason-input" rows="3" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:border-red-500 focus:outline-none" placeholder="e.g. Insufficient funds, Account closed..."></textarea>
        </div>
        <div class="flex gap-2 justify-end">
            <button onclick="closeBounceModal()" class="px-4 py-2 text-sm bg-gray-700 text-gray-200 rounded font-semibold hover:bg-gray-600">Cancel</button>
            <button onclick="submitBounce()" class="px-4 py-2 text-sm bg-red-700 text-white rounded font-semibold hover:bg-red-800">
                <i class="fas fa-ban mr-1"></i> Mark as Bounced
            </button>
        </div>
    </div>
</div>

<script>
let _confirmId = null, _bounceId = null;

function openConfirmModal(id) {
    _confirmId = id;
    // Show bank select if payment has no GL linked
    const row = document.getElementById('row-' + id);
    const hasBank = row && row.querySelector('.bank-select') === null;
    document.getElementById('confirm-bank-select-wrap').classList.toggle('hidden', hasBank);
    document.getElementById('clearing-date-input').value = new Date().toISOString().split('T')[0];
    document.getElementById('confirm-modal').classList.remove('hidden');
}
function closeConfirmModal() { document.getElementById('confirm-modal').classList.add('hidden'); }

function openBounceModal(id) {
    _bounceId = id;
    document.getElementById('bounce-reason-input').value = '';
    document.getElementById('bounce-modal').classList.remove('hidden');
}
function closeBounceModal() { document.getElementById('bounce-modal').classList.add('hidden'); }

function submitConfirm() {
    const clearingDate = document.getElementById('clearing-date-input').value;
    if (!clearingDate) { Swal.fire({ icon:'warning', title:'Required', text:'Please enter a clearing date.' }); return; }

    const bankSelect = document.querySelector(`#row-${_confirmId} .bank-select`);
    const bankAccountId = bankSelect ? bankSelect.value : document.getElementById('confirm-bank-select').value || null;

    if (bankSelect && !bankAccountId) { Swal.fire({ icon:'warning', title:'Select Bank', text:'Please select a bank account.' }); return; }

    closeConfirmModal();
    const body = { clearing_date: clearingDate };
    if (bankAccountId) body.manual_bank_account_id = bankAccountId;

    fetch(`/treasury/confirm/${_confirmId}`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify(body),
    }).then(r=>r.json()).then(data => {
        if (data.success) {
            Swal.fire({ icon:'success', title:'Confirmed!', text:data.message, timer:1500, showConfirmButton:false });
            document.getElementById('row-'+_confirmId)?.remove();
        } else {
            Swal.fire({ icon:'error', title:'Error', text:data.message });
        }
    });
}

function submitBounce() {
    const reason = document.getElementById('bounce-reason-input').value.trim();
    if (!reason) { Swal.fire({ icon:'warning', title:'Required', text:'Please enter a reason.' }); return; }
    closeBounceModal();
    fetch(`/treasury/bounce/${_bounceId}`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify({ bounce_reason: reason }),
    }).then(r=>r.json()).then(data => {
        if (data.success) {
            Swal.fire({ icon:'info', title:'Bounced', text:data.message, timer:1500, showConfirmButton:false });
            document.getElementById('row-'+_bounceId)?.remove();
        } else {
            Swal.fire({ icon:'error', title:'Error', text:data.message });
        }
    });
}

function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => { cb.checked = master.checked; });
    updateCount();
}

function updateCount() {
    const checked = document.querySelectorAll('.row-check:checked');
    document.getElementById('selected-count').textContent = checked.length;
    document.getElementById('bulk-confirm-btn').disabled = checked.length === 0;
}


function bulkConfirm() {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(cb => parseInt(cb.value));
    if (!ids.length) return;

    Swal.fire({
        title: `Confirm ${ids.length} payment(s)?`,
        text: 'All selected payments will be marked as confirmed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#15803d',
        confirmButtonText: 'Confirm All',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('/treasury/bulk-confirm', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ ids }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon:'success', title:'Done!', text: data.message, timer:1500, showConfirmButton:false });
                setTimeout(() => location.reload(), 1600);
            } else {
                Swal.fire({ icon:'error', title:'Error', text: data.message });
            }
        });
    });
}
</script>
@endsection
