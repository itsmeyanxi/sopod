@extends('layouts.app')
@section('title', 'Create Cost Center')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">CREATE COST CENTER</h1>
            <a href="{{ route('cost_centers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('cost_centers.store') }}" method="POST">
            @csrf

            <!-- Header Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">COST CENTER CODE <span class="text-red-500">*</span></label>
                    <input type="text" name="cost_center_code" value="{{ old('cost_center_code') }}" required
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white uppercase focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. CC-001" style="text-transform:uppercase">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">COST CENTER NAME <span class="text-red-500">*</span></label>
                    <input type="text" name="cost_center_name" value="{{ old('cost_center_name') }}" required
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. Marketing Department">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">DIMENSION</label>
                    <input type="text" name="dimension" value="{{ old('dimension') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. Operations">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-1">COST CENTER OWNER</label>
                    <input type="text" name="cost_center_owner" value="{{ old('cost_center_owner') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Owner name or position">
                </div>
            </div>

            <!-- General CC Section -->
            <div class="bg-gray-900 border border-gray-700 rounded p-4 mb-6">
                <h3 class="font-semibold text-purple-400 mb-4 text-sm uppercase tracking-wide">General CC</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DIVISION</label>
                        <input type="text" name="division" value="{{ old('division') }}"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="e.g. Corporate">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">DEPARTMENT</label>
                        <input type="text" name="department" value="{{ old('department') }}"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="e.g. Finance">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">CC</label>
                        <input type="text" name="cc" value="{{ old('cc') }}"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="CC classification">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">GENERAL CC</label>
                        <input type="text" name="general_cc" value="{{ old('general_cc') }}"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="General CC">
                    </div>
                </div>

                <!-- GL Accounts -->
                <div class="mt-4">
                    <label class="block font-semibold text-gray-300 mb-2">GL ACCOUNTS</label>
                    <div class="relative mb-2">
                        <input type="text" id="glSearchInput" placeholder="Search GL account by code or name..."
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" autocomplete="off">
                        <div id="glDropdown" class="hidden absolute z-20 left-0 right-0 bg-gray-700 border border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto" style="top:100%"></div>
                    </div>
                    <div id="glSelectedList" class="space-y-1">
                        @foreach(old('gl_accounts', []) as $gl)
                        @php $glArr = is_string($gl) ? json_decode($gl, true) : $gl; @endphp
                        @if(is_array($glArr))
                        <div class="flex items-center justify-between bg-gray-800 border border-gray-600 rounded px-3 py-1 text-sm gl-item">
                            <span class="text-gray-200"><span class="font-mono text-purple-400">{{ $glArr['code'] ?? '' }}</span> — {{ $glArr['name'] ?? '' }}</span>
                            <button type="button" onclick="removeGl(this)" class="text-red-400 hover:text-red-300 ml-2"><i class="fas fa-times"></i></button>
                            <input type="hidden" name="gl_accounts[]" value="{{ json_encode($glArr) }}">
                        </div>
                        @endif
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Search and click to add multiple GL accounts.</p>
                </div>

                <!-- Lookup & OPEX Mapping -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">LOOKUP</label>
                        <textarea name="lookup" rows="3"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Lookup reference...">{{ old('lookup') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-1">OPEX MAPPING</label>
                        <textarea name="opex_mapping" rows="3"
                            class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="OPEX mapping details...">{{ old('opex_mapping') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('cost_centers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-2 rounded">
                    <i class="fas fa-save mr-1"></i> Save Cost Center
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const allGlAccounts = @json($glAccounts);
const selectedGlIds = new Set();

const glInput    = document.getElementById('glSearchInput');
const glDropdown = document.getElementById('glDropdown');
const glList     = document.getElementById('glSelectedList');

// Pre-mark old() selections
document.querySelectorAll('.gl-item input[type=hidden]').forEach(inp => {
    try { const g = JSON.parse(inp.value); if (g.id) selectedGlIds.add(g.id); } catch(e){}
});

glInput.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) { glDropdown.classList.add('hidden'); return; }
    const matches = allGlAccounts.filter(g =>
        !selectedGlIds.has(g.id) &&
        (g.account_code.toLowerCase().includes(q) || g.account_name.toLowerCase().includes(q))
    ).slice(0, 15);
    if (!matches.length) { glDropdown.classList.add('hidden'); return; }
    glDropdown.innerHTML = matches.map(g =>
        `<div class="px-3 py-2 hover:bg-gray-600 cursor-pointer text-sm text-gray-200" data-id="${g.id}" data-code="${g.account_code.replace(/"/g,'&quot;')}" data-name="${g.account_name.replace(/"/g,'&quot;')}">
            <span class="font-mono text-purple-400">${g.account_code}</span> — ${g.account_name}
        </div>`
    ).join('');
    glDropdown.classList.remove('hidden');
    glDropdown.querySelectorAll('div').forEach(opt => {
        opt.addEventListener('mousedown', function(e) {
            e.preventDefault();
            addGl(this.dataset.id, this.dataset.code, this.dataset.name);
            glInput.value = '';
            glDropdown.classList.add('hidden');
        });
    });
});

glInput.addEventListener('blur', () => setTimeout(() => glDropdown.classList.add('hidden'), 150));

function addGl(id, code, name) {
    if (selectedGlIds.has(parseInt(id))) return;
    selectedGlIds.add(parseInt(id));
    const div = document.createElement('div');
    div.className = 'flex items-center justify-between bg-gray-800 border border-gray-600 rounded px-3 py-1 text-sm gl-item';
    div.innerHTML = `<span class="text-gray-200"><span class="font-mono text-purple-400">${code}</span> — ${name}</span>
        <button type="button" onclick="removeGl(this)" class="text-red-400 hover:text-red-300 ml-2"><i class="fas fa-times"></i></button>
        <input type="hidden" name="gl_accounts[]" value='${JSON.stringify({id: parseInt(id), code, name})}'>`;
    glList.appendChild(div);
}

function removeGl(btn) {
    const item = btn.closest('.gl-item');
    try { const g = JSON.parse(item.querySelector('input').value); selectedGlIds.delete(g.id); } catch(e){}
    item.remove();
}
</script>
@endsection
