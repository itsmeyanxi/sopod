@extends('layouts.app')

@section('title', 'Daily Feed Usage')

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:.45rem; padding:.8rem 1rem; }
.stat-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; }
.stat-val { font-size:1.5rem; font-weight:800; color:#111827; line-height:1.1; margin:.1rem 0; }
.usage-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.usage-table thead th { background:#1e3a5f; color:#fff; padding:.5rem .75rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.usage-table thead th.r { text-align:right; }
.usage-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.usage-table tbody tr:hover { background:#f8fbff; }
.usage-table tbody td { padding:.5rem .75rem; color:#374151; vertical-align:middle; }
.usage-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }
.action-btn { padding:.25rem .55rem; border-radius:.3rem; font-size:.73rem; font-weight:600; transition:all .12s; }
.action-btn.view  { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.action-btn.view:hover  { background:#dbeafe; }
.action-btn.del   { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
.action-btn.del:hover   { background:#ffe4e6; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-white">Daily Feed Usage</h2>
        <p class="text-xs text-gray-500 mt-0.5">Track daily material consumption from approved BOMs</p>
    </div>
    <a href="{{ route('daily_feed_usage.create') }}"
       class="flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold shadow-sm">
        <i class="fas fa-plus"></i> Log Usage
    </a>
</div>

@if(session('success'))
<div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
</div>
@endif

<!-- FILTERS -->
<div class="b p-4 mb-4">
    <form method="GET" action="{{ route('daily_feed_usage.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-gray-500 font-semibold mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Cycle ref, grower..."
                class="w-full bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-sm text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-xs text-gray-500 font-semibold mb-1">BOM Cycle</label>
            <select name="bom_id" class="bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-sm text-gray-200">
                <option value="">All BOMs</option>
                @foreach($boms as $bom)
                    <option value="{{ $bom->id }}" {{ $bomId == $bom->id ? 'selected' : '' }}>
                        {{ $bom->cycle_ref }} — {{ $bom->grower }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
            <i class="fas fa-search mr-1"></i> Filter
        </button>
        @if($search || $bomId)
        <a href="{{ route('daily_feed_usage.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-200 px-4 py-2 rounded-md text-sm font-medium transition">Clear</a>
        @endif
    </form>
</div>

<!-- TABLE -->
<div class="b">
    <div class="overflow-x-auto">
        <table class="usage-table">
            <thead>
                <tr>
                    <th style="width:130px;">Date</th>
                    <th style="width:140px;">BOM Cycle</th>
                    <th>House</th>
                    <th class="r">Materials Logged</th>
                    <th>Notes</th>
                    <th style="width:110px;">Logged By</th>
                    <th style="width:110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usages as $usage)
                <tr>
                    <td class="font-semibold text-white">{{ $usage->usage_date->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('inhouse_bom.show', $usage->bom_id) }}" class="text-blue-700 hover:underline text-xs font-semibold">
                            {{ $usage->bom->cycle_ref ?? '—' }}
                        </a>
                    </td>
                    <td>
                        @php
                            $house = $usage->bom?->houses?->where('house_number', $usage->house_number)->first();
                        @endphp
                        {{ $house?->house_name ?: 'House '.$usage->house_number }}
                    </td>
                    <td class="r">{{ count($usage->materials_used ?? []) }} items</td>
                    <td class="text-gray-500 text-xs">{{ \Str::limit($usage->notes, 40) ?: '—' }}</td>
                    <td class="text-gray-500 text-xs">{{ $usage->logged_by ?? '—' }}</td>
                    <td>
                        <div class="flex gap-1">
                            <a href="{{ route('daily_feed_usage.show', $usage) }}" class="action-btn view">
                                <i class="fas fa-eye mr-0.5"></i> View
                            </a>
                            <form method="POST" action="{{ route('daily_feed_usage.destroy', $usage) }}"
                                  onsubmit="return confirmDel(event)">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn del"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-400">
                        <i class="fas fa-clipboard-list text-4xl mb-3 block"></i>
                        <p class="text-sm">No usage logs yet.</p>
                        <a href="{{ route('daily_feed_usage.create') }}" class="text-blue-600 hover:underline text-xs mt-1 inline-block">Log your first usage</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($usages->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $usages->appends(request()->query())->links() }}
    </div>
    @endif
</div>

<script>
function confirmDel(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Delete this usage log?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, delete',
    }).then(r => { if (r.isConfirmed) e.target.submit(); });
    return false;
}
</script>
@endsection
