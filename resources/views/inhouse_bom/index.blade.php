@extends('layouts.app')

@section('title', 'In-House BOM')

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.badge { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; }
.badge-draft    { background:#f3f4f6; color:#6b7280; }
.badge-active   { background:#dbeafe; color:#1d4ed8; }
.badge-complete { background:#dcfce7; color:#15803d; }
.badge-archived { background:#fef9c3; color:#854d0e; }
.badge-approved { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
.badge-pending  { background:#fef3c7; color:#92400e; }

.bom-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.bom-table thead th { background:#1e3a5f; color:#fff; padding:.5rem .75rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.bom-table thead th.r { text-align:right; }
.bom-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.bom-table tbody tr:hover { background:#f8fbff; }
.bom-table tbody td { padding:.5rem .75rem; color:#374151; vertical-align:middle; }
.bom-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }

.stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; }
.stat-val { font-size:1.5rem; font-weight:800; color:#111827; line-height:1.1; margin:.1rem 0; }

.search-input { padding:.38rem .65rem; border:1px solid #d1d5db; border-radius:.375rem; font-size:.83rem; color:#111827; }
.search-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }

.action-btn { padding:.25rem .55rem; border-radius:.3rem; font-size:.73rem; font-weight:600; transition:all .12s; }
.action-btn.view  { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.action-btn.view:hover  { background:#dbeafe; }
.action-btn.edit  { background:#f9fafb; color:#374151; border:1px solid #e5e7eb; }
.action-btn.edit:hover  { background:#f3f4f6; }
.action-btn.del   { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
.action-btn.del:hover   { background:#ffe4e6; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-white">In-House BOM</h2>
        <p class="text-xs text-gray-500 mt-0.5">Bill of Materials — Broiler Production Cycles</p>
    </div>
    <a href="{{ route('inhouse_bom.create') }}"
       class="flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold shadow-sm">
        <i class="fas fa-plus"></i> New BOM
    </a>
</div>

@if(session('success'))
<div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-times-circle text-red-500"></i> {{ session('error') }}
</div>
@endif

@php
    $statusMap  = ['draft'=>'badge-draft','active'=>'badge-active','complete'=>'badge-complete','archived'=>'badge-archived'];
    $totalBoms  = $boms->total();
    $activeCnt  = \App\Models\InHouseBom::where('status','active')->count();
    $completeCnt= \App\Models\InHouseBom::where('status','complete')->count();
    $allHouses  = \App\Models\InHouseBomHouse::whereNotNull('cost_per_kg');
    $avgCpk     = $allHouses->avg('cost_per_kg');
@endphp

<!-- STAT CARDS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="stat-card">
        <div class="stat-lbl">Total BOMs</div>
        <div class="stat-val">{{ $totalBoms }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Active Cycles</div>
        <div class="stat-val text-blue-700">{{ $activeCnt }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-lbl">Completed</div>
        <div class="stat-val text-green-700">{{ $completeCnt }}</div>
    </div>
    <div class="stat-card" style="border-color:#bfdbfe;background:linear-gradient(135deg,#eff6ff,#fff);">
        <div class="stat-lbl" style="color:#3b82f6;">Avg. Cost / kg</div>
        <div class="stat-val text-blue-800">{{ $avgCpk ? 'PHP '.number_format($avgCpk,2) : '—' }}</div>
    </div>
</div>

<!-- TABLE CARD -->
<div class="b">
    <!-- Toolbar -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 gap-3 flex-wrap">
        <div class="flex items-center gap-2">
            <input type="text" id="search-input" class="search-input" style="width:220px;"
                   placeholder="Search cycle ref, grower..." oninput="filterTable()">
            <select id="status-filter" class="search-input" style="width:140px;" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="complete">Complete</option>
                <option value="archived">Archived</option>
            </select>
        </div>
        <span class="text-xs text-gray-500" id="row-count">{{ $totalBoms }} record{{ $totalBoms != 1 ? 's' : '' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="bom-table">
            <thead>
                <tr>
                    <th style="width:150px;">Cycle Ref</th>
                    <th style="width:105px;">Date</th>
                    <th>Grower</th>
                    <th class="r">Houses</th>
                    <th class="r">Loading Qty</th>
                    <th class="r">Harvest Qty</th>
                    <th class="r">FCR</th>
                    <th class="r">Total Cost</th>
                    <th class="r">Cost/kg</th>
                    <th style="width:80px;">Status</th>
                    <th style="width:80px;">Approval</th>
                    <th style="width:110px;">Created By</th>
                    <th style="width:115px;">Actions</th>
                </tr>
            </thead>
            <tbody id="bom-list-tbody">

            @forelse($boms as $bom)
                @php
                    $totalLoading = $bom->houses->sum('loading_qty');
                    $totalHarvest = $bom->houses->sum('harvest_qty');
                    $totalCost    = $bom->houses->sum('total_cost');
                    $totalKg      = $bom->houses->sum('total_kg');
                    $avgFcr       = $bom->houses->whereNotNull('fcr')->avg('fcr');
                    $costPerKg    = $totalKg > 0 ? $totalCost / $totalKg : null;
                    $searchStr    = strtolower($bom->cycle_ref . ' ' . $bom->grower);
                @endphp
                <tr data-status="{{ $bom->status }}" data-search="{{ $searchStr }}">
                    <td class="font-semibold">
                        <a href="{{ route('inhouse_bom.show', $bom) }}"
                           class="text-blue-700 hover:underline">{{ $bom->cycle_ref }}</a>
                        @if($bom->parent_bom_id)
                            <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.6rem;vertical-align:middle;">EXT</span>
                        @endif
                    </td>
                    <td class="text-gray-500 text-xs">{{ $bom->cycle_date->format('M d, Y') }}</td>
                    <td>{{ $bom->grower ?: '—' }}</td>
                    <td class="r">{{ $bom->houses_count }}</td>
                    <td class="r">{{ $totalLoading ? number_format($totalLoading) : '—' }}</td>
                    <td class="r">{{ $totalHarvest ? number_format($totalHarvest) : '—' }}</td>
                    <td class="r">{{ $avgFcr ? number_format($avgFcr,3) : '—' }}</td>
                    <td class="r font-semibold text-white">
                        {{ $totalCost ? 'PHP '.number_format($totalCost,2) : '—' }}
                    </td>
                    <td class="r">{{ $costPerKg ? number_format($costPerKg,2) : '—' }}</td>
                    <td>
                        <span class="badge {{ $statusMap[$bom->status] ?? 'badge-draft' }}">
                            {{ ucfirst($bom->status) }}
                        </span>
                    </td>
                    <td>
                        @if($bom->approved)
                            <span class="badge badge-approved" title="Approved by {{ $bom->approved_by }}">
                                <i class="fas fa-check-circle mr-0.5"></i> Yes
                            </span>
                        @else
                            <span class="badge badge-pending">Pending</span>
                        @endif
                    </td>
                    <td class="text-gray-500 text-xs">{{ $bom->creator->name ?? '—' }}</td>
                    <td>
                        <div class="flex gap-1 flex-wrap">
                            <a href="{{ route('inhouse_bom.show', $bom) }}" class="action-btn view">
                                <i class="fas fa-eye mr-0.5"></i> View
                            </a>
                            @if($bom->approved)
                            <a href="{{ route('purchase_requests.create', ['bom_id' => $bom->id]) }}" class="action-btn" style="background:#faf5ff;color:#7c3aed;border:1px solid #ddd6fe;" title="Create Purchase Request from BOM">
                                <i class="fas fa-file-invoice mr-0.5"></i> PR
                            </a>
                            @endif
                            @if(!$bom->approved)
                            <a href="{{ route('inhouse_bom.edit', $bom) }}" class="action-btn edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('inhouse_bom.destroy', $bom) }}"
                                  onsubmit="return confirmDelete(event, '{{ $bom->cycle_ref }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn del">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                {{-- empty-state row inserted by JS below --}}
            @endforelse

            </tbody>
        </table>

        @if($boms->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-gray-500">
            <i class="fas fa-clipboard-list text-4xl mb-3 text-gray-200"></i>
            <p class="text-sm font-medium text-gray-500">No BOM records yet</p>
            <p class="text-xs mt-1">Create your first Bill of Materials to get started.</p>
            <a href="{{ route('inhouse_bom.create') }}"
               class="mt-4 flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold">
                <i class="fas fa-plus"></i> Create BOM
            </a>
        </div>
        @endif

    </div>

    @if($boms->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $boms->links() }}
    </div>
    @endif
</div>

<script>
function filterTable() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    const rows   = document.querySelectorAll('#bom-list-tbody tr[data-status]');
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !search || (row.dataset.search || '').includes(search);
        const matchStatus = !status || row.dataset.status === status;
        row.style.display = (matchSearch && matchStatus) ? '' : 'none';
        if (matchSearch && matchStatus) visible++;
    });

    document.getElementById('row-count').textContent =
        visible + ' record' + (visible !== 1 ? 's' : '');
}

function confirmDelete(e, ref) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({
        title: 'Delete "' + ref + '"?',
        text: 'All house data will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, delete it',
    }).then(result => { if (result.isConfirmed) form.submit(); });
    return false;
}
</script>

@endsection