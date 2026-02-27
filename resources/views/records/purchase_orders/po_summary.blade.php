@extends('layouts.app')

@section('title', 'PO Summary Monitor')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap');

    .po-summary-wrap {
        font-family: 'IBM Plex Sans', sans-serif;
    }

    .mono {
        font-family: 'IBM Plex Mono', monospace;
    }

    /* ── KPI Cards ── */
    .kpi-card {
        background: #111827;
        border: 1px solid #1f2937;
        border-radius: 8px;
        padding: 1.25rem 1.5rem;
        position: relative;
        overflow: hidden;
        transition: border-color 0.2s, transform 0.2s;
    }
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 3px;
        height: 100%;
    }
    .kpi-card:hover { transform: translateY(-2px); border-color: #374151; }
    .kpi-card.accent-blue::before  { background: #3b82f6; }
    .kpi-card.accent-green::before { background: #22c55e; }
    .kpi-card.accent-amber::before { background: #f59e0b; }
    .kpi-card.accent-rose::before  { background: #f43f5e; }
    .kpi-card.accent-violet::before{ background: #8b5cf6; }

    .kpi-value {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 1.6rem;
        font-weight: 600;
        color: #f9fafb;
        line-height: 1.2;
    }
    .kpi-label {
        font-size: 0.7rem;
        font-weight: 500;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.4rem;
    }
    .kpi-sub {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.7rem;
        color: #4b5563;
        margin-top: 0.3rem;
    }

    /* ── Status Pills ── */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        font-family: 'IBM Plex Mono', monospace;
    }
    .status-PAID        { background: rgba(34,197,94,0.12);  color: #22c55e;  border: 1px solid rgba(34,197,94,0.25); }
    .status-UNPAID      { background: rgba(244,63,94,0.12);  color: #f43f5e;  border: 1px solid rgba(244,63,94,0.25); }
    .status-FOR_DEPOSIT { background: rgba(59,130,246,0.12); color: #60a5fa;  border: 1px solid rgba(59,130,246,0.25); }
    .status-FOR_COLLECTION{ background: rgba(139,92,246,0.12);color: #a78bfa; border: 1px solid rgba(139,92,246,0.25); }
    .status-HOLD        { background: rgba(245,158,11,0.12); color: #fbbf24;  border: 1px solid rgba(245,158,11,0.25); }
    .status-CANCEL      { background: rgba(107,114,128,0.12);color: #9ca3af;  border: 1px solid rgba(107,114,128,0.25); }
    .status-REFUNDED    { background: rgba(20,184,166,0.12); color: #2dd4bf;  border: 1px solid rgba(20,184,166,0.25); }

    /* ── Panels ── */
    .panel {
        background: #111827;
        border: 1px solid #1f2937;
        border-radius: 8px;
        overflow: hidden;
    }
    .panel-header {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid #1f2937;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .panel-title {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #6b7280;
    }

    /* ── Bar chart rows ── */
    .bar-row {
        display: grid;
        grid-template-columns: 9rem 1fr 7rem;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 1.25rem;
        border-bottom: 1px solid #1a2030;
        transition: background 0.15s;
    }
    .bar-row:last-child { border-bottom: none; }
    .bar-row:hover { background: #0f172a; }
    .bar-track {
        height: 6px;
        background: #1f2937;
        border-radius: 3px;
        overflow: hidden;
    }
    .bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 1s cubic-bezier(0.4,0,0.2,1);
    }
    .bar-label {
        font-size: 0.72rem;
        color: #9ca3af;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bar-amount {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: #d1d5db;
        text-align: right;
    }

    /* ── Table ── */
    .po-table { width: 100%; border-collapse: collapse; }
    .po-table thead th {
        padding: 0.6rem 1rem;
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #4b5563;
        background: #0f172a;
        text-align: left;
        border-bottom: 1px solid #1f2937;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .po-table tbody tr {
        border-bottom: 1px solid #1a2030;
        transition: background 0.12s;
    }
    .po-table tbody tr:hover { background: #0f172a; }
    .po-table tbody td {
        padding: 0.6rem 1rem;
        font-size: 0.8rem;
        color: #d1d5db;
    }
    .po-table tbody td.mono-col {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: #9ca3af;
    }

    /* ── Filters ── */
    .filter-select {
        background: #0f172a;
        border: 1px solid #1f2937;
        border-radius: 4px;
        color: #9ca3af;
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        outline: none;
        cursor: pointer;
        font-family: 'IBM Plex Sans', sans-serif;
    }
    .filter-select:focus { border-color: #3b82f6; color: #f9fafb; }

    .search-input {
        background: #0f172a;
        border: 1px solid #1f2937;
        border-radius: 4px;
        color: #d1d5db;
        font-size: 0.75rem;
        padding: 0.35rem 0.75rem;
        outline: none;
        width: 200px;
        font-family: 'IBM Plex Sans', sans-serif;
        transition: border-color 0.15s, width 0.3s;
    }
    .search-input:focus { border-color: #3b82f6; width: 260px; }
    .search-input::placeholder { color: #374151; }

    /* ── Company tabs ── */
    .company-tab {
        padding: 0.4rem 1rem;
        font-size: 0.72rem;
        font-weight: 500;
        border-radius: 4px;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.15s;
        color: #6b7280;
        background: transparent;
        font-family: 'IBM Plex Sans', sans-serif;
    }
    .company-tab:hover { color: #d1d5db; background: #1f2937; }
    .company-tab.active {
        color: #60a5fa;
        background: rgba(59,130,246,0.1);
        border-color: rgba(59,130,246,0.3);
    }

    /* ── Donut placeholder ── */
    .donut-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        gap: 2rem;
        flex-wrap: wrap;
    }
    .donut-svg { transform: rotate(-90deg); }
    .donut-legend { display: flex; flex-direction: column; gap: 0.5rem; }
    .donut-legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.72rem;
        color: #9ca3af;
    }
    .donut-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ── Progress bar for status breakdown ── */
    .status-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 1.25rem;
        border-bottom: 1px solid #1a2030;
    }
    .status-row:last-child { border-bottom: none; }
    .status-name { width: 9rem; font-size: 0.72rem; color: #9ca3af; }
    .status-bar-wrap { flex: 1; height: 6px; background: #1f2937; border-radius: 3px; overflow: hidden; }
    .status-count-num { font-family: 'IBM Plex Mono', monospace; font-size: 0.72rem; color: #6b7280; width: 2.5rem; text-align: right; }
    .status-amount-num { font-family: 'IBM Plex Mono', monospace; font-size: 0.72rem; color: #d1d5db; width: 8rem; text-align: right; }

    /* ── Table scroll wrapper ── */
    .table-scroll { overflow-x: auto; overflow-y: auto; max-height: 420px; }
    .table-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
    .table-scroll::-webkit-scrollbar-track { background: transparent; }
    .table-scroll::-webkit-scrollbar-thumb { background: #374151; border-radius: 2px; }

    /* ── Divider badge ── */
    .section-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #4b5563;
    }
    .section-badge::before {
        content: '';
        display: block;
        width: 12px;
        height: 1px;
        background: #374151;
    }

    /* ── Stagger animations ── */
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .kpi-card { animation: fadeSlideUp 0.4s ease both; }
    .kpi-card:nth-child(1) { animation-delay: 0.05s; }
    .kpi-card:nth-child(2) { animation-delay: 0.1s; }
    .kpi-card:nth-child(3) { animation-delay: 0.15s; }
    .kpi-card:nth-child(4) { animation-delay: 0.2s; }
    .kpi-card:nth-child(5) { animation-delay: 0.25s; }
    .panel { animation: fadeSlideUp 0.5s ease 0.2s both; }

    /* Loading shimmer */
    .shimmer {
        background: linear-gradient(90deg, #1f2937 25%, #374151 50%, #1f2937 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>

<div class="po-summary-wrap" x-data="poSummary()" x-init="init()">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-white font-semibold text-xl tracking-tight">PO Summary Monitor</h1>
            <p class="text-gray-500 text-xs mt-0.5 mono">{{ now()->format('l, F j Y · H:i') }} · Real-time snapshot</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Company filter tabs --}}
            <div class="flex items-center gap-1 bg-gray-900 border border-gray-800 rounded-md p-1">
                <button class="company-tab" :class="{ active: activeCompany === 'all' }" @click="setCompany('all')">All</button>
                <button class="company-tab" :class="{ active: activeCompany === 'NBC' }" @click="setCompany('NBC')">NBC</button>
                <button class="company-tab" :class="{ active: activeCompany === 'PMAI' }" @click="setCompany('PMAI')">PMAI</button>
                <button class="company-tab" :class="{ active: activeCompany === 'PARI' }" @click="setCompany('PARI')">PARI</button>
            </div>
            <button @click="refreshData()" class="filter-select flex items-center gap-1.5" title="Refresh">
                <i class="fas fa-sync-alt text-xs" :class="{ 'fa-spin': loading }"></i>
                <span>Refresh</span>
            </button>
        </div>
    </div>

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="kpi-card accent-blue">
            <p class="kpi-label">Total PO Value</p>
            <p class="kpi-value" x-text="formatPHP(kpis.totalPOAmount)">—</p>
            <p class="kpi-sub" x-text="kpis.totalPOCount + ' purchase orders'">—</p>
        </div>
        <div class="kpi-card accent-green">
            <p class="kpi-label">Total Invoiced</p>
            <p class="kpi-value" x-text="formatPHP(kpis.totalInvoiceAmount)">—</p>
            <p class="kpi-sub" x-text="formatPct(kpis.totalInvoiceAmount, kpis.totalPOAmount) + '% of PO value'">—</p>
        </div>
        <div class="kpi-card accent-rose">
            <p class="kpi-label">Outstanding Balance</p>
            <p class="kpi-value" x-text="formatPHP(kpis.totalPOAmount - kpis.totalInvoiceAmount)">—</p>
            <p class="kpi-sub">uninvoiced amount</p>
        </div>
        <div class="kpi-card accent-amber">
            <p class="kpi-label">Unpaid / For Collection</p>
            <p class="kpi-value" x-text="formatPHP(kpis.unpaidAmount)">—</p>
            <p class="kpi-sub" x-text="kpis.unpaidCount + ' orders pending'">—</p>
        </div>
        <div class="kpi-card accent-violet">
            <p class="kpi-label">Active Suppliers</p>
            <p class="kpi-value mono" x-text="kpis.supplierCount">—</p>
            <p class="kpi-sub" x-text="kpis.categoryCount + ' categories'">—</p>
        </div>
    </div>

    {{-- ── Middle Row: Status Breakdown + Company Split + Category ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">

        {{-- Status Breakdown --}}
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Payment Status</span>
                <span class="mono" style="font-size:0.65rem; color:#374151;" x-text="kpis.totalPOCount + ' total POs'"></span>
            </div>
            <template x-for="s in statuses" :key="s.key">
                <div class="status-row">
                    <div class="status-name">
                        <span class="status-pill" :class="'status-' + s.key.replace(/ /g,'_')" x-text="s.label"></span>
                    </div>
                    <div class="status-bar-wrap">
                        <div class="bar-fill" :style="'width:' + pct(s.amount, kpis.totalPOAmount) + '%; background:' + s.color"></div>
                    </div>
                    <div class="status-count-num" x-text="s.count"></div>
                    <div class="status-amount-num" x-text="formatPHP(s.amount)"></div>
                </div>
            </template>
        </div>

        {{-- Company Split --}}
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">By Company</span>
            </div>
            <div class="donut-wrap" x-show="!loading">
                <svg class="donut-svg" width="100" height="100" viewBox="0 0 42 42">
                    <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#1f2937" stroke-width="5"/>
                    <template x-for="(seg, i) in donutSegments" :key="i">
                        <circle
                            cx="21" cy="21" r="15.915"
                            fill="transparent"
                            :stroke="seg.color"
                            stroke-width="5"
                            :stroke-dasharray="seg.dash"
                            :stroke-dashoffset="seg.offset"
                        />
                    </template>
                </svg>
                <div class="donut-legend">
                    <template x-for="c in companies" :key="c.key">
                        <div class="donut-legend-item">
                            <span class="donut-dot" :style="'background:' + c.color"></span>
                            <span x-text="c.key"></span>
                            <span class="mono ml-auto pl-3" style="color:#d1d5db; font-size:0.68rem;" x-text="formatPHP(c.amount)"></span>
                        </div>
                    </template>
                </div>
            </div>
            <div class="p-4" x-show="loading">
                <div class="shimmer h-20 w-full"></div>
            </div>
        </div>

        {{-- Top Categories --}}
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Top Categories</span>
            </div>
            <template x-for="cat in topCategories" :key="cat.name">
                <div class="bar-row">
                    <span class="bar-label" x-text="cat.name"></span>
                    <div class="bar-track">
                        <div class="bar-fill" :style="'width:' + pct(cat.amount, topCategories[0].amount) + '%; background: #3b82f6;'"></div>
                    </div>
                    <span class="bar-amount" x-text="formatPHP(cat.amount)"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- ── Bottom Row: Top Suppliers + Recent POs Table ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

        {{-- Top Suppliers --}}
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Top Suppliers</span>
                <span class="mono" style="font-size:0.65rem;color:#374151;">by PO amount</span>
            </div>
            <template x-for="(sup, i) in topSuppliers" :key="sup.name">
                <div class="bar-row">
                    <span class="bar-label">
                        <span class="mono" style="color:#374151;" x-text="(i+1).toString().padStart(2,'0') + '. '"></span>
                        <span x-text="sup.name"></span>
                    </span>
                    <div class="bar-track">
                        <div class="bar-fill" :style="'width:' + pct(sup.amount, topSuppliers[0].amount) + '%; background: #8b5cf6;'"></div>
                    </div>
                    <span class="bar-amount" x-text="formatPHP(sup.amount)"></span>
                </div>
            </template>
        </div>

        {{-- Recent POs Table --}}
        <div class="panel lg:col-span-2">
            <div class="panel-header">
                <span class="panel-title">Recent Purchase Orders</span>
                <div class="flex items-center gap-2">
                    <select class="filter-select" x-model="filterStatus" @change="applyFilters()">
                        <option value="">All Statuses</option>
                        <option value="PAID">PAID</option>
                        <option value="UNPAID">UNPAID</option>
                        <option value="FOR DEPOSIT">FOR DEPOSIT</option>
                        <option value="FOR COLLECTION">FOR COLLECTION</option>
                        <option value="HOLD">HOLD</option>
                        <option value="CANCEL">CANCEL</option>
                        <option value="REFUNDED">REFUNDED</option>
                    </select>
                    <select class="filter-select" x-model="filterCategory" @change="applyFilters()">
                        <option value="">All Categories</option>
                        <option value="Day-Old-Chick">Day-Old-Chick</option>
                        <option value="Feeds">Feeds</option>
                        <option value="Vitamins">Vitamins</option>
                        <option value="Vaccines - Farm">Vaccines - Farm</option>
                        <option value="Farm Supplies">Farm Supplies</option>
                        <option value="Repair and Maintenance">Repair and Maintenance</option>
                        <option value="Fixed Asset">Fixed Asset</option>
                        <option value="Office Supplies">Office Supplies</option>
                    </select>
                    <input type="text" class="search-input" placeholder="Search PO / supplier…" x-model="searchQuery" @input="applyFilters()">
                </div>
            </div>
            <div class="table-scroll">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Category</th>
                            <th>GL Type</th>
                            <th>Company</th>
                            <th>PO Amount</th>
                            <th>Invoice Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="filteredPOs.length === 0">
                            <tr>
                                <td colspan="9" class="text-center py-8 text-gray-600 mono text-xs">No records match your filters.</td>
                            </tr>
                        </template>
                        <template x-for="po in filteredPOs.slice(0, 60)" :key="po.po_number + po.supplier">
                            <tr>
                                <td class="mono-col" x-text="po.po_number"></td>
                                <td class="mono-col" x-text="po.date"></td>
                                <td x-text="po.supplier" style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></td>
                                <td x-text="po.category" style="font-size:0.72rem; color:#9ca3af;"></td>
                                <td x-text="po.gl_type" style="font-size:0.7rem; color:#6b7280;"></td>
                                <td>
                                    <span class="mono" style="font-size:0.68rem; padding:0.1rem 0.4rem; background:#0f172a; border:1px solid #1f2937; border-radius:3px; color:#60a5fa;" x-text="po.company"></span>
                                </td>
                                <td class="mono-col text-right" x-text="formatPHP(po.po_amount)"></td>
                                <td class="mono-col text-right" x-text="formatPHP(po.invoice_amount)"></td>
                                <td>
                                    <span class="status-pill" :class="'status-' + po.status.replace(/ /g,'_')" x-text="po.status"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="panel-header" style="border-top:1px solid #1f2937; border-bottom:none; justify-content:flex-end;">
                <span class="mono" style="font-size:0.65rem; color:#374151;" x-text="'Showing ' + Math.min(filteredPOs.length, 60) + ' of ' + filteredPOs.length + ' records'"></span>
            </div>
        </div>

    </div>
</div>

{{-- ── Alpine.js data ── --}}
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function poSummary() {
    return {
        loading: true,
        activeCompany: 'all',
        filterStatus: '',
        filterCategory: '',
        searchQuery: '',
        allPOs: [],
        filteredPOs: [],

        kpis: {
            totalPOAmount: 0,
            totalInvoiceAmount: 0,
            totalPOCount: 0,
            unpaidAmount: 0,
            unpaidCount: 0,
            supplierCount: 0,
            categoryCount: 0,
        },

        statuses: [],
        companies: [],
        topCategories: [],
        topSuppliers: [],
        donutSegments: [],

        async init() {
            await this.fetchData();
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = this.activeCompany !== 'all' ? '?company=' + this.activeCompany : '';
                const res = await fetch(`{{ route('po_summary.api_data') }}${params}`);
                const data = await res.json();
                this.processData(data);
            } catch (e) {
                console.error('PO Summary fetch error:', e);
                this.processData({ pos: [], summary: {} });
            }
            this.loading = false;
        },

        processData(data) {
            this.allPOs = data.pos || [];
            this.applyFilters();
            this.buildKPIs();
            this.buildStatuses();
            this.buildCompanies();
            this.buildCategories();
            this.buildSuppliers();
            this.buildDonut();
            // Animate bars after render
            this.$nextTick(() => this.animateBars());
        },

        applyFilters() {
            let result = [...this.allPOs];
            if (this.filterStatus)   result = result.filter(p => p.status === this.filterStatus);
            if (this.filterCategory) result = result.filter(p => p.category === this.filterCategory);
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(p =>
                    (p.po_number||'').toLowerCase().includes(q) ||
                    (p.supplier||'').toLowerCase().includes(q)
                );
            }
            if (this.activeCompany !== 'all') result = result.filter(p => p.company === this.activeCompany);
            this.filteredPOs = result;
        },

        buildKPIs() {
    const pos = this.activeCompany === 'all' ? this.allPOs
        : this.allPOs.filter(p => p.company === this.activeCompany);

    this.kpis.totalPOAmount      = pos.reduce((s,p) => s + (p.po_amount||0), 0);
    this.kpis.totalInvoiceAmount = pos.reduce((s,p) => s + (p.invoice_amount||0), 0);
    this.kpis.totalPOCount       = pos.length;

    // Unpaid: use po_amount for UNPAID (no invoice), invoice_amount for invoiced-but-unpaid
    const unpaid = pos.filter(p => ['UNPAID','FOR COLLECTION','FOR DEPOSIT'].includes(p.status));
    this.kpis.unpaidAmount = unpaid.reduce((s,p) => {
        if (p.status === 'UNPAID') return s + (p.po_amount||0);
        return s + (p.invoice_amount||0);
    }, 0);
    this.kpis.unpaidCount  = unpaid.length;

    this.kpis.supplierCount  = new Set(pos.map(p => p.supplier)).size;
    this.kpis.categoryCount  = new Set(pos.map(p => p.category)).size;
},

        buildStatuses() {
            const defs = [
                { key: 'PAID',           label: 'PAID',           color: '#22c55e' },
                { key: 'UNPAID',         label: 'UNPAID',         color: '#f43f5e' },
                { key: 'FOR_DEPOSIT',    label: 'FOR DEPOSIT',    color: '#60a5fa' },
                { key: 'FOR_COLLECTION', label: 'FOR COLLECTION', color: '#a78bfa' },
                { key: 'HOLD',           label: 'HOLD',           color: '#fbbf24' },
                { key: 'REFUNDED',       label: 'REFUNDED',       color: '#2dd4bf' },
                { key: 'CANCEL',         label: 'CANCEL',         color: '#6b7280' },
            ];
            const pos = this.activeCompany === 'all' ? this.allPOs : this.allPOs.filter(p => p.company === this.activeCompany);
            this.statuses = defs.map(d => {
                const rows = pos.filter(p => p.status === d.label);
                return { ...d, count: rows.length, amount: rows.reduce((s,p)=>s+(p.po_amount||0),0) };
            }).filter(d => d.count > 0);
        },

        buildCompanies() {
            const companyDefs = [
                { key: 'NBC',  color: '#3b82f6' },
                { key: 'PMAI', color: '#22c55e' },
                { key: 'PARI', color: '#f59e0b' },
            ];
            this.companies = companyDefs.map(c => ({
                ...c,
                amount: this.allPOs.filter(p => p.company === c.key).reduce((s,p)=>s+(p.po_amount||0),0),
                count:  this.allPOs.filter(p => p.company === c.key).length,
            })).filter(c => c.count > 0);
        },

        buildDonut() {
            const total = this.companies.reduce((s,c)=>s+c.amount,0);
            const circumference = 100; // 2π × r = 2π × 15.915 ≈ 100
            let offset = 0;
            this.donutSegments = this.companies.map(c => {
                const fraction = total > 0 ? c.amount / total : 0;
                const dash = fraction * circumference;
                const seg = { color: c.color, dash: `${dash} ${circumference - dash}`, offset: -offset };
                offset += dash;
                return seg;
            });
        },

        buildCategories() {
            const pos = this.activeCompany === 'all' ? this.allPOs : this.allPOs.filter(p => p.company === this.activeCompany);
            const map = {};
            pos.forEach(p => {
                if (!p.category) return;
                map[p.category] = (map[p.category]||0) + (p.po_amount||0);
            });
            this.topCategories = Object.entries(map)
                .map(([name,amount]) => ({name,amount}))
                .sort((a,b) => b.amount-a.amount)
                .slice(0,8);
        },

        buildSuppliers() {
            const pos = this.activeCompany === 'all' ? this.allPOs : this.allPOs.filter(p => p.company === this.activeCompany);
            const map = {};
            pos.forEach(p => {
                if (!p.supplier) return;
                map[p.supplier] = (map[p.supplier]||0) + (p.po_amount||0);
            });
            this.topSuppliers = Object.entries(map)
                .map(([name,amount]) => ({name,amount}))
                .sort((a,b) => b.amount-a.amount)
                .slice(0,8);
        },

        animateBars() {
            document.querySelectorAll('.bar-fill').forEach(el => {
                const target = el.style.width;
                el.style.width = '0%';
                setTimeout(() => { el.style.width = target; }, 50);
            });
        },

        setCompany(c) {
            this.activeCompany = c;
            this.fetchData();
        },

        refreshData() { this.fetchData(); },

        formatPHP(val) {
    if (!val && val !== 0) return '—';
    return '₱' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
},

        formatPct(a, b) {
            if (!b) return '0';
            return ((a / b) * 100).toFixed(1);
        },

        pct(val, max) {
            if (!max) return 0;
            return Math.max(2, (val / max) * 100);
        },
    };
}
</script>

@endsection