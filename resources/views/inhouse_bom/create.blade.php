@extends('layouts.app')

@section('title', !empty($parentBom) ? 'Extend BOM — '.$parentBom->cycle_ref : 'Create In-House BOM')

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.b-hd {
    font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em;
    color:#1e3a5f; padding:.6rem 1rem; border-bottom:1px solid #f0f4f8;
    background:linear-gradient(to right,#f0f7ff,#f8fafc);
    border-radius:.5rem .5rem 0 0; display:flex; align-items:center; justify-content:space-between;
}
.fi { display:flex; flex-direction:column; gap:.2rem; }
.fi label { font-size:.72rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
.fi input,.fi select,.fi textarea {
    padding:.38rem .6rem; border:1px solid #d1d5db; border-radius:.375rem;
    font-size:.83rem; color:#111827; background:#fff; transition:border-color .15s,box-shadow .15s;
}
.fi input:focus,.fi select:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12); }
.fi input[readonly] { background:#f9fafb; color:#6b7280; cursor:default; }

.kpi { background:#fff; border:1px solid #e5e7eb; border-radius:.45rem; padding:.75rem 1rem; }
.kpi-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; }
.kpi-val { font-size:1.3rem; font-weight:800; color:#111827; line-height:1.1; margin:.1rem 0; }
.kpi-sub { font-size:.68rem; color:#d1d5db; }
.kpi.accent { border-color:#bfdbfe; background:linear-gradient(135deg,#eff6ff,#fff); }
.kpi.accent .kpi-val { color:#1d4ed8; }

.htabs { display:flex; gap:.35rem; flex-wrap:wrap; padding:.65rem 1rem; border-bottom:1px solid #f0f4f8; }
.htab { padding:.28rem .8rem; border-radius:999px; font-size:.75rem; font-weight:600; border:1px solid #e5e7eb; color:#6b7280; background:#fff; cursor:pointer; transition:all .15s; }
.htab:hover { border-color:#93c5fd; color:#2563eb; }
.htab.filled { border-color:#86efac; color:#15803d; background:#f0fdf4; }
.htab.active { background:#1e3a5f !important; color:#fff !important; border-color:#1e3a5f !important; }

.param-panel { width:255px; min-width:255px; border-right:1px solid #f0f4f8; padding:1rem; display:flex; flex-direction:column; gap:.5rem; }
.derived-lbl { font-size:.68rem; font-weight:700; color:#93c5fd; text-transform:uppercase; letter-spacing:.04em; }

.bt { width:100%; border-collapse:collapse; font-size:.79rem; }
.bt thead th { background:#1e3a5f; color:#fff; padding:.48rem .6rem; font-size:.69rem; font-weight:600; text-align:left; white-space:nowrap; }
.bt thead th.r { text-align:right; }
.bt tbody tr { border-bottom:1px solid #f3f4f6; }
.bt tbody tr:hover { background:#f8fbff; }
.bt tr.grand-total td { background:#0f2744; color:#fff; font-weight:800; font-size:.82rem; padding:.55rem .6rem; }
.bt tr.cat-hd td { background:#1e3a5f; color:#e0f2fe; font-weight:700; padding:.42rem .6rem; font-size:.73rem; }
.bt tr.data-row td { padding:.36rem .6rem; color:#374151; }
.bt td.r { text-align:right; font-variant-numeric:tabular-nums; }

.ci { padding:.2rem .38rem; border:1px solid #e5e7eb; border-radius:.25rem; font-size:.78rem; color:#111827; background:#fff; text-align:right; transition:border-color .12s; }
.ci:focus { outline:none; border-color:#3b82f6; background:#eff6ff; }
.ci.nm { text-align:left; }

.add-btn { display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:600; padding:.26rem .6rem; border-radius:.3rem; border:1px dashed; cursor:pointer; transition:all .12s; }
.add-btn.blue   { border-color:#93c5fd; color:#2563eb; background:#eff6ff; }
.add-btn.blue:hover   { background:#dbeafe; }
.add-btn.indigo { border-color:#a5b4fc; color:#4338ca; background:#eef2ff; }
.add-btn.indigo:hover { background:#e0e7ff; }
.add-btn.teal   { border-color:#5eead4; color:#0f766e; background:#f0fdfa; }
.add-btn.teal:hover   { background:#ccfbf1; }
.add-btn.amber  { border-color:#fcd34d; color:#92400e; background:#fffbeb; }
.add-btn.amber:hover  { background:#fef3c7; }
.add-btn.rose   { border-color:#fca5a5; color:#991b1b; background:#fff1f2; }
.add-btn.rose:hover   { background:#ffe4e6; }
.del-btn { color:#f87171; cursor:pointer; font-size:.74rem; padding:0 .2rem; opacity:.55; }
.del-btn:hover { opacity:1; }
.sbadge { width:1.35rem; height:1.35rem; border-radius:50%; background:#1e3a5f; color:#fff; font-size:.63rem; font-weight:800; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-4">
    <div>
        <div class="text-sm text-gray-500 mb-1">
            <a href="{{ route('inhouse_bom.index') }}" class="hover:text-gray-300"><i class="fas fa-arrow-left mr-1"></i>BOM List</a>
        </div>
        <h2 class="text-xl font-bold text-white">Create In-House BOM</h2>
        <p class="text-xs text-gray-500 mt-0.5">Bill of Materials — Broiler Production Cycle</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('inhouse_bom.index') }}" class="px-3 py-2 text-sm border border-gray-600 rounded-md hover:bg-gray-900 text-gray-300">Cancel</a>
        <button onclick="submitBOM()" class="flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold shadow-sm">
            <i class="fas fa-save"></i> Save BOM
        </button>
    </div>
    
</div>

<form id="bom-form" method="POST" action="{{ route('inhouse_bom.store') }}">
    @csrf
    <input type="hidden" name="bom_payload" id="bom-payload">
    @if(!empty($parentBom))
    <input type="hidden" id="parent-bom-id" value="{{ $parentBom->id }}">
    @endif
</form>

@if($errors->any())
<div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-2.5 mb-4">
    <i class="fas fa-exclamation-circle text-red-500"></i>
    <div>
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
</div>
@endif

@if(!empty($parentBom))
<!-- Extension Banner -->
<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="bg-amber-100 text-amber-700 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h3 class="font-bold text-amber-800 text-sm">BOM Extension</h3>
                <p class="text-xs text-amber-600 mt-0.5">
                    Extending <strong>{{ $parentBom->cycle_ref }}</strong>
                    @if($parentBom->grower) · Grower: {{ $parentBom->grower }} @endif
                    · Original Cost: <strong>PHP {{ number_format($parentBom->total_cost, 2) }}</strong>
                </p>
            </div>
        </div>
        <a href="{{ route('inhouse_bom.show', $parentBom) }}"
           class="px-4 py-2 text-sm border border-amber-300 rounded-md hover:bg-amber-100 text-amber-700 font-medium">
            <i class="fas fa-eye mr-1"></i> View Original BOM
        </a>
    </div>
</div>
@endif

<!-- STEP 1: Cycle Info -->
<div class="b mb-4">
    <div class="b-hd"><span class="flex items-center gap-2"><span class="sbadge">1</span> Cycle Information</span></div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4">
        <div class="fi">
            <label>Cycle / Batch Ref <span class="text-red-700">*</span></label>
            <input type="text" id="inp-cycle-ref" value="{{ $nextRef ?? '' }}" placeholder="e.g. BOM-2026-001">
        </div>
        <div class="fi">
            <label>Date <span class="text-red-700">*</span></label>
            <input type="date" id="inp-cycle-date" value="{{ date('Y-m-d') }}">
        </div>
        <div class="fi">
            <label>Grower / Farm Manager</label>
            <input type="text" id="inp-grower" placeholder="e.g. Vinson / Eric">
        </div>
        <div class="fi">
            <label>No. of Houses</label>
            <input type="number" id="inp-num-houses" value="6" min="1" placeholder="e.g. 6" oninput="updateHouseTabs()">
        </div>
        <div class="fi md:col-span-4">
            <label>Remarks</label>
            <input type="text" id="inp-cycle-notes" placeholder="Optional...">
        </div>
    </div>
</div>

<!-- KPI STRIP -->
<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-2 mb-4">
    <div class="kpi"><div class="kpi-lbl">Loading Qty</div><div class="kpi-val" id="k-loading">—</div><div class="kpi-sub">heads</div></div>
    <div class="kpi"><div class="kpi-lbl">Livability</div><div class="kpi-val" id="k-liv">—</div><div class="kpi-sub">%</div></div>
    <div class="kpi"><div class="kpi-lbl">Harvest Qty</div><div class="kpi-val" id="k-harvest">—</div><div class="kpi-sub">heads</div></div>
    <div class="kpi"><div class="kpi-lbl">ALW</div><div class="kpi-val" id="k-alw">—</div><div class="kpi-sub">kg/head</div></div>
    <div class="kpi"><div class="kpi-lbl">FCR</div><div class="kpi-val" id="k-fcr">—</div><div class="kpi-sub">ratio</div></div>
    <div class="kpi"><div class="kpi-lbl">BPI</div><div class="kpi-val" id="k-bpi">—</div><div class="kpi-sub">index</div></div>
    <div class="kpi"><div class="kpi-lbl">COST / KG</div><div class="kpi-val" id="k-cpk">—</div><div class="kpi-sub">PHP / kg</div></div>
    <div class="kpi accent"><div class="kpi-lbl">Total Cost</div><div class="kpi-val" id="k-total">—</div><div class="kpi-sub">PHP</div></div>
</div>

<!-- STEP 2: House + BOM -->
<div class="b mb-4">
    <div class="b-hd">
        <span class="flex items-center gap-2"><span class="sbadge">2</span> House Parameters &amp; Bill of Materials</span>
        <span id="active-house-lbl" class="text-xs font-normal text-blue-600 normal-case tracking-normal">House 1</span>
    </div>
    <div class="htabs" id="houseTabs"></div>

    <div class="flex flex-col xl:flex-row">
        <!-- PARAMS -->
        <div class="param-panel flex-shrink-0">
            <div class="fi"><label>House Name / Grower</label>
                <input type="text" id="p-name" placeholder="House 1 (Vinson)" oninput="sf('name',this.value)">
            </div>
            <div class="fi"><label>Loading Quantity (heads)</label>
                <input type="number" id="p-loading" placeholder="40,000" oninput="calc()">
            </div>
            <div class="fi"><label>Livability (%)</label>
                <input type="number" id="p-liv" placeholder="97.00" step="0.01" oninput="calc()">
            </div>
            <div class="fi"><label>ALW (kg / head)</label>
                <input type="number" id="p-alw" placeholder="1.60" step="0.01" oninput="calc()">
            </div>
            <div class="fi"><label>FCR</label>
                <input type="number" id="p-fcr" placeholder="1.300" step="0.001" oninput="calc()">
            </div>
            <div class="fi"><label>Age (days)</label>
                <input type="number" id="p-age" placeholder="29" oninput="calc()">
            </div>
            <div class="fi"><label>BPI <span class="text-gray-500 font-normal normal-case" style="font-size:.62rem;">(auto-calculated, editable)</span></label>
                <input type="number" id="p-bpi" placeholder="412" step="1" oninput="onBpiManual()">
            </div>
            <hr class="border-gray-100">
            <div class="fi"><span class="derived-lbl">Harvest Qty, Heads</span>
                <input type="text" id="p-harvest" readonly placeholder="—">
            </div>
            <div class="fi"><span class="derived-lbl">Total KG</span>
                <input type="text" id="p-totalkg" readonly placeholder="—">
            </div>
            <div class="fi"><span class="derived-lbl">Feed Req. (KG)</span>
                <input type="text" id="p-feedreq" readonly placeholder="—">
            </div>
            <hr class="border-gray-100">
            <div class="fi"><span class="derived-lbl">Cost of Feeds</span>
                <input type="text" id="p-cost-feeds" readonly placeholder="—">
            </div>
            <div class="fi"><span class="derived-lbl">Cost of Day Old Chick</span>
                <input type="text" id="p-cost-doc" readonly placeholder="—">
            </div>
            <div class="fi"><span class="derived-lbl">Cost of Total Materials</span>
                <input type="text" id="p-cost-total" readonly placeholder="—">
            </div>
        </div>

        <!-- TABLE -->
        <div class="flex-1 min-w-0 overflow-x-auto">
            <table class="bt">
                <thead>
                    <tr>
                        <th style="width:215px;">Material / Item</th>
                        <th class="r" style="width:70px;"></th>
                        <th class="r" style="width:90px;">QTY</th>
                        <th class="r" style="width:72px;">Bags</th>
                        <th style="width:65px;">UOM</th>
                        <th class="r" style="width:95px;">COST (PHP)</th>
                        <th class="r" style="width:100px;">AMOUNT</th>
                        <th class="r" style="width:72px;">COST/KG</th>
                        <th style="width:28px;"></th>
                    </tr>
                </thead>
                <tbody id="bom-tbody"></tbody>
            </table>
            <div class="flex items-center gap-2 p-3 border-t border-gray-100 flex-wrap">
                <button type="button" onclick="addRow('feed')"       class="add-btn blue">  <i class="fas fa-plus"></i> Feed</button>
                <button type="button" onclick="addRow('supplement')" class="add-btn indigo"><i class="fas fa-plus"></i> Supplement</button>
                <button type="button" onclick="addRow('vaccine')"    class="add-btn teal">  <i class="fas fa-plus"></i> Vaccine</button>
                <button type="button" onclick="addRow('cleaning_material')" class="add-btn amber"> <i class="fas fa-plus"></i> Cleaning</button>
                <button type="button" onclick="addRow('supply')"     class="add-btn rose">  <i class="fas fa-plus"></i> Supply</button>
                <button type="button" onclick="addRow('labor')"      class="add-btn blue">  <i class="fas fa-plus"></i> Labor</button>
                <button type="button" onclick="addRow('overhead')"   class="add-btn indigo"><i class="fas fa-plus"></i> Overhead</button>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-between items-center mb-6">
    <p class="text-xs text-gray-500"><i class="fas fa-info-circle mr-1"></i>All house data is saved together in one BOM cycle.</p>
    <div class="flex gap-2">
        <a href="{{ route('inhouse_bom.index') }}" class="px-4 py-2 text-sm border border-gray-600 rounded-md hover:bg-gray-900 text-gray-300">Cancel</a>
        <button onclick="submitBOM()" class="flex items-center gap-1.5 px-5 py-2 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold shadow-sm">
            <i class="fas fa-save"></i> Save BOM
        </button>
    </div>
</div>

<script>
const CATS = ['feed','supplement','vaccine','disinfectant','cleaning_material','supply','labor','overhead'];
const CAT_LABELS = {
    feed:'Feeds', supplement:'Supplements', vaccine:'Vaccine',
    disinfectant:'Disinfectant', cleaning_material:'Cleaning Material', supply:'Supplies',
    labor:'Labor', overhead:'Overhead'
};
// Sub-categories grouped under "Cleaning and Disinfection Mat"
const CDM_CATS = ['disinfectant','cleaning_material','supply'];

// Default rows matching the PDF exactly
const DEFAULTS = {
    feed: [
        { name:'Booster (Crumble)', days:8,  qty_kg:320000, qty_bags:320, uom:'Bags', cost:1870.00 },
        { name:'Starter',           days:16, qty_kg:640000, qty_bags:640, uom:'Bags', cost:1830.00 },
        { name:'Grower',            days:16, qty_kg:655000, qty_bags:655, uom:'Bags', cost:1770.00 },
    ],
    supplement: [
        { name:'Electrolytes - JCS (1 liter/bottle)',               days:'', qty_kg:1,  qty_bags:'', uom:'Liter', cost:195.00   },
        { name:'Enrofloxacin - JCS (1 liter/bottle)',               days:'', qty_kg:6,  qty_bags:'', uom:'Liter', cost:1395.00  },
        { name:'Multi Vitamins + Amino Acids - (1 liter/bottle)',   days:'', qty_kg:36, qty_bags:'', uom:'Liter', cost:725.00   },
        { name:'Vitamin C plus Electrolytes - JCS (1 liter/bottle)',days:'', qty_kg:1,  qty_bags:'', uom:'Liter', cost:495.00   },
        { name:'Vitamin E + Selenium - JCS (1 liter/bottle)',       days:'', qty_kg:1,  qty_bags:'', uom:'Liter', cost:680.00   },
        { name:'Amprolium Hydrochloride (20%) - 1 liter/bottle',    days:'', qty_kg:7,  qty_bags:'', uom:'Liter', cost:1060.00  },
        { name:'Tylosin Tartrate (20%) - 2kg/bottle',               days:'', qty_kg:18, qty_bags:'', uom:'kg',    cost:1550.00  },
        { name:'Livertonic - Digest Sea Still (1 liter/bottle)',    days:'', qty_kg:11, qty_bags:'', uom:'Liter', cost:1524.22  },
        { name:'Chlorine',                                           days:'', qty_kg:11, qty_bags:'', uom:'KG',    cost:166.67   },
    ],
    vaccine: [
        { name:'Lasota Plain (1000 dose)',  days:'', qty_kg:40, qty_bags:'', uom:'vial', cost:215.00 },
        { name:'Tabic MB (2000 dose)',      days:'', qty_kg:20, qty_bags:'', uom:'vial', cost:536.67 },
        { name:'Vac-Safe Dechlor Agent',   days:'', qty_kg:10, qty_bags:'', uom:'Bot',  cost:69.20  },
    ],
    disinfectant: [
        { name:'Lasto-zide',      days:'', qty_kg:15, qty_bags:'', uom:'Liter', cost:1300.00 },
        { name:'Liquid Formalin', days:'', qty_kg:15, qty_bags:'', uom:'Liter', cost:494.00  },
        { name:'Glutaraldehyde',  days:'', qty_kg:15, qty_bags:'', uom:'Liter', cost:2200.00 },
    ],
    cleaning_material: [
        { name:'Linol Detergent', days:'', qty_kg:5,  qty_bags:'', uom:'L',     cost:58.93   },
    ],
    supply: [
        { name:'Ricehull',    days:'', qty_kg:16000, qty_bags:'', uom:'kgs',   cost:6.00    },
        { name:'Chick Paper', days:'', qty_kg:250,   qty_bags:'', uom:'kgs',   cost:35.00   },
        { name:'Gas',         days:'', qty_kg:12,    qty_bags:'', uom:'tanks', cost:3291.00 },
    ],
    labor: [
        { name:'Area Manager',      days:20, qty_kg:1,  qty_bags:'', uom:'pax', cost:110000.00, labor_op:'divide', divisor:'' },
        { name:'Manager/Supervisor',days:10, qty_kg:1,  qty_bags:'', uom:'pax', cost:50000.00,  labor_op:'divide', divisor:'' },
        { name:'Flockman',          days:45, qty_kg:3,  qty_bags:'', uom:'pax', cost:750.00,    labor_op:'multiply', divisor:'' },
        { name:'Cook',              days:45, qty_kg:1,  qty_bags:'', uom:'pax', cost:600.00,    labor_op:'multiply', divisor:4 },
        { name:'Maintenance 1',     days:45, qty_kg:1,  qty_bags:'', uom:'pax', cost:600.00,    labor_op:'multiply', divisor:6 },
        { name:'Leadman',           days:'', qty_kg:1,  qty_bags:'', uom:'pax', cost:40000.00,  labor_op:'', divisor:4 },
        { name:'Food Allowance',    days:45, qty_kg:6,  qty_bags:'', uom:'pax', cost:120.00,    labor_op:'multiply', divisor:'' },
        { name:'Cleaning',          days:2,  qty_kg:15, qty_bags:'', uom:'pax', cost:870.00,    labor_op:'multiply', divisor:'' },
        { name:'Harvest',           days:1,  qty_kg:45, qty_bags:'', uom:'pax', cost:870.00,    labor_op:'multiply', divisor:'' },
        { name:'Manure Collection', days:'', qty_kg:1,  qty_bags:'', uom:'pax', cost:25000.00,  labor_op:'', divisor:'' },
    ],
    overhead: [
        { name:'Electricity',          days:'', qty_kg:1,     qty_bags:'', uom:'Houses', cost:50000.00 },
        { name:'Diesel/Genset',        days:'', qty_kg:2,     qty_bags:'', uom:'Houses', cost:10000.00 },
        { name:'CG Cost - Floor Type', days:'', qty_kg:35000, qty_bags:'', uom:'bird',   cost:10.22    },
    ],
};

// State
let numHouses=6, currentHouse=1, houseData={};

function initHouse(h) {
    if (houseData[h]) return;
    houseData[h] = { name:'', loading:null, liv:null, alw:null, fcr:null, age:null, bpi:null, _bpiManual:false, docCost:24.00, _totalKg:0, _cpk:0, mats:{} };
    CATS.forEach(c => { houseData[h].mats[c] = (DEFAULTS[c]||[]).map(r=>({...r})); });
}
for(let h=1;h<=numHouses;h++) initHouse(h);

// Tabs
let _houseTabTimer=null;
function updateHouseTabs() {
    clearTimeout(_houseTabTimer);
    _houseTabTimer = setTimeout(() => {
    numHouses = parseInt(document.getElementById('inp-num-houses').value);
    for(let h=1;h<=numHouses;h++) initHouse(h);
    buildTabs();
    if(currentHouse>numHouses) currentHouse=1;
    activateHouse(currentHouse);
    }, 400);
}
function buildTabs() {
    const c = document.getElementById('houseTabs'); c.innerHTML='';
    for(let h=1;h<=numHouses;h++) {
        const d=houseData[h], filled=d.loading||d.name;
        const btn=document.createElement('button'); btn.type='button';
        btn.className='htab'+(filled?' filled':'')+(h===currentHouse?' active':'');
        btn.textContent=d.name?d.name.split('(')[0].trim():`House ${h}`;
        btn.onclick=()=>switchHouse(btn,h); c.appendChild(btn);
    }
}
function switchHouse(btn,h) {
    saveCurrent(); currentHouse=h;
    document.querySelectorAll('.htab').forEach(t=>t.classList.remove('active'));
    btn.classList.add('active'); activateHouse(h);
}
function activateHouse(h) {
    const d=houseData[h];
    document.getElementById('active-house-lbl').textContent=d.name||`House ${h}`;
    loadParams(h); calc();
}

// Params
function sf(field,val) { houseData[currentHouse][field]=val; buildTabs(); }
function saveCurrent() {
    const d=houseData[currentHouse];
    d.name=gv('p-name'); d.loading=fv('p-loading'); d.liv=fv('p-liv');
    d.alw=fv('p-alw'); d.fcr=fv('p-fcr'); d.age=fv('p-age');
    if(d._bpiManual) d.bpi=fv('p-bpi')||null;
}
function loadParams(h) {
    const d=houseData[h];
    sv('p-name',d.name??''); sv('p-loading',d.loading??''); sv('p-liv',d.liv??'');
    sv('p-alw',d.alw??''); sv('p-fcr',d.fcr??''); sv('p-age',d.age??'');
    sv('p-bpi',d.bpi??'');
}

// Called when user manually types in BPI field
function onBpiManual() {
    const d=houseData[currentHouse];
    const v=fv('p-bpi');
    d._bpiManual=true;
    d.bpi=v||null;
    el('k-bpi').textContent=v?v.toFixed(0):'—';
}

// Calc — applies Excel formulas:
// Harvest = Loading × Livability%
// Total kg = Harvest × ALW
// Feed Req = Total kg × FCR
// BPI = (Livability × ALW) / (Age × FCR) × 100  (auto-filled, user can override)
// Cost/kg = Grand Total / Total kg
function calc() {
    const loading=fv('p-loading'), liv=fv('p-liv'), alw=fv('p-alw'), fcr=fv('p-fcr'), age=fv('p-age');
    const d=houseData[currentHouse];
    d.loading=loading||null; d.liv=liv||null; d.alw=alw||null; d.fcr=fcr||null; d.age=age||null;

    const harvest=(loading&&liv)?Math.round(loading*liv/100):0;
    const totalKg=harvest*alw;
    const feedReqKg=totalKg*fcr;

    // BPI auto-calc (only if user hasn't manually overridden)
    if(!d._bpiManual) {
        const bpi=(liv&&alw&&age&&fcr)?((liv*alw)/(age*fcr))*100:0;
        d.bpi=bpi?Math.ceil(bpi):null;
        sv('p-bpi',bpi?Math.ceil(bpi):'');
    }

    sv('p-harvest',harvest?N(harvest):'');
    sv('p-totalkg',totalKg?N(totalKg,2):'');
    sv('p-feedreq',feedReqKg?N(feedReqKg,2):'');

    // KPI strip
    el('k-loading').textContent=loading?N(loading):'—';
    el('k-liv').textContent=liv?liv+'%':'—';
    el('k-harvest').textContent=harvest?N(harvest):'—';
    el('k-alw').textContent=alw?alw.toFixed(2):'—';
    el('k-fcr').textContent=fcr?fcr.toFixed(3):'—';
    el('k-bpi').textContent=d.bpi?d.bpi.toFixed(0):'—';

    d._totalKg=totalKg; buildTabs(); renderTable();
}

// Table
function rowAmt(r,cat) {
    if(cat==='feed') return (parseInt(r.qty_bags)||0)*(parseFloat(r.cost)||0);
    if(cat==='labor') {
        const qty=parseFloat(r.qty_kg)||0, cost=parseFloat(r.cost)||0, days=parseFloat(r.days)||0, div=parseFloat(r.divisor)||0;
        let amt=qty*cost;
        if(days){ amt=r.labor_op==='divide'?(qty*cost)/days:qty*cost*days; }
        if(div) amt=amt/div;
        return amt;
    }
    const qty=parseFloat(r.qty_kg)||0, cost=parseFloat(r.cost)||0;
    // Electricity & Diesel: Cost / Houses (qty_kg = num houses)
    if(cat==='overhead'&&(r.uom||'').toLowerCase()==='houses'&&qty) return cost/qty;
    return qty*cost;
}

function renderTable() {
    const d=houseData[currentHouse];
    const loading=fv('p-loading'), totalKg=d._totalKg||0, docCost=d.docCost||24;
    const docAmt=loading*docCost;
    const catTots={};
    CATS.forEach(c=>{ catTots[c]=(d.mats[c]||[]).reduce((s,r)=>s+rowAmt(r,c),0); });
    const cdmAmt=(catTots['cleaning_material']||0)+(catTots['supply']||0)-(catTots['disinfectant']||0);
    const grandTotal=docAmt+(catTots['feed']||0)+(catTots['supplement']||0)+(catTots['vaccine']||0)+cdmAmt;
    const grandCpk=totalKg>0?grandTotal/totalKg:0;
    el('k-total').textContent=grandTotal?'PHP '+N(grandTotal,2):'—';
    el('k-cpk').textContent=grandCpk?grandCpk.toFixed(2):'—';

    // Cost breakdown: amount / total kg
    const feedAmt=catTots['feed']||0;
    const costFeeds=totalKg>0?feedAmt/totalKg:0;
    const costDoc=totalKg>0?docAmt/totalKg:0;
    const costTotal=grandCpk;
    sv('p-cost-feeds',costFeeds>0.01?costFeeds.toFixed(2):'');
    sv('p-cost-doc',costDoc>0.01?costDoc.toFixed(2):'');
    sv('p-cost-total',costTotal>0.01?costTotal.toFixed(2):'');

    let html='';
    // Grand total
    html+=`<tr class="grand-total"><td colspan="5">TOTAL COST</td><td></td><td class="r">${N(grandTotal,2)}</td><td class="r">${grandCpk?grandCpk.toFixed(2):'—'}</td><td></td></tr>`;
    // DOC
    const docCpk=totalKg>0?docAmt/totalKg:0;
    html+=`<tr class="cat-hd"><td colspan="4">Day Old Chick</td><td>Hds</td><td></td><td class="r">${N(docAmt,2)}</td><td class="r">${docCpk?docCpk.toFixed(2):'—'}</td><td></td></tr>`;
    html+=`<tr class="data-row"><td style="padding-left:1.4rem;">Day Old Chick</td><td></td><td class="r"><input class="ci" style="width:75px;" value="${N(loading)}" readonly></td><td></td><td>Hds</td>
        <td class="r"><input class="ci" style="width:82px;" value="${docCost.toFixed(2)}" onchange="houseData[currentHouse].docCost=parseFloat(this.value)||0;renderTable()"></td>
        <td class="r">${N(docAmt,2)}</td><td class="r">${docCpk?docCpk.toFixed(2):'—'}</td><td></td></tr>`;

    CATS.forEach(cat=>{
        // CDM parent header: insert before first sub-category
        if(cat==='disinfectant'){
            const cdmAmt=(catTots['cleaning_material']||0)+(catTots['supply']||0)-(catTots['disinfectant']||0);
            const cdmCpk=totalKg>0?cdmAmt/totalKg:0;
            html+=`<tr class="cat-hd">
                <td>Cleaning and Disinfection Mat</td><td></td><td></td><td></td><td></td><td></td>
                <td class="r">${N(cdmAmt,2)}</td>
                <td class="r">${cdmCpk?cdmCpk.toFixed(2):'—'}</td><td></td></tr>`;
        }
        const rows=d.mats[cat]||[], catAmt=catTots[cat], catCpk=totalKg>0?catAmt/totalKg:0;
        const feedBagTot=cat==='feed'?rows.reduce((s,r)=>s+(parseInt(r.qty_bags)||0),0):null;
        const feedKgTot=cat==='feed'?rows.reduce((s,r)=>s+(parseFloat(r.qty_kg)||0),0):null;
        const isCdmSub=CDM_CATS.includes(cat);
        html+=`<tr class="cat-hd"${isCdmSub?' style="background:#2a4f7a;"':''}>
            <td${isCdmSub?' style="padding-left:1.2rem;"':''}>${CAT_LABELS[cat]}</td>
            <td class="r" style="font-size:.6rem;white-space:nowrap;">${cat==='feed'?'Rec. Bags/Hds':cat==='labor'?'Days':''}</td>
            <td class="r" style="font-size:.6rem;white-space:nowrap;">${cat==='feed'?'KG':'QTY'}</td>
            <td class="r" style="font-size:.6rem;white-space:nowrap;">${cat==='feed'?'QTY':cat==='labor'?'Op / ÷':''}</td>
            <td style="font-size:.6rem;white-space:nowrap;">UOM</td>
            <td class="r" style="font-size:.6rem;white-space:nowrap;">${cat==='feed'?'':'Cost'}</td>
            <td class="r">${N(catAmt,2)}</td>
            <td class="r">${catCpk?catCpk.toFixed(2):'—'}</td><td></td></tr>`;
        rows.forEach((r,i)=>{
            const amt=rowAmt(r,cat), cpk=totalKg>0&&amt>0?amt/totalKg:0;
            const isLabor=cat==='labor';
            html+=`<tr class="data-row" data-cat="${cat}" data-idx="${i}">
                <td style="padding-left:${isCdmSub?'2.2':'1.4'}rem;"><input class="ci nm" style="width:200px;" value="${esc(r.name)}" onchange="updateMat('${cat}',${i},'name',this.value)"></td>
                <td class="r">${isLabor
                    ?`<input class="ci" style="width:40px;" value="${r.days??''}" onchange="updateMat('${cat}',${i},'days',this.value);renderTable()" placeholder="days">`
                    :`<input class="ci" style="width:40px;" value="${r.days??''}" onchange="updateMat('${cat}',${i},'days',this.value)">`}</td>
                <td class="r">${cat==='feed'
                    ?`<input class="ci" style="width:75px;background:#f0f7ff;color:#1e3a5f;font-weight:700;" value="${r.qty_kg??''}" readonly title="Auto: Bags × 1,000">`
                    :`<input class="ci" style="width:75px;" value="${r.qty_kg??''}" onchange="updateMat('${cat}',${i},'qty_kg',this.value);renderTable()">`}</td>
                <td class="r">${cat==='feed'?`<input class="ci" style="width:58px;" value="${r.qty_bags??''}" onchange="updateMat('${cat}',${i},'qty_bags',this.value);renderTable()">`
                    :isLabor?`<span style="display:flex;align-items:center;gap:2px;justify-content:flex-end;">
                        <select class="ci" style="width:38px;font-size:.65rem;padding:1px 2px;" onchange="updateMat('${cat}',${i},'labor_op',this.value);renderTable()">
                            <option value=""${!r.labor_op?' selected':''}>—</option>
                            <option value="multiply"${r.labor_op==='multiply'?' selected':''}>×</option>
                            <option value="divide"${r.labor_op==='divide'?' selected':''}>÷</option>
                        </select>
                        <input class="ci" style="width:30px;font-size:.7rem;" value="${r.divisor??''}" onchange="updateMat('${cat}',${i},'divisor',this.value);renderTable()" placeholder="÷" title="Divisor">
                    </span>`:''}</td>
                <td><input class="ci nm" style="width:58px;" value="${esc(r.uom)}" onchange="updateMat('${cat}',${i},'uom',this.value)"></td>
                <td class="r"><input class="ci" style="width:82px;" value="${parseFloat(r.cost||0).toFixed(2)}" onchange="updateMat('${cat}',${i},'cost',this.value);renderTable()"></td>
                <td class="r">${N(amt,2)}</td>
                <td class="r">${cat==='feed'?'':cpk?cpk.toFixed(2):'0.00'}</td>
                <td><span class="del-btn" onclick="delRow('${cat}',${i})" title="Remove"><i class="fas fa-times"></i></span></td>
            </tr>`;
        });
        if(cat==='feed'&&feedKgTot){
            html+=`<tr style="background:#f0f7ff;border-bottom:2px solid #bfdbfe;">
                <td style="padding-left:1.4rem;font-size:.72rem;font-weight:700;color:#1e3a5f;">Total Kg Conversion</td>
                <td class="r" style="font-size:.6rem;color:#6b7280;">Bags × 1,000</td>
                <td class="r" style="font-weight:800;color:#1e3a5f;">${N(feedKgTot)}</td>
                <td colspan="6"></td>
            </tr>`;
        }
    });
    document.getElementById('bom-tbody').innerHTML=html;
}

function updateMat(cat,idx,field,val) {
    houseData[currentHouse].mats[cat][idx][field]=val;
    // Auto-calc: feed bags × 1000 = kg
    if(cat==='feed' && field==='qty_bags') {
        const bags=parseInt(val)||0;
        houseData[currentHouse].mats[cat][idx]['qty_kg']=bags*1000;
    }
}
function delRow(cat,idx) { houseData[currentHouse].mats[cat].splice(idx,1); renderTable(); }
function addRow(cat) {
    const uomMap={feed:'Bags',labor:'pax',overhead:'Houses',vaccine:'vial',supply:'kgs',disinfectant:'Liter',cleaning_material:'L'};
    const row={name:'',days:'',qty_kg:0,qty_bags:'',uom:uomMap[cat]||'unit',cost:0};
    if(cat==='labor'){row.labor_op='multiply';row.divisor='';}
    houseData[currentHouse].mats[cat].push(row);
    renderTable();
    setTimeout(()=>{ const rows=document.querySelectorAll(`[data-cat="${cat}"]`); if(rows.length){const inp=rows[rows.length-1].querySelector('.ci.nm');if(inp)inp.focus();}},50);
}



function el(id){return document.getElementById(id);}
function gv(id){return el(id)?el(id).value.trim():'';}
function fv(id){return parseFloat(gv(id))||0;}
function sv(id,v){if(el(id))el(id).value=v;}
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;');}
function N(n,d=0){const x=parseFloat(n);if(isNaN(x))return'—';const t=Math.trunc(x*Math.pow(10,d))/Math.pow(10,d);return t.toLocaleString('en-PH',{minimumFractionDigits:d,maximumFractionDigits:d});}

function submitBOM() {
    saveCurrent();

    const cycleRef  = gv('inp-cycle-ref');
    const cycleDate = gv('inp-cycle-date');

    if (!cycleRef) { Swal.fire('Error','Cycle / Batch Ref is required.','error'); return; }
    if (!cycleDate) { Swal.fire('Error','Date is required.','error'); return; }

    const houses = {};
    for (let h = 1; h <= numHouses; h++) {
        const d = houseData[h];
        if (!d.loading && !d.name) continue;
        houses[h] = {
            name:    d.name || '',
            loading: d.loading || 0,
            liv:     d.liv || 0,
            alw:     d.alw || 0,
            fcr:     d.fcr || 0,
            age:     d.age || 0,
            bpi:     d.bpi || null,
            docCost: d.docCost || 24,
            mats:    d.mats || {}
        };
    }

    const payload = {
        cycle_ref:  cycleRef,
        cycle_date: cycleDate,
        grower:     gv('inp-grower'),
        notes:      gv('inp-cycle-notes'),
        num_houses: numHouses,
        houses:     houses,
        parent_bom_id: document.getElementById('parent-bom-id')?.value || null
    };

    document.getElementById('bom-payload').value = JSON.stringify(payload);
    document.getElementById('bom-form').submit();
}

@if(!empty($parentBom) && !empty($parentHouses))
// ── Pre-fill from parent BOM for extension ──
(function() {
    const parentHouses = @json($parentHouses);
    const houseNums = Object.keys(parentHouses);
    const parentNumHouses = houseNums.length;

    // Set number of houses
    numHouses = parentNumHouses;
    document.getElementById('inp-num-houses').value = parentNumHouses;

    // Pre-fill grower
    const growerVal = @json($parentBom->grower ?? '');
    if (growerVal) document.getElementById('inp-grower').value = growerVal;

    // Pre-fill notes
    document.getElementById('inp-cycle-notes').value = 'Extension of {{ $parentBom->cycle_ref }}';

    // Override houseData with parent data
    houseData = {};
    houseNums.forEach(hNum => {
        const h = parseInt(hNum);
        const ph = parentHouses[hNum];
        houseData[h] = {
            name: ph.name || '',
            loading: ph.loading || null,
            liv: ph.liv || null,
            alw: ph.alw || null,
            fcr: ph.fcr || null,
            age: ph.age || null,
            bpi: ph.bpi || null,
            _bpiManual: !!ph.bpi,
            docCost: ph.docCost || 24,
            _totalKg: 0,
            _cpk: 0,
            mats: {}
        };
        // Fill materials from parent
        CATS.forEach(c => {
            if (ph.mats && ph.mats[c] && ph.mats[c].length) {
                houseData[h].mats[c] = ph.mats[c].map(m => ({...m}));
            } else {
                houseData[h].mats[c] = [];
            }
        });
    });
})();
@endif

buildTabs(); activateHouse(1);
</script>
@endsection