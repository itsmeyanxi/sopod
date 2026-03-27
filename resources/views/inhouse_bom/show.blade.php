@extends('layouts.app')

@section('title', 'BOM — '.$bom->cycle_ref)

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.b-hd { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#1e3a5f; padding:.6rem 1rem; border-bottom:1px solid #f0f4f8; background:linear-gradient(to right,#f0f7ff,#f8fafc); border-radius:.5rem .5rem 0 0; display:flex; align-items:center; justify-content:space-between; }
.kpi { background:#fff; border:1px solid #e5e7eb; border-radius:.45rem; padding:.75rem 1rem; }
.kpi-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; }
.kpi-val { font-size:1.2rem; font-weight:800; color:#111827; line-height:1.1; margin:.1rem 0; }
.kpi.accent { border-color:#bfdbfe; background:linear-gradient(135deg,#eff6ff,#fff); }
.kpi.accent .kpi-val { color:#1d4ed8; }
.badge { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; }
.badge-draft    { background:#f3f4f6; color:#6b7280; }
.badge-active   { background:#dbeafe; color:#1d4ed8; }
.badge-complete { background:#dcfce7; color:#15803d; }
.badge-archived { background:#fef9c3; color:#854d0e; }
.badge-approved { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
.badge-not-approved { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
.htab { padding:.28rem .8rem; border-radius:999px; font-size:.75rem; font-weight:600; border:1px solid #e5e7eb; color:#6b7280; background:#fff; cursor:pointer; transition:all .15s; }
.htab.active { background:#1e3a5f; color:#fff; border-color:#1e3a5f; }
.bt { width:100%; border-collapse:collapse; font-size:.79rem; }
.bt thead th { background:#1e3a5f; color:#fff; padding:.48rem .6rem; font-size:.69rem; font-weight:600; text-align:left; white-space:nowrap; }
.bt thead th.r { text-align:right; }
.bt tr.grand-total td { background:#0f2744; color:#fff; font-weight:800; font-size:.82rem; padding:.55rem .6rem; }
.bt tr.cat-hd td { background:#1e3a5f; color:#e0f2fe; font-weight:700; padding:.42rem .6rem; font-size:.73rem; }
.bt tr.data-row td { padding:.36rem .6rem; color:#374151; border-bottom:1px solid #f3f4f6; }
.bt td.r { text-align:right; font-variant-numeric:tabular-nums; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-4">
    <div>
        <div class="text-sm text-gray-500 mb-1">
            <a href="{{ route('inhouse_bom.index') }}" class="hover:text-gray-600"><i class="fas fa-arrow-left mr-1"></i>BOM List</a>
        </div>
        <h2 class="text-xl font-bold text-gray-800">
            {{ $bom->cycle_ref }}
            @if($bom->isExtension())
                <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.65rem;vertical-align:middle;">EXT {{ $bom->extension_number }}</span>
            @endif
        </h2>
        <p class="text-xs text-gray-500 mt-0.5">
            {{ $bom->cycle_date->format('F d, Y') }}
            @if($bom->grower) · {{ $bom->grower }} @endif
            · <span class="badge badge-{{ $bom->status }}">{{ ucfirst($bom->status) }}</span>
            @if($bom->isExtension())
                · Extension of <a href="{{ route('inhouse_bom.show', $bom->parent_bom_id) }}" class="text-blue-600 hover:underline font-semibold">{{ $bom->parentBom->cycle_ref }}</a>
            @endif
        </p>
    </div>
    <div class="flex gap-2 items-center">
        @if($bom->approved)
            <span class="badge badge-approved px-3 py-1.5 text-xs"><i class="fas fa-check-circle mr-1"></i>Approved</span>
            <a href="{{ route('inhouse_bom.extend', $bom) }}"
               class="flex items-center gap-1.5 px-4 py-2 text-sm border border-amber-300 rounded-md hover:bg-amber-50 text-amber-700 font-medium">
                <i class="fas fa-layer-group"></i> Extend BOM
            </a>
            <a href="{{ route('purchase_requests.create', ['bom_id' => $bom->id]) }}"
               class="flex items-center gap-1.5 px-4 py-2 text-sm border border-purple-300 rounded-md hover:bg-purple-50 text-purple-700 font-medium">
                <i class="fas fa-file-invoice"></i> Create PR
            </a>
        @endif
        <a href="{{ route('inhouse_bom.export', $bom) }}"
           class="flex items-center gap-1.5 px-4 py-2 text-sm border border-green-300 rounded-md hover:bg-green-50 text-green-700">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <button onclick="printBOM()"
           class="flex items-center gap-1.5 px-4 py-2 text-sm border border-blue-300 rounded-md hover:bg-blue-50 text-blue-600">
            <i class="fas fa-print"></i> Print
        </button>
        @if(!$bom->approved)
        <a href="{{ route('inhouse_bom.edit', $bom) }}"
           class="flex items-center gap-1.5 px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 text-gray-600">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form method="POST" action="{{ route('inhouse_bom.destroy', $bom) }}"
              onsubmit="return confirmDelete(event,'{{ $bom->cycle_ref }}')">
            @csrf @method('DELETE')
            <button class="flex items-center gap-1.5 px-4 py-2 text-sm border border-red-200 rounded-md hover:bg-red-50 text-red-600">
                <i class="fas fa-trash"></i> Delete
            </button>
        </form>
        @endif
    </div>
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
    $totalLoading = $bom->houses->sum('loading_qty');
    $totalHarvest = $bom->houses->sum('harvest_qty');
    $totalKgAll   = $bom->houses->sum('total_kg');
    $totalCostAll = $bom->houses->sum('total_cost');
    $avgFcr       = $bom->houses->whereNotNull('fcr')->avg('fcr');
    $avgCpk       = $totalKgAll > 0 ? $totalCostAll / $totalKgAll : null;
@endphp

@php
    $avgLiv = $bom->houses->whereNotNull('livability')->avg('livability');
    $avgAlw = $bom->houses->whereNotNull('alw')->avg('alw');
    $avgBpi = $bom->houses->whereNotNull('bpi')->avg('bpi');
@endphp

<!-- KPI STRIP -->
<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-2 mb-4">
    <div class="kpi"><div class="kpi-lbl">Loading Qty</div><div class="kpi-val">{{ $totalLoading ? number_format($totalLoading) : '—' }}</div><div class="text-xs text-gray-500">heads</div></div>
    <div class="kpi"><div class="kpi-lbl">Livability</div><div class="kpi-val">{{ $avgLiv ? number_format($avgLiv,2).'%' : '—' }}</div></div>
    <div class="kpi"><div class="kpi-lbl">Harvest Qty</div><div class="kpi-val">{{ $totalHarvest ? number_format($totalHarvest) : '—' }}</div><div class="text-xs text-gray-500">heads</div></div>
    <div class="kpi"><div class="kpi-lbl">ALW</div><div class="kpi-val">{{ $avgAlw ? number_format($avgAlw,2) : '—' }}</div><div class="text-xs text-gray-500">kg/head</div></div>
    <div class="kpi"><div class="kpi-lbl">Avg FCR</div><div class="kpi-val">{{ $avgFcr ? number_format($avgFcr,3) : '—' }}</div></div>
    <div class="kpi"><div class="kpi-lbl">Avg BPI</div><div class="kpi-val">{{ $avgBpi ? number_format($avgBpi,0) : '—' }}</div><div class="text-xs text-gray-500">index</div></div>
    <div class="kpi"><div class="kpi-lbl">Cost / kg</div><div class="kpi-val">{{ $avgCpk ? number_format($avgCpk,2) : '—' }}</div><div class="text-xs text-gray-500">PHP / kg</div></div>
    <div class="kpi accent"><div class="kpi-lbl">Total Cost</div><div class="kpi-val">{{ $totalCostAll ? 'PHP '.number_format(floor($totalCostAll*100)/100,2) : '—' }}</div></div>
</div>

<!-- META INFO -->
<div class="b mb-4">
    <div class="b-hd">Cycle Information</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 text-sm">
        <div><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Cycle Ref</div><div class="font-semibold text-gray-800">{{ $bom->cycle_ref }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Date</div><div>{{ $bom->cycle_date->format('M d, Y') }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Grower</div><div>{{ $bom->grower ?: '—' }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Houses</div><div>{{ $bom->num_houses }}</div></div>
        @if($bom->notes)
        <div class="col-span-2 md:col-span-4"><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Remarks</div><div>{{ $bom->notes }}</div></div>
        @endif
        <div><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Status</div>
            <span class="badge badge-{{ $bom->status }}">{{ ucfirst($bom->status) }}</span>
        </div>
        <div><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Created By</div><div>{{ $bom->creator->name ?? '—' }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase font-semibold mb-0.5">Created At</div><div>{{ $bom->created_at->format('M d, Y h:i A') }}</div></div>
    </div>
</div>

<!-- EXTENSIONS & PARENT INFO -->
@if(!$bom->isExtension() && $bom->extensions->count())
<div class="b mb-4">
    <div class="b-hd">
        <span>Extensions</span>
        <span class="text-xs font-normal text-amber-600 normal-case tracking-normal">
            Combined Cost: PHP {{ number_format($bom->combined_cost, 2) }}
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-amber-50 text-amber-800">
                    <th class="px-4 py-2 text-left font-semibold text-xs">Extension</th>
                    <th class="px-4 py-2 text-left font-semibold text-xs">Date</th>
                    <th class="px-4 py-2 text-right font-semibold text-xs">Houses</th>
                    <th class="px-4 py-2 text-right font-semibold text-xs">Extension Cost</th>
                    <th class="px-4 py-2 text-center font-semibold text-xs">Status</th>
                    <th class="px-4 py-2 text-center font-semibold text-xs">Approved</th>
                    <th class="px-4 py-2 text-center font-semibold text-xs">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bom->extensions as $ext)
                <tr class="border-b border-gray-100 hover:bg-amber-50/50">
                    <td class="px-4 py-2">
                        <a href="{{ route('inhouse_bom.show', $ext) }}" class="text-blue-700 hover:underline font-semibold">
                            {{ $ext->cycle_ref }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $ext->cycle_date->format('M d, Y') }}</td>
                    <td class="px-4 py-2 text-right">{{ $ext->num_houses }}</td>
                    <td class="px-4 py-2 text-right font-semibold">PHP {{ number_format($ext->total_cost, 2) }}</td>
                    <td class="px-4 py-2 text-center"><span class="badge badge-{{ $ext->status }}">{{ ucfirst($ext->status) }}</span></td>
                    <td class="px-4 py-2 text-center">
                        @if($ext->approved)
                            <span class="badge badge-approved"><i class="fas fa-check-circle mr-0.5"></i> Yes</span>
                        @else
                            <span class="badge badge-not-approved">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('inhouse_bom.show', $ext) }}" class="text-blue-600 hover:underline text-xs font-medium">
                            <i class="fas fa-eye mr-0.5"></i> View
                        </a>
                    </td>
                </tr>
                @endforeach
                <tr class="bg-gray-50 font-bold">
                    <td class="px-4 py-2" colspan="3">Total (Original + Extensions)</td>
                    <td class="px-4 py-2 text-right text-blue-800">PHP {{ number_format($bom->combined_cost, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@if($bom->isExtension())
<div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 flex items-center gap-3">
    <i class="fas fa-layer-group text-amber-600"></i>
    <span class="text-sm text-amber-800">
        This is <strong>Extension #{{ $bom->extension_number }}</strong> of
        <a href="{{ route('inhouse_bom.show', $bom->parent_bom_id) }}" class="text-blue-700 hover:underline font-semibold">{{ $bom->parentBom->cycle_ref }}</a>
        · Original Cost: PHP {{ number_format($bom->parentBom->total_cost, 2) }}
        · This Extension: PHP {{ number_format($bom->total_cost, 2) }}
    </span>
</div>
@endif

<!-- HOUSE TABS + BOM TABLE -->
<div class="b mb-6">
    <div class="b-hd">
        <span>House Parameters &amp; Bill of Materials</span>
        <span id="active-house-lbl" class="text-xs font-normal text-blue-600 normal-case tracking-normal"></span>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 flex-wrap p-3 border-b border-gray-100">
        @foreach($bom->houses as $house)
        <button type="button" onclick="showHouse({{ $loop->index }})"
            id="htab-{{ $loop->index }}"
            class="htab {{ $loop->first ? 'active' : '' }}">
            {{ $house->house_name ?: 'House '.$house->house_number }}
        </button>
        @endforeach
    </div>

    @foreach($bom->houses as $hIdx => $house)
    @php
        $cats = ['feed'=>'Feeds','supplement'=>'Supplements','vaccine'=>'Vaccine',
                 'disinfectant'=>'Disinfectant','cleaning_material'=>'Cleaning Material','supply'=>'Supplies',
                 'labor'=>'Labor','overhead'=>'Overhead'];
        $cdmCats = ['disinfectant','cleaning_material','supply'];
        $mats = collect($house->materials ?? []);
        $docAmt = ($house->loading_qty ?? 0) * ($house->doc_cost ?? 24);
        $grandTotal = $house->total_cost ?? 0;
        $totalKg = $house->total_kg ?? 0;
        $grandCpk = $totalKg > 0 ? $grandTotal / $totalKg : 0;
        $feedAmt = $mats->where('category', 'feed')->sum(fn($r) => (float)($r['qty_bags']??0) * (float)($r['cost']??0));
        $costFeeds = $totalKg > 0 ? $feedAmt / $totalKg : 0;
        $costDoc = $totalKg > 0 ? $docAmt / $totalKg : 0;
    @endphp
    <div class="house-panel {{ $hIdx > 0 ? 'hidden' : '' }}" id="hpanel-{{ $hIdx }}">
        <div class="flex flex-col xl:flex-row">
            <!-- PARAMS -->
            <div style="width:255px;min-width:255px;border-right:1px solid #f0f4f8;padding:1rem;" class="flex flex-col gap-2 text-sm">
                {{-- Input parameters --}}
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">House Name</div>
                    <div class="font-semibold text-gray-800">{{ $house->house_name ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Loading Qty (heads)</div>
                    <div class="font-semibold text-gray-800">{{ $house->loading_qty ? number_format($house->loading_qty) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Livability (%)</div>
                    <div class="font-semibold text-gray-800">{{ $house->livability ? number_format($house->livability,2).'%' : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">ALW (kg/head)</div>
                    <div class="font-semibold text-gray-800">{{ $house->alw ? number_format($house->alw,2) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">FCR</div>
                    <div class="font-semibold text-gray-800">{{ $house->fcr ? number_format($house->fcr,3) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Age (days)</div>
                    <div class="font-semibold text-gray-800">{{ $house->age_days ?? '—' }}</div>
                </div>
                <hr class="border-gray-100">
                {{-- Derived / computed fields --}}
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Harvest Qty</div>
                    <div class="font-semibold text-gray-800">{{ $house->harvest_qty ? number_format($house->harvest_qty) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Total kg</div>
                    <div class="font-semibold text-gray-800">{{ $house->total_kg ? number_format((float)$house->total_kg,2) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Feed Req (kg)</div>
                    <div class="font-semibold text-gray-800">{{ $house->feed_req_kg ? number_format((float)$house->feed_req_kg,2) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">BPI (Performance Index)</div>
                    <div class="font-semibold text-gray-800">{{ $house->bpi ? number_format((float)$house->bpi, 0) : '—' }}</div>
                </div>
                <hr class="border-gray-100">
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Cost</div>
                    <div class="font-bold text-blue-800">PHP {{ number_format(floor((float)$grandTotal*100)/100,2) }}</div>
                </div>
                <hr class="border-gray-100">
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Cost of Feeds</div>
                    <div class="font-semibold text-gray-800">{{ $costFeeds ? number_format($costFeeds,2) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Cost of Day Old Chick</div>
                    <div class="font-semibold text-gray-800">{{ $costDoc ? number_format($costDoc,2) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Cost of Total Materials</div>
                    <div class="font-semibold text-gray-800">{{ $grandCpk ? number_format($grandCpk,2) : '—' }}</div>
                </div>
            </div>

            <!-- BOM TABLE -->
            <div class="flex-1 min-w-0 overflow-x-auto">
                <table class="bt">
                    <thead>
                        <tr>
                            <th style="width:230px;">Material / Item</th>
                            <th class="r" style="width:70px;"></th>
                            <th class="r" style="width:90px;">QTY</th>
                            <th class="r" style="width:72px;">Bags</th>
                            <th style="width:65px;">UOM</th>
                            <th class="r" style="width:95px;">COST (PHP)</th>
                            <th class="r" style="width:110px;">AMOUNT</th>
                            <th class="r" style="width:80px;">COST/KG</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Grand total row -->
                        <tr class="grand-total">
                            <td colspan="6">TOTAL COST</td>
                            <td class="r">{{ number_format(floor($grandTotal*100)/100,2) }}</td>
                            <td class="r">{{ $grandCpk ? number_format($grandCpk,2) : '—' }}</td>
                        </tr>

                        <!-- DOC row -->
                        @php $docCpk = $totalKg > 0 ? $docAmt / $totalKg : 0; @endphp
                        <tr class="cat-hd"><td colspan="8">Day Old Chick</td></tr>
                        <tr class="data-row">
                            <td style="padding-left:1.4rem;">Day Old Chick</td>
                            <td></td>
                            <td class="r">{{ number_format($house->loading_qty ?? 0) }}</td>
                            <td></td>
                            <td>Hds</td>
                            <td class="r">{{ number_format($house->doc_cost ?? 24, 2) }}</td>
                            <td class="r">{{ number_format(floor($docAmt*100)/100,2) }}</td>
                            <td class="r">{{ $docCpk ? number_format($docCpk,2) : '—' }}</td>
                        </tr>

                        @foreach($cats as $catKey => $catLabel)
                            @php
                                $catRows  = $mats->where('category', $catKey)->values();
                                $catAmt   = 0;
                                $feedBags = 0;
                                $feedKg   = 0;
                                foreach ($catRows as $_r) {
                                    if ($catKey === 'feed') {
                                        $catAmt += (float)($_r['qty_bags']??0) * (float)($_r['cost']??0);
                                    } elseif ($catKey === 'labor') {
                                        $q=(float)($_r['qty_kg']??0); $c=(float)($_r['cost']??0); $dy=(float)($_r['days']??0); $dv=(float)($_r['divisor']??0); $op=$_r['labor_op']??'';
                                        $a=$q*$c; if($dy){$a=$op==='divide'?($q*$c)/$dy:$q*$c*$dy;} if($dv)$a=$a/$dv;
                                        $catAmt += $a;
                                    } else {
                                        $q=(float)($_r['qty_kg']??0); $c=(float)($_r['cost']??0);
                                        $catAmt += ($catKey==='overhead' && strtolower($_r['uom']??'')==='houses' && $q) ? $c/$q : $q*$c;
                                    }
                                    $feedBags += (float)($_r['qty_bags']??0);
                                    $feedKg   += (float)($_r['qty_kg']??0);
                                }
                                $catCpk = $totalKg > 0 ? $catAmt / $totalKg : 0;
                                if ($catKey !== 'feed') { $feedBags = null; $feedKg = null; }
                                $isCdmSub = in_array($catKey, $cdmCats);
                            @endphp
                            @if($catKey === 'disinfectant')
                            @php
                                $disAmt = $mats->where('category','disinfectant')->sum(fn($r)=>(float)($r['qty_kg']??0)*(float)($r['cost']??0));
                                $cmAmt  = $mats->where('category','cleaning_material')->sum(fn($r)=>(float)($r['qty_kg']??0)*(float)($r['cost']??0));
                                $supAmt = $mats->where('category','supply')->sum(fn($r)=>(float)($r['qty_kg']??0)*(float)($r['cost']??0));
                                $cdmAmt = $cmAmt + $supAmt - $disAmt;
                                $cdmCpk = $totalKg > 0 ? $cdmAmt / $totalKg : 0;
                            @endphp
                            <tr class="cat-hd">
                                <td>Cleaning and Disinfection Mat</td><td></td><td></td><td></td><td></td><td></td>
                                <td class="r">{{ number_format(floor($cdmAmt*100)/100,2) }}</td>
                                <td class="r">{{ $cdmCpk ? number_format($cdmCpk,2) : '—' }}</td>
                            </tr>
                            @endif
                            <tr class="cat-hd"@if($isCdmSub) style="background:#2a4f7a;"@endif>
                                <td @if($isCdmSub) style="padding-left:1.2rem;" @endif>{{ $catLabel }}</td>
                                <td class="r" style="font-size:.6rem;white-space:nowrap;">{{ $catKey==='feed' ? 'Rec. Bags/Hds' : ($catKey==='labor' ? 'Days' : '') }}</td>
                                <td class="r" style="font-size:.6rem;white-space:nowrap;">{{ $catKey==='feed' ? 'KG' : 'QTY' }}</td>
                                <td class="r" style="font-size:.6rem;white-space:nowrap;">{{ $catKey==='feed' ? 'QTY' : ($catKey==='labor' ? '÷' : '') }}</td>
                                <td style="font-size:.6rem;white-space:nowrap;">UOM</td>
                                <td class="r" style="font-size:.6rem;white-space:nowrap;">{{ $catKey==='feed' ? '' : 'Cost' }}</td>
                                <td class="r">{{ number_format(floor($catAmt*100)/100,2) }}</td>
                                <td class="r">{{ $catCpk ? number_format($catCpk,2) : '—' }}</td>
                            </tr>
                            @foreach($catRows as $row)
                            @php
                                if ($catKey === 'labor') {
                                    $q=(float)($row['qty_kg']??0); $c=(float)($row['cost']??0); $dy=(float)($row['days']??0); $dv=(float)($row['divisor']??0); $op=$row['labor_op']??'';
                                    $amt=$q*$c; if($dy){$amt=$op==='divide'?($q*$c)/$dy:$q*$c*$dy;} if($dv)$amt=$amt/$dv;
                                } elseif ($catKey === 'feed') {
                                    $amt = (float)($row['qty_bags']??0) * (float)($row['cost']??0);
                                } else {
                                    $q = (float)($row['qty_kg']??0); $c = (float)($row['cost']??0);
                                    $amt = ($catKey==='overhead' && strtolower($row['uom']??'')==='houses' && $q) ? $c/$q : $q*$c;
                                }
                                $cpk = $totalKg > 0 && $amt > 0 ? $amt / $totalKg : 0;
                            @endphp
                            <tr class="data-row">
                                <td style="padding-left:{{ $isCdmSub ? '2.2' : '1.4' }}rem;">{{ $row['name'] ?? '—' }}</td>
                                <td class="r">{{ $row['days'] ?? '' }}</td>
                                <td class="r">{{ isset($row['qty_kg']) ? number_format((float)$row['qty_kg'],2) : '' }}</td>
                                <td class="r">{{ $catKey==='feed' && isset($row['qty_bags']) ? number_format((float)$row['qty_bags']) : ($catKey==='labor' && !empty($row['divisor']) ? '÷'.$row['divisor'] : '') }}</td>
                                <td>{{ $row['uom'] ?? '' }}</td>
                                <td class="r">{{ isset($row['cost']) ? number_format((float)$row['cost'],2) : '' }}</td>
                                <td class="r">{{ number_format(floor($amt*100)/100,2) }}</td>
                                <td class="r">{{ $catKey==='feed' ? '' : ($cpk ? number_format($cpk,2) : '0.00') }}</td>
                            </tr>
                            @endforeach
                            @if($catKey==='feed' && $feedKg)
                            <tr style="background:#f0f7ff;border-bottom:2px solid #bfdbfe;">
                                <td style="padding-left:1.4rem;font-size:.72rem;font-weight:700;color:#1e3a5f;">Total Kg Conversion</td>
                                <td class="r" style="font-size:.6rem;color:#6b7280;">Bags × 1,000</td>
                                <td class="r" style="font-weight:800;color:#1e3a5f;">{{ number_format($feedKg) }}</td>
                                <td colspan="5"></td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- APPROVAL & STATUS CONTROL -->
<div class="b p-4 mb-6">
    <!-- Approval Section -->
    <div class="flex items-center gap-4 flex-wrap mb-4 pb-4 border-b border-gray-100">
        <span class="text-sm font-semibold text-gray-700">Approval:</span>
        @if($bom->approved)
            <div class="flex items-center gap-3">
                <span class="badge badge-approved px-3 py-1"><i class="fas fa-check-circle mr-1"></i>Approved</span>
                <span class="text-xs text-gray-500">
                    by <strong>{{ $bom->approved_by }}</strong> on {{ $bom->approved_at->format('M d, Y h:i A') }}
                </span>
                <button type="button" id="unapproveBtn"
                    class="px-3 py-1.5 text-xs font-semibold rounded-md border border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100 transition">
                    <i class="fas fa-undo mr-1"></i> Revoke Approval
                </button>
            </div>
        @else
            <div class="flex items-center gap-3">
                <span class="badge badge-not-approved px-3 py-1"><i class="fas fa-clock mr-1"></i>Not Approved</span>
                <button type="button" id="approveBtn"
                    class="px-4 py-1.5 text-xs font-semibold rounded-md bg-green-600 text-white hover:bg-green-700 transition shadow-sm">
                    <i class="fas fa-check mr-1"></i> Approve BOM
                </button>
            </div>
        @endif
    </div>

    <!-- Status Section -->
    @if(!$bom->approved)
    <div class="flex items-center gap-4 flex-wrap">
        <span class="text-sm font-semibold text-gray-700">Change Status:</span>
        @foreach(['draft','active','complete','archived'] as $s)
        <form method="POST" action="{{ route('inhouse_bom.updateStatus', $bom) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="{{ $s }}">
            <button type="submit"
                class="px-3 py-1.5 text-xs font-semibold rounded-md border transition-all
                {{ $bom->status === $s ? 'bg-white text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' }}">
                {{ ucfirst($s) }}
            </button>
        </form>
        @endforeach
    </div>
    @else
    <div class="flex items-center gap-2 text-xs text-gray-400">
        <i class="fas fa-lock"></i> Status changes and editing are locked while BOM is approved.
    </div>
    @endif
</div>

<script>
const panels = document.querySelectorAll('.house-panel');
const tabs   = document.querySelectorAll('[id^="htab-"]');

function showHouse(idx) {
    panels.forEach((p,i) => p.classList.toggle('hidden', i !== idx));
    tabs.forEach((t,i)   => t.classList.toggle('active', i === idx));
    document.getElementById('active-house-lbl').textContent =
        tabs[idx] ? tabs[idx].textContent.trim() : '';
}
showHouse(0);

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
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}

function printBOM() {
    const printEl = document.getElementById('print-area');
    const w = window.open('', '_blank');
    w.document.write(printEl.innerHTML);
    w.document.close();
    w.focus();
    setTimeout(() => { w.print(); w.close(); }, 350);
}

// BOM Approval
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const approveBtn = document.getElementById('approveBtn');
if (approveBtn) {
    approveBtn.addEventListener('click', async function() {
        const result = await Swal.fire({
            title: 'Approve this BOM?',
            html: 'Once approved, this BOM <strong>cannot be edited or deleted</strong>.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: '<i class="fas fa-check mr-1"></i> Yes, Approve'
        });
        if (!result.isConfirmed) return;
        try {
            const res = await fetch(`{{ route('inhouse_bom.approve', $bom) }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Approved!', text: data.message, timer: 2000, showConfirmButton: false });
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to approve BOM.', 'error');
        }
    });
}

const unapproveBtn = document.getElementById('unapproveBtn');
if (unapproveBtn) {
    unapproveBtn.addEventListener('click', async function() {
        const result = await Swal.fire({
            title: 'Revoke Approval?',
            text: 'This will allow editing and deleting the BOM again.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ea580c',
            confirmButtonText: 'Yes, Revoke'
        });
        if (!result.isConfirmed) return;
        try {
            const res = await fetch(`{{ route('inhouse_bom.unapprove', $bom) }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Revoked', text: data.message, timer: 2000, showConfirmButton: false });
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to revoke approval.', 'error');
        }
    });
}
</script>

{{-- ═══ PRINT AREA (hidden, opened in new window) ═══ --}}
<div id="print-area" class="hidden">
<html><head><title>{{ $bom->cycle_ref }} — BOM Print</title>
<style>
@@media print { @@page { size: landscape; margin: 10mm 12mm; } }
body { font-family: Calibri, Arial, sans-serif; font-size: 10pt; color: #000; margin: 0; padding: 0; }
.page-break { page-break-after: always; }
.bom-sheet { width: 100%; max-width: 850px; margin: 0 auto; }

/* Header params table */
.params { border-collapse: collapse; width: 100%; margin-bottom: 6px; }
.params td { padding: 1px 6px; font-size: 10pt; vertical-align: middle; }
.params td.lbl { font-weight: 400; width: 200px; }
.params td.val { text-align: right; font-weight: 600; width: 120px; }
.params td.val-bold { text-align: right; font-weight: 700; }

/* House label */
.house-label { text-align: right; font-weight: 700; font-size: 10pt; margin: 2px 0 4px; }

/* BOM table */
.bom { border-collapse: collapse; width: 100%; font-size: 10pt; }
.bom th, .bom td { padding: 2px 6px; border: 1px solid #bbb; vertical-align: middle; }
.bom th { font-weight: 700; text-align: center; border-bottom: 2px solid #999; }
.bom td.r { text-align: right; }
.bom td.name { padding-left: 14px; }

/* Category rows - exact Excel colors */
.cat-blue { background: #156082; color: #fff; font-weight: 600; }
.cat-lightblue { background: #DCEAF7; font-weight: 600; }
.cat-gray { background: #E8E8E8; font-weight: 600; }
.cat-subhd { background: #f0f0f0; font-weight: 600; font-size: 9pt; }
.grand-total { background: #D0D0D0; font-weight: 700; }
</style>
</head><body>

@foreach($bom->houses as $hIdx => $house)
@php
    $cats = ['feed'=>'Feeds','supplement'=>'Supplements','vaccine'=>'Vaccine',
             'disinfectant'=>'Disinfectant','cleaning_material'=>'Cleaning Material','supply'=>'Supplies',
             'labor'=>'Labor','overhead'=>'Overhead'];
    $catStyles = ['feed'=>'cat-lightblue','supplement'=>'cat-lightblue','vaccine'=>'cat-lightblue',
                  'disinfectant'=>'cat-lightblue','cleaning_material'=>'cat-lightblue','supply'=>'cat-gray',
                  'labor'=>'cat-blue','overhead'=>'cat-blue'];
    $cdmCats = ['disinfectant','cleaning_material','supply'];
    $mats = collect($house->materials ?? []);
    $loading   = $house->loading_qty ?? 0;
    $livPct    = $house->livability ? $house->livability / 100 : 0;
    $livDisplay= $house->livability ? number_format($house->livability, 2) . '%' : '—';
    $alw       = $house->alw ?? 0;
    $harvest   = $house->harvest_qty ?? 0;
    $totalKg   = (float)($house->total_kg ?? 0);
    $fcr       = $house->fcr ?? 0;
    $feedReq   = (float)($house->feed_req_kg ?? 0);
    $docCost   = $house->doc_cost ?? 24;
    $docAmt    = $loading * $docCost;
    $grandTotal= (float)($house->total_cost ?? 0);
    $grandCpk  = $totalKg > 0 ? $grandTotal / $totalKg : 0;
    $bpi       = $house->bpi ? number_format((float)$house->bpi, 0) : '—';

    // Material totals per category
    $matAmt = $grandTotal - $docAmt;
@endphp
<div class="bom-sheet{{ $hIdx < count($bom->houses) - 1 ? ' page-break' : '' }}">

{{-- Header parameters (matches Excel rows 2-12) --}}
<table class="params">
    <tr><td class="lbl">Loading Quantity</td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ number_format($loading) }}</td></tr>
    <tr><td class="lbl">Livability</td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ $livDisplay }}</td></tr>
    <tr><td class="lbl">Harvest Qty, Heads</td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ number_format($harvest) }}</td></tr>
    <tr><td class="lbl">ALW</td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ $alw ? number_format($alw, 2) : '—' }}</td></tr>
    <tr><td class="lbl">Total kg</td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ number_format($totalKg, 2) }}</td></tr>
    <tr><td class="lbl">FCR</td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ $fcr ? number_format($fcr, 3) : '—' }}</td></tr>
    <tr><td class="lbl">Feed Requirement</td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ number_format($feedReq, 0) }}</td></tr>
    <tr><td class="lbl"><b>COST</b></td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val-bold">{{ $grandCpk ? number_format($grandCpk, 2) : '—' }}</td></tr>
    <tr><td class="lbl"><b>AGE</b></td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ $house->age_days ?? '—' }}</td></tr>
    <tr><td class="lbl"><b>BPI</b></td><td></td><td></td><td></td><td></td><td></td><td></td><td class="val">{{ $bpi }}</td></tr>
</table>

{{-- House label --}}
<div class="house-label">{{ $house->house_name ?: 'House ' . $house->house_number }}</div>

{{-- BOM Table (matches Excel: cols B-I) --}}
<table class="bom">
    <thead>
        <tr>
            <th style="width:200px;"></th>
            <th style="width:70px;"></th>
            <th style="width:60px;"></th>
            <th style="width:70px;">QTY</th>
            <th style="width:55px;">UOM</th>
            <th style="width:80px;">COST</th>
            <th style="width:100px;">AMOUNT</th>
            <th style="width:80px;">COST/KG</th>
        </tr>
    </thead>
    <tbody>
        {{-- TOTAL COST row (matches Excel row 15 — grand total first) --}}
        <tr class="grand-total">
            <td><b>TOTAL COST</b></td><td></td><td></td><td></td><td></td><td></td>
            <td class="r"><b>{{ number_format(floor($grandTotal*100)/100,2) }}</b></td>
            <td class="r"><b>{{ $grandCpk ? number_format($grandCpk, 2) : '—' }}</b></td>
        </tr>

        {{-- DOC row (matches Excel row 16) --}}
        @php $docCpk = $totalKg > 0 ? $docAmt / $totalKg : 0; @endphp
        <tr>
            <td>Day Old Chick</td><td></td><td></td>
            <td class="r">{{ number_format($loading) }}</td>
            <td>Hds</td>
            <td class="r">{{ number_format($docCost, 2) }}</td>
            <td class="r">{{ number_format(floor($docAmt*100)/100,2) }}</td>
            <td class="r">{{ $docCpk ? number_format($docCpk, 2) : '—' }}</td>
        </tr>

        {{-- Total Materials row (matches Excel row 17) --}}
        @php $matTotal = $grandTotal - $docAmt; $matCpk = $totalKg > 0 ? $matTotal / $totalKg : 0; @endphp
        <tr class="cat-blue">
            <td>Total Materials</td><td></td><td></td><td></td><td></td><td></td>
            <td class="r">{{ number_format(floor($matTotal*100)/100,2) }}</td>
            <td class="r">{{ $matCpk ? number_format($matCpk, 2) : '—' }}</td>
        </tr>

        @foreach($cats as $catKey => $catLabel)
            @php
                $catRows = $mats->where('category', $catKey)->values();
                $catAmt = 0;
                $feedKg = 0; $feedBags = 0;
                foreach ($catRows as $_r) {
                    if ($catKey === 'feed') {
                        $catAmt += (float)($_r['qty_bags']??0) * (float)($_r['cost']??0);
                    } elseif ($catKey === 'labor') {
                        $q=(float)($_r['qty_kg']??0); $c=(float)($_r['cost']??0); $dy=(float)($_r['days']??0); $dv=(float)($_r['divisor']??0); $op=$_r['labor_op']??'';
                        $a=$q*$c; if($dy){$a=$op==='divide'?($q*$c)/$dy:$q*$c*$dy;} if($dv)$a=$a/$dv;
                        $catAmt += $a;
                    } else {
                        $q=(float)($_r['qty_kg']??0); $c=(float)($_r['cost']??0);
                        $catAmt += ($catKey==='overhead' && strtolower($_r['uom']??'')==='houses' && $q) ? $c/$q : $q*$c;
                    }
                    $feedKg += (float)($_r['qty_kg']??0);
                    $feedBags += (float)($_r['qty_bags']??0);
                }
                $catCpk = $totalKg > 0 ? $catAmt / $totalKg : 0;
                $style = $catStyles[$catKey] ?? 'cat-lightblue';
                $isCdmSub = in_array($catKey, $cdmCats);
            @endphp
            @if($catKey === 'disinfectant')
            @php
                $disAmt = $mats->where('category','disinfectant')->sum(fn($r)=>(float)($r['qty_kg']??0)*(float)($r['cost']??0));
                $cmAmt  = $mats->where('category','cleaning_material')->sum(fn($r)=>(float)($r['qty_kg']??0)*(float)($r['cost']??0));
                $supAmt = $mats->where('category','supply')->sum(fn($r)=>(float)($r['qty_kg']??0)*(float)($r['cost']??0));
                $cdmAmt = $cmAmt + $supAmt - $disAmt;
                $cdmCpk = $totalKg > 0 ? $cdmAmt / $totalKg : 0;
            @endphp
            <tr class="cat-blue">
                <td>Cleaning and Disinfection Mat</td><td></td><td></td><td></td><td></td><td></td>
                <td class="r">{{ number_format(floor($cdmAmt*100)/100,2) }}</td>
                <td class="r">{{ $cdmCpk ? number_format($cdmCpk, 2) : '—' }}</td>
            </tr>
            @endif
            {{-- Category header (matches Excel column layout) --}}
            <tr class="{{ $style }}">
                <td @if($isCdmSub) style="padding-left:1rem;" @endif>{{ $catLabel }}</td>
                <td class="r" style="font-size:7pt;white-space:nowrap;">{{ $catKey === 'feed' ? 'Rec. Bags/Hds' : ($catKey === 'labor' ? 'Days' : '') }}</td>
                <td class="r" style="font-size:7pt;white-space:nowrap;">{{ $catKey === 'feed' ? 'KG' : '' }}</td>
                <td class="r" style="font-size:7pt;white-space:nowrap;">{{ $catKey === 'feed' ? 'QTY' : 'QTY' }}</td>
                <td style="font-size:7pt;white-space:nowrap;">{{ $catKey === 'feed' ? 'UOM' : 'UOM' }}</td>
                <td class="r" style="font-size:7pt;white-space:nowrap;">{{ $catKey === 'feed' ? '' : 'Cost' }}</td>
                <td class="r">{{ number_format(floor($catAmt*100)/100,2) }}</td>
                <td class="r">{{ $catCpk ? number_format($catCpk, 2) : '—' }}</td>
            </tr>
            {{-- Items (matches Excel columns B-I) --}}
            @foreach($catRows as $row)
                @php
                    if ($catKey === 'labor') {
                        $q=(float)($row['qty_kg']??0); $c=(float)($row['cost']??0); $dy=(float)($row['days']??0); $dv=(float)($row['divisor']??0); $op=$row['labor_op']??'';
                        $amt=$q*$c; if($dy){$amt=$op==='divide'?($q*$c)/$dy:$q*$c*$dy;} if($dv)$amt=$amt/$dv;
                    } elseif ($catKey === 'feed') {
                        $amt = (float)($row['qty_bags']??0) * (float)($row['cost']??0);
                    } else {
                        $q=(float)($row['qty_kg']??0); $c=(float)($row['cost']??0);
                        $amt = ($catKey==='overhead' && strtolower($row['uom']??'')==='houses' && $q) ? $c/$q : $q*$c;
                    }
                    $cpk = $totalKg > 0 && $amt > 0 ? $amt / $totalKg : 0;
                @endphp
                <tr>
                    <td class="name" @if($isCdmSub) style="padding-left:1.8rem;" @endif>{{ $row['name'] ?? '—' }}</td>
                    @if($catKey === 'feed')
                        {{-- Feed: C=days(rec bags/hds), D=qty_kg(KG), E=qty_bags(QTY), F=uom, G=cost, H=amount, I=(empty) --}}
                        <td class="r">{{ $row['days'] ?? '' }}</td>
                        <td class="r">{{ isset($row['qty_kg']) ? number_format((float)$row['qty_kg']) : '' }}</td>
                        <td class="r">{{ isset($row['qty_bags']) ? number_format((float)$row['qty_bags']) : '' }}</td>
                        <td>{{ $row['uom'] ?? '' }}</td>
                        <td class="r">{{ isset($row['cost']) ? number_format((float)$row['cost'], 2) : '' }}</td>
                        <td class="r">{{ number_format(floor($amt*100)/100,2) }}</td>
                        <td></td>
                    @elseif($catKey === 'labor')
                        {{-- Labor: C=days, D=(empty), E=qty, F=uom, G=cost, H=amount, I=cost/kg --}}
                        <td class="r">{{ $row['days'] ?? '' }}</td>
                        <td></td>
                        <td class="r">{{ isset($row['qty_kg']) ? number_format((float)$row['qty_kg']) : '' }}</td>
                        <td>{{ $row['uom'] ?? '' }}</td>
                        <td class="r">{{ isset($row['cost']) ? number_format((float)$row['cost'], 2) : '' }}</td>
                        <td class="r">{{ number_format(floor($amt*100)/100,2) }}</td>
                        <td class="r">{{ $cpk ? number_format($cpk, 2) : '0.00' }}</td>
                    @else
                        {{-- Others: C=(empty), D=(empty), E=qty, F=uom, G=cost, H=amount, I=cost/kg --}}
                        <td></td>
                        <td></td>
                        <td class="r">{{ isset($row['qty_kg']) ? number_format((float)$row['qty_kg']) : '' }}</td>
                        <td>{{ $row['uom'] ?? '' }}</td>
                        <td class="r">{{ isset($row['cost']) ? number_format((float)$row['cost'], 2) : '' }}</td>
                        <td class="r">{{ number_format(floor($amt*100)/100,2) }}</td>
                        <td class="r">{{ $cpk ? number_format($cpk, 2) : '0.00' }}</td>
                    @endif
                </tr>
            @endforeach
            @if($catKey === 'feed' && $feedKg)
                <tr style="background:#eaf3fb;">
                    <td style="padding-left:1.2rem;font-size:8pt;font-weight:700;color:#156082;">Total Kg Conversion</td>
                    <td class="r" style="font-size:7pt;color:#666;">Bags × 1,000</td>
                    <td class="r" style="font-weight:800;color:#156082;">{{ number_format($feedKg) }}</td>
                    <td colspan="5"></td>
                </tr>
            @endif
        @endforeach

        {{-- GRAND TOTAL (bottom) --}}
        <tr class="grand-total">
            <td><b>TOTAL COST</b></td><td></td><td></td><td></td><td></td><td></td>
            <td class="r"><b>{{ number_format(floor($grandTotal*100)/100,2) }}</b></td>
            <td class="r"><b>{{ $grandCpk ? number_format($grandCpk, 2) : '—' }}</b></td>
        </tr>
    </tbody>
</table>
</div>
@endforeach

</body></html>
</div>

@endsection