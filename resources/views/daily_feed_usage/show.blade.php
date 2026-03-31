@extends('layouts.app')

@section('title', 'Usage Log — '.$usage->usage_date->format('M d, Y'))

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.b-hd { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#1e3a5f; padding:.6rem 1rem; border-bottom:1px solid #f0f4f8; background:linear-gradient(to right,#f0f7ff,#f8fafc); border-radius:.5rem .5rem 0 0; }
.detail-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.detail-table thead th { background:#1e3a5f; color:#fff; padding:.45rem .6rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.detail-table thead th.r { text-align:right; }
.detail-table tbody tr { border-bottom:1px solid #f3f4f6; }
.detail-table tbody td { padding:.4rem .6rem; color:#374151; vertical-align:middle; }
.detail-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }
.cat-hd td { background:#eef5ff; font-weight:700; color:#1e3a5f; font-size:.74rem; }
</style>

<div class="max-w-4xl mx-auto">
    <!-- HEADER -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-sm text-gray-500 mb-1">
                <a href="{{ route('daily_feed_usage.index') }}" class="hover:text-gray-300"><i class="fas fa-arrow-left mr-1"></i>Usage List</a>
            </div>
            <h2 class="text-xl font-bold text-white">Usage Log — {{ $usage->usage_date->format('F d, Y') }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $usage->bom->cycle_ref ?? '—' }}
                · {{ $house?->house_name ?: 'House '.$usage->house_number }}
                · Logged by {{ $usage->logged_by ?? '—' }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inhouse_bom.show', $usage->bom_id) }}"
               class="flex items-center gap-1.5 px-4 py-2 text-sm border border-blue-300 rounded-md hover:bg-blue-50 text-blue-600">
                <i class="fas fa-clipboard-list"></i> View BOM
            </a>
            <form method="POST" action="{{ route('daily_feed_usage.destroy', $usage) }}"
                  onsubmit="event.preventDefault(); Swal.fire({title:'Delete this log?',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'Yes'}).then(r=>{if(r.isConfirmed)this.submit()})">
                @csrf @method('DELETE')
                <button class="flex items-center gap-1.5 px-4 py-2 text-sm border border-red-200 rounded-md hover:bg-red-50 text-red-600">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <!-- INFO CARD -->
    <div class="b mb-4">
        <div class="b-hd">Log Details</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 text-sm">
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">BOM Cycle</div>
                <div class="font-semibold text-white">{{ $usage->bom->cycle_ref ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">House</div>
                <div class="font-semibold text-white">{{ $house?->house_name ?: 'House '.$usage->house_number }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Usage Date</div>
                <div class="font-semibold text-white">{{ $usage->usage_date->format('M d, Y') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Logged By</div>
                <div class="font-semibold text-white">{{ $usage->logged_by ?? '—' }}</div>
            </div>
            @if($usage->notes)
            <div class="col-span-2 md:col-span-4">
                <div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Notes</div>
                <div>{{ $usage->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- MATERIALS USED -->
    @php
        $materialsUsed = collect($usage->materials_used ?? []);
        $grouped = $materialsUsed->groupBy('category');
        $catLabels = [
            'feed'=>'Feeds','supplement'=>'Supplements','vaccine'=>'Vaccine',
            'disinfectant'=>'Disinfectant','cleaning_material'=>'Cleaning Material',
            'supply'=>'Supplies','labor'=>'Labor','overhead'=>'Overhead'
        ];
        $catOrder = ['feed','supplement','vaccine','disinfectant','cleaning_material','supply','labor','overhead'];

        // Get BOM materials for comparison
        $bomMats = collect($house?->materials ?? []);
    @endphp

    <div class="b mb-6">
        <div class="b-hd">Materials Used</div>
        <div class="overflow-x-auto">
            <table class="detail-table">
                <thead>
                    <tr>
                        <th style="width:250px;">Material</th>
                        <th class="r" style="width:110px;">BOM Qty</th>
                        <th style="width:70px;">UOM</th>
                        <th class="r" style="width:110px;">Used Today</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($catOrder as $cat)
                        @if($grouped->has($cat))
                        <tr class="cat-hd"><td colspan="4">{{ $catLabels[$cat] ?? $cat }}</td></tr>
                        @foreach($grouped[$cat] as $mat)
                        @php
                            $bomMat = $bomMats->where('name', $mat['name'])->where('category', $cat)->first();
                            $bomQty = $bomMat ? ($cat === 'feed' ? (float)($bomMat['qty_bags'] ?? 0) : (float)($bomMat['qty_kg'] ?? 0)) : 0;
                        @endphp
                        <tr>
                            <td style="padding-left:1.4rem;">{{ $mat['name'] ?? '—' }}</td>
                            <td class="r">{{ $bomQty ? number_format($bomQty, 2) : '—' }}</td>
                            <td>{{ $mat['uom'] ?? '—' }}</td>
                            <td class="r font-semibold text-blue-800">{{ number_format((float)($mat['qty_used'] ?? 0), 2) }}</td>
                        </tr>
                        @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
