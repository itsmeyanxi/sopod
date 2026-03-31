@extends('layouts.app')

@section('title', 'Log Daily Feed Usage')

@section('content')
<style>
.b { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.b-hd { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#1e3a5f; padding:.6rem 1rem; border-bottom:1px solid #f0f4f8; background:linear-gradient(to right,#f0f7ff,#f8fafc); border-radius:.5rem .5rem 0 0; }
.mat-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.mat-table thead th { background:#1e3a5f; color:#fff; padding:.45rem .6rem; font-size:.7rem; font-weight:600; text-align:left; white-space:nowrap; }
.mat-table thead th.r { text-align:right; }
.mat-table tbody tr { border-bottom:1px solid #f3f4f6; }
.mat-table tbody td { padding:.4rem .6rem; color:#374151; vertical-align:middle; }
.mat-table tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }
.cat-hd td { background:#eef5ff; font-weight:700; color:#1e3a5f; font-size:.74rem; }
.usage-input { width:90px; padding:.25rem .4rem; border:1px solid #d1d5db; border-radius:.3rem; text-align:right; font-size:.82rem; }
.usage-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.15); }
.remaining { font-size:.68rem; color:#6b7280; }
.remaining.low { color:#dc2626; font-weight:600; }
</style>

<div class="max-w-5xl mx-auto">
    <!-- HEADER -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-sm text-gray-300 mb-1">
                <a href="{{ route('daily_feed_usage.index') }}" class="hover:text-gray-300"><i class="fas fa-arrow-left mr-1"></i>Usage List</a>
            </div>
            <h2 class="text-xl font-bold text-white">Log Daily Feed Usage</h2>
            <p class="text-xs text-gray-300 mt-0.5">Record how much material was used today from an approved BOM</p>
        </div>
    </div>

    <form method="POST" action="{{ route('daily_feed_usage.store') }}" id="usageForm">
        @csrf
        <input type="hidden" name="materials" id="materialsJson">

        <!-- Step 1: Select BOM & House -->
        <div class="b mb-4">
            <div class="b-hd">Select BOM & House</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
                <div>
                    <label class="block text-xs text-gray-300 font-semibold mb-1">BOM Cycle <span class="text-red-500">*</span></label>
                    <select name="bom_id" id="bomSelect" required
                        class="w-full bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-sm text-gray-200">
                        <option value="">— Select Approved BOM —</option>
                        @foreach($boms as $bom)
                            <option value="{{ $bom->id }}" {{ ($selectedBom && $selectedBom->id == $bom->id) ? 'selected' : '' }}>
                                {{ $bom->cycle_ref }} — {{ $bom->grower }} ({{ $bom->cycle_date->format('M d, Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-300 font-semibold mb-1">House <span class="text-red-500">*</span></label>
                    <select name="house_number" id="houseSelect" required
                        class="w-full bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-sm text-gray-200">
                        <option value="">— Select House —</option>
                        @if($selectedBom)
                            @foreach($houses as $house)
                                <option value="{{ $house->house_number }}">
                                    {{ $house->house_name ?: 'House '.$house->house_number }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-300 font-semibold mb-1">Usage Date <span class="text-red-500">*</span></label>
                    <input type="date" name="usage_date" id="usageDate" value="{{ date('Y-m-d') }}" required
                        class="w-full bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-sm text-gray-200">
                </div>
            </div>
        </div>

        <!-- Step 2: Material Usage -->
        <div class="b mb-4" id="materialsSection" style="display:none;">
            <div class="b-hd">Materials — Enter Quantity Used</div>
            <div class="overflow-x-auto">
                <table class="mat-table" id="materialsTable">
                    <thead>
                        <tr>
                            <th style="width:230px;">Material</th>
                            <th class="r" style="width:100px;">BOM Qty</th>
                            <th style="width:70px;">UOM</th>
                            <th class="r" style="width:110px;">Total Used</th>
                            <th class="r" style="width:110px;">Remaining</th>
                            <th class="r" style="width:120px;">Qty Used Today</th>
                        </tr>
                    </thead>
                    <tbody id="materialsBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <div class="b mb-4" id="notesSection" style="display:none;">
            <div class="b-hd">Notes</div>
            <div class="p-4">
                <textarea name="notes" rows="2" placeholder="Optional notes about today's usage..."
                    class="w-full bg-gray-900 border border-gray-700 rounded-md px-3 py-2 text-sm text-gray-200"></textarea>
            </div>
        </div>

        <!-- Submit -->
        <div id="submitSection" style="display:none;" class="flex justify-end gap-3">
            <a href="{{ route('daily_feed_usage.index') }}" class="px-5 py-2.5 text-sm border border-gray-600 rounded-md text-gray-300 hover:bg-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2.5 text-sm bg-blue-700 text-white rounded-md hover:bg-blue-800 font-semibold shadow-sm">
                <i class="fas fa-save mr-1"></i> Save Usage Log
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bomSelect   = document.getElementById('bomSelect');
    const houseSelect = document.getElementById('houseSelect');
    const matSection  = document.getElementById('materialsSection');
    const matBody     = document.getElementById('materialsBody');
    const notesSection= document.getElementById('notesSection');
    const submitSection=document.getElementById('submitSection');
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

    let housesData = {};
    let cumulativeData = {};

    const catLabels = {
        feed: 'Feeds', supplement: 'Supplements', vaccine: 'Vaccine',
        disinfectant: 'Disinfectant', cleaning_material: 'Cleaning Material',
        supply: 'Supplies', labor: 'Labor', overhead: 'Overhead'
    };

    bomSelect.addEventListener('change', async function() {
        houseSelect.innerHTML = '<option value="">— Select House —</option>';
        matSection.style.display = 'none';
        notesSection.style.display = 'none';
        submitSection.style.display = 'none';
        housesData = {};

        if (!this.value) return;

        try {
            const res = await fetch(`{{ url('/daily-feed-usage/bom-houses') }}?bom_id=${this.value}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            const data = await res.json();
            data.houses.forEach(h => {
                housesData[h.house_number] = h;
                const opt = document.createElement('option');
                opt.value = h.house_number;
                opt.textContent = h.house_name;
                houseSelect.appendChild(opt);
            });
        } catch (e) {
            console.error('Failed to load houses', e);
        }
    });

    houseSelect.addEventListener('change', async function() {
        matBody.innerHTML = '';
        matSection.style.display = 'none';
        notesSection.style.display = 'none';
        submitSection.style.display = 'none';

        const houseNum = this.value;
        if (!houseNum || !housesData[houseNum]) return;

        // Load cumulative usage
        try {
            const res = await fetch(`{{ url('/daily-feed-usage/cumulative') }}?bom_id=${bomSelect.value}&house_number=${houseNum}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            const data = await res.json();
            cumulativeData = {};
            (data.cumulative || []).forEach(c => {
                cumulativeData[c.name + '|' + c.category] = c.total_used;
            });
        } catch (e) {
            cumulativeData = {};
        }

        const materials = housesData[houseNum].materials;

        // Group by category
        const grouped = {};
        materials.forEach(m => {
            if (!grouped[m.category]) grouped[m.category] = [];
            grouped[m.category].push(m);
        });

        const catOrder = ['feed','supplement','vaccine','disinfectant','cleaning_material','supply','labor','overhead'];
        catOrder.forEach(cat => {
            if (!grouped[cat] || !grouped[cat].length) return;

            // Category header
            const hdr = document.createElement('tr');
            hdr.className = 'cat-hd';
            hdr.innerHTML = `<td colspan="6">${catLabels[cat] || cat}</td>`;
            matBody.appendChild(hdr);

            grouped[cat].forEach((m, idx) => {
                const key = m.name + '|' + m.category;
                const bomQty = cat === 'feed' ? m.qty_bags : m.qty_kg;
                const totalUsed = cumulativeData[key] || 0;
                const remaining = Math.max(0, bomQty - totalUsed);
                const isLow = remaining < bomQty * 0.2;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding-left:1.4rem;">${m.name}</td>
                    <td class="r">${bomQty ? Number(bomQty).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : '—'}</td>
                    <td>${m.uom || '—'}</td>
                    <td class="r">${totalUsed ? Number(totalUsed).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : '0.00'}</td>
                    <td class="r remaining ${isLow ? 'low' : ''}">${Number(remaining).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td class="r">
                        <input type="number" step="0.01" min="0"
                            class="usage-input mat-input"
                            data-name="${m.name}" data-category="${m.category}" data-uom="${m.uom || ''}"
                            data-bom-qty="${bomQty}" data-remaining="${remaining}"
                            placeholder="0.00">
                    </td>
                `;
                matBody.appendChild(tr);
            });
        });

        matSection.style.display = '';
        notesSection.style.display = '';
        submitSection.style.display = 'flex';
    });

    // Form submission — serialize materials
    document.getElementById('usageForm').addEventListener('submit', function(e) {
        const inputs = document.querySelectorAll('.mat-input');
        const materials = [];

        inputs.forEach(inp => {
            const val = parseFloat(inp.value);
            if (val > 0) {
                materials.push({
                    name:     inp.dataset.name,
                    category: inp.dataset.category,
                    uom:      inp.dataset.uom,
                    qty_used: val,
                });
            }
        });

        if (!materials.length) {
            e.preventDefault();
            Swal.fire('No Usage', 'Please enter at least one material quantity.', 'warning');
            return;
        }

        document.getElementById('materialsJson').value = JSON.stringify(materials);
    });

    // If BOM was pre-selected (from URL param), trigger load
    @if($selectedBom)
        bomSelect.dispatchEvent(new Event('change'));
    @endif
});
</script>
@endsection
