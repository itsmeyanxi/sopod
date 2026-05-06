@extends('layouts.app')

@section('title', 'Bank')

@section('content')
<style>
.b { background:#1f2937; border:1px solid #374151; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.stat-card { background:#1f2937; border:1px solid #374151; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; }
.stat-val { font-size:1.5rem; font-weight:800; color:#f9fafb; line-height:1.1; margin:.1rem 0; }

.ts-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.ts-table thead th { background:#111827; color:#fff; padding:.5rem .75rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.ts-table thead th.r { text-align:right; }
.ts-table tbody tr { border-bottom:1px solid #374151; transition:background .1s; }
.ts-table tbody tr:hover { background:#374151; }
.ts-table tbody td { padding:.5rem .75rem; color:#d1d5db; vertical-align:middle; }
.ts-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }
.ts-table tfoot td { padding:.6rem .75rem; font-weight:700; background:#111827; border-top:2px solid #374151; color:#f9fafb; }

.search-input { padding:.38rem .65rem; border:1px solid #4b5563; border-radius:.375rem; font-size:.83rem; color:#f9fafb; background:#374151; }
.search-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.2); }
.search-input::placeholder { color:#6b7280; }

.badge-confirmed { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; background:#14532d; color:#86efac; border:1px solid #16a34a; }
.credit-row { background:linear-gradient(135deg,#2a1f00,#1f2937) !important; }
.credit-row td { color:#fbbf24 !important; font-weight:600; }

.currency-card {
    display:flex; align-items:center; justify-content:space-between;
    background:#1f2937; border:2px solid #374151; border-radius:.6rem;
    padding:1rem 1.5rem; cursor:pointer; transition:all .18s;
    text-decoration:none;
}
.currency-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.3); }
.currency-card.peso { border-color:#1d4ed8; background:linear-gradient(135deg,#1e3a5f,#1f2937); }
.currency-card.peso:hover { border-color:#3b82f6; box-shadow:0 4px 12px rgba(59,130,246,.2); }
.currency-card.dollar { border-color:#15803d; background:linear-gradient(135deg,#14532d,#1f2937); }
.currency-card.dollar:hover { border-color:#22c55e; box-shadow:0 4px 12px rgba(34,197,94,.2); }
.currency-card .cc-icon { width:48px; height:48px; border-radius:.5rem; display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:#fff; font-weight:800; flex-shrink:0; }
.currency-card .cc-title { font-size:1rem; font-weight:800; color:#f9fafb; }
.currency-card .cc-sub { font-size:.72rem; color:#9ca3af; }
.currency-card .cc-balance { font-size:1.2rem; font-weight:800; text-align:right; }
.currency-card .cc-chevron { font-size:.8rem; color:#6b7280; margin-left:.75rem; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-white">Bank</h2>
        <p class="text-xs text-gray-300 mt-0.5">Bank — Overview of confirmed payments & credit balance</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('treasury.confirmation') }}"
           class="flex items-center gap-1.5 px-4 py-2 text-sm bg-amber-500 text-white rounded-md hover:bg-amber-600 font-semibold shadow-sm">
            <i class="fas fa-clipboard-check"></i> Payment Confirmation
        </a>
    </div>
</div>

<!-- STAT CARDS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="stat-card" style="border-color:#15803d;">
        <div class="stat-lbl" style="color:#4ade80;">Total Confirmed</div>
        <div class="stat-val text-green-400">{{ $stats['total_count'] }}</div>
        <div class="text-xs text-green-500 mt-0.5">PHP {{ number_format($stats['total_amount'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Total Net Amount</div>
        <div class="stat-val">PHP {{ number_format($stats['total_net'], 2) }}</div>
    </div>
    <div class="stat-card" style="border-color:#1d4ed8;">
        <div class="stat-lbl" style="color:#60a5fa;">Confirmed Today</div>
        <div class="stat-val text-blue-400">{{ $stats['today_count'] }}</div>
        <div class="text-xs text-blue-400 mt-0.5">PHP {{ number_format($stats['today_amount'], 2) }}</div>
    </div>
    <div class="stat-card" style="border-color:#b45309;">
        <div class="stat-lbl" style="color:#fbbf24;">Remaining Credit Balance</div>
        <div class="stat-val text-amber-400">PHP {{ number_format($stats['remaining_credit'], 2) }}</div>
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
                <div class="cc-balance text-blue-300">₱ {{ number_format($bankStats['pesoTotal'], 2) }}</div>
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
                <div class="cc-balance text-green-300">$ {{ number_format($bankStats['dollarTotal'], 2) }}</div>
                <div class="cc-sub text-right">Total Cash Balance</div>
            </div>
            <div class="cc-chevron"><i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
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
            <a href="{{ route('treasury.summary') }}" class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200">Clear</a>
            @endif
        </form>
        <span class="text-xs text-gray-400">{{ $payments->total() }} confirmed payment{{ $payments->total() != 1 ? 's' : '' }}</span>
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
                    <td class="font-semibold text-blue-400">{{ $payment->collection_receipt_number ?: '—' }}</td>
                    <td class="text-white">{{ $payment->customer_name ?: '—' }}</td>
                    <td class="text-gray-400 text-xs">{{ $payment->invoice_no ?: '—' }}</td>
                    <td class="text-gray-400 text-xs">{{ $payment->dr_no ?: '—' }}</td>
                    <td class="text-gray-400 text-xs">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                    <td class="text-xs text-gray-300">{{ ucfirst($payment->payment_method ?? '—') }}</td>
                    <td class="r font-semibold text-white">PHP {{ number_format($payment->amount, 2) }}</td>
                    <td class="r text-gray-400">{{ number_format($payment->tax, 2) }}</td>
                    <td class="r font-semibold text-green-400">PHP {{ number_format($payment->net ?? $payment->amount, 2) }}</td>
                    <td class="r">
                        @if($payment->overpayment > 0)
                            <span class="text-amber-400 font-bold">PHP {{ number_format($payment->overpayment, 2) }}</span>
                        @else —
                        @endif
                    </td>
                    <td class="r">
                        @if($payment->credit_applied > 0)
                            <span class="text-green-400 font-semibold">PHP {{ number_format($payment->credit_applied, 2) }}</span>
                        @else —
                        @endif
                    </td>
                    <td class="text-xs text-gray-400">{{ $payment->confirmed_by ?: '—' }}</td>
                    <td class="text-xs text-gray-400">{{ $payment->confirmed_at ? \Carbon\Carbon::parse($payment->confirmed_at)->format('M d, Y h:i A') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-center py-10 text-gray-500">No confirmed payments yet.</td></tr>
            @endforelse
            </tbody>

            @if($payments->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right text-gray-400">Page Totals:</td>
                    <td class="r text-white">PHP {{ number_format($payments->sum('amount'), 2) }}</td>
                    <td class="r text-gray-400">{{ number_format($payments->sum('tax'), 2) }}</td>
                    <td class="r text-green-400">PHP {{ number_format($payments->sum(fn($p) => $p->net ?? $p->amount), 2) }}</td>
                    <td class="r text-amber-400 font-bold">PHP {{ number_format($payments->sum('overpayment'), 2) }}</td>
                    <td class="r text-green-400 font-bold">PHP {{ number_format($payments->sum('credit_applied'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="credit-row">
                    <td colspan="6" class="text-right">
                        <i class="fas fa-coins text-amber-400 mr-1"></i> Overall Credit / Overage Balance:
                    </td>
                    <td colspan="7" class="text-left" style="font-size:.9rem;">
                        <span class="text-amber-400 font-bold">PHP {{ number_format($stats['remaining_credit'], 2) }}</span>
                        <span class="text-xs text-gray-400 ml-2">(Total Overpayments: PHP {{ number_format($stats['total_overpayment'], 2) }} — Credits Used: PHP {{ number_format($stats['total_overpayment'] - $stats['remaining_credit'], 2) }})</span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-4 py-3 border-t border-gray-700">
        {{ $payments->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- ═══════════ ADD BANK MODAL ═══════════ -->
<div id="addBankModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-gray-800 border border-gray-700 rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700 bg-gray-900">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-university text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-base">Add Bank Account</h3>
                    <p class="text-gray-400 text-xs">Fill in all required fields</p>
                </div>
            </div>
            <button onclick="closeAddBankModal()" class="text-gray-400 hover:text-white transition text-xl leading-none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="addBankForm" method="POST" action="{{ route('treasury.banks.store') }}">
            @csrf
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                <!-- Row 1: Bank Name + Currency -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                            Bank Name <span class="text-red-400">*</span>
                        </label>
                        <select name="bank_name" id="modal_bank_name" required onchange="updateBankPreview()"
                            class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Bank</option>
                            <option value="BDO">BDO</option>
                            <option value="BPI">BPI</option>
                            <option value="METROBANK">Metrobank</option>
                            <option value="UNIONBANK">UnionBank</option>
                            <option value="SECURITY BANK">Security Bank</option>
                            <option value="PBB">PBB</option>
                            <option value="AUB">AUB</option>
                            <option value="PBCOM">PBCOM</option>
                            <option value="BOC">BOC</option>
                            <option value="CHINABANK">China Bank</option>
                            <option value="RCBC">RCBC</option>
                            <option value="MAYBANK">Maybank</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                            Currency <span class="text-red-400">*</span>
                        </label>
                        <select name="currency" required
                            class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="PHP">₱ PHP — Peso</option>
                            <option value="USD">$ USD — Dollar</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Account Type + Account Number -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                            Account Type <span class="text-red-400">*</span>
                        </label>
                        <select name="account_type" required
                            class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="CA">CA — Current / Checking</option>
                            <option value="SA">SA — Savings</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                            Account Number <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="account_number" required placeholder="e.g. 001-234-567890"
                            class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2.5 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                    </div>
                </div>

                <!-- Row 3: Short Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                        Short Name
                    </label>
                    <input type="text" name="short_name" placeholder="e.g. BDO Payroll"
                        class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                </div>

                <!-- Row 4: GL Account -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                        GL Account
                    </label>
                    <div class="relative">
                        <input type="hidden" name="gl_account_id" id="modal_gl_account_id">
                        <input type="text" id="modal_gl_search" placeholder="Search GL account by code or name..."
                            class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                        <div id="modal_gl_dropdown"
                            class="absolute top-full left-0 right-0 mt-1 bg-gray-800 border border-gray-600 rounded-lg max-h-44 overflow-y-auto z-50 hidden shadow-xl">
                        </div>
                    </div>
                </div>

                <!-- Row 5: Opening Balance + Balance As Of -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                            Opening Balance
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-semibold" id="modal_currency_symbol">₱</span>
                            <input type="number" name="cash_balance" step="0.01" min="0" placeholder="0.00"
                                class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg pl-7 pr-3 py-2.5 text-sm text-right focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                            Balance As Of
                        </label>
                        <input type="date" name="balance_as_of"
                            class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Preview Card -->
                <div id="bankPreview" class="hidden bg-gray-900 border border-gray-600 rounded-lg p-3 flex items-center gap-3">
                    <div id="previewIcon" class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-xs text-white flex-shrink-0" style="background:#6b7280;">—</div>
                    <div>
                        <p class="text-white text-sm font-semibold" id="previewName">Bank Name</p>
                        <p class="text-gray-400 text-xs">Preview of how it will appear in the list</p>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-700 bg-gray-900">
                <button type="button" onclick="closeAddBankModal()"
                    class="px-4 py-2 text-sm text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg font-medium transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Bank Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddBankModal() {
    document.getElementById('addBankModal').classList.remove('hidden');
    document.getElementById('addBankModal').classList.add('flex');
}
function closeAddBankModal() {
    document.getElementById('addBankModal').classList.add('hidden');
    document.getElementById('addBankModal').classList.remove('flex');
    document.getElementById('addBankForm').reset();
    document.getElementById('modal_gl_account_id').value = '';
    document.getElementById('modal_gl_search').value = '';
    document.getElementById('bankPreview').classList.add('hidden');
}
document.getElementById('addBankModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddBankModal();
});

// Update currency symbol when currency changes
document.querySelector('[name="currency"]').addEventListener('change', function() {
    document.getElementById('modal_currency_symbol').textContent = this.value === 'PHP' ? '₱' : '$';
});

const bankColors = {
    'BDO':'#0052a0','UNIONBANK':'#f97316','SECURITY BANK':'#7c3aed','PBB':'#dc2626',
    'AUB':'#059669','METROBANK':'#7c3aed','PBCOM':'#0e7490','BPI':'#dc2626',
    'BOC':'#b45309','CHINABANK':'#be123c','RCBC':'#1d4ed8','MAYBANK':'#f59e0b',
};
function updateBankPreview() {
    const sel = document.getElementById('modal_bank_name');
    const val = sel.value;
    if (!val) { document.getElementById('bankPreview').classList.add('hidden'); return; }
    document.getElementById('previewIcon').style.background = bankColors[val] ?? '#6b7280';
    document.getElementById('previewIcon').textContent = val.substring(0, 3).toUpperCase();
    document.getElementById('previewName').textContent = sel.options[sel.selectedIndex].text;
    document.getElementById('bankPreview').classList.remove('hidden');
}

// GL Account search
(function() {
    const searchInput = document.getElementById('modal_gl_search');
    const dropdown    = document.getElementById('modal_gl_dropdown');
    const idInput     = document.getElementById('modal_gl_account_id');
    let timeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const q = this.value.trim();
        if (!q) { dropdown.classList.add('hidden'); return; }
        timeout = setTimeout(() => fetchGL(q), 220);
    });
    searchInput.addEventListener('focus', function() {
        if (this.value.trim()) fetchGL(this.value.trim());
    });
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && !dropdown.contains(e.target))
            dropdown.classList.add('hidden');
    });

    async function fetchGL(q) {
        try {
            const res  = await fetch(`/ar-adjustments/gl-accounts?search=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (!data.success || !data.accounts.length) {
                dropdown.innerHTML = '<div class="px-3 py-2 text-gray-400 text-sm bg-gray-800">No accounts found</div>';
            } else {
                dropdown.innerHTML = data.accounts.map(a => `
                    <div class="px-3 py-2 hover:bg-gray-700 cursor-pointer border-b border-gray-700 last:border-0"
                         onclick="selectGL(${a.id}, '${a.display.replace(/'/g,"\\'")}')">
                        <div class="text-sm font-semibold text-white">${a.display}</div>
                        <div class="text-xs text-gray-400">${a.fs_line_item || ''}</div>
                    </div>`).join('');
            }
            dropdown.classList.remove('hidden');
        } catch(e) { console.error(e); }
    }

    window.selectGL = function(id, display) {
        idInput.value = id;
        searchInput.value = display;
        dropdown.classList.add('hidden');
    };
})();
</script>
@endsection