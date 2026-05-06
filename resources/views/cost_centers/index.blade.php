@extends('layouts.app')
@section('title', 'Cost Centers')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">COST CENTERS</h1>
            <div class="flex gap-2">
                <button onclick="document.getElementById('importModal').classList.remove('hidden')"
                    class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-file-upload mr-1"></i> Import
                </button>
                <a href="{{ route('cost_centers.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-plus mr-1"></i> New Cost Center
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Search -->
        <form method="GET" class="mb-4 flex gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by code, name, division, department..."
                class="flex-1 bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            @if($search)
                <a href="{{ route('cost_centers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Clear</a>
            @endif
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-700 text-sm">
                <thead class="bg-red-700 text-white uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-3 py-2">CC CODE</th>
                        <th class="border border-gray-700 px-3 py-2">CC NAME</th>
                        <th class="border border-gray-700 px-3 py-2">DIMENSION</th>
                        <th class="border border-gray-700 px-3 py-2">OWNER</th>
                        <th class="border border-gray-700 px-3 py-2">DIVISION</th>
                        <th class="border border-gray-700 px-3 py-2">DEPARTMENT</th>
                        <th class="border border-gray-700 px-3 py-2">CC</th>
                        <!-- <th class="border border-gray-700 px-3 py-2">GL ACCOUNTS</th> -->
                        <th class="border border-gray-700 px-3 py-2">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costCenters as $cc)
                    <tr class="hover:bg-gray-700/40 border-b border-gray-700">
                        <td class="border border-gray-700 px-3 py-2 font-mono font-semibold text-purple-400">{{ $cc->cost_center_code }}</td>
                        <td class="border border-gray-700 px-3 py-2 font-semibold">{{ $cc->cost_center_name }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-300">{{ $cc->dimension ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-300">{{ $cc->cost_center_owner ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-300">{{ $cc->division ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-300">{{ $cc->department ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-300">{{ $cc->cc ?? '—' }}</td>
                        <!-- <td class="border border-gray-700 px-3 py-2 text-gray-300 text-xs">
                            {{ is_array($cc->gl_accounts) ? implode(', ', array_column($cc->gl_accounts, 'code')) : '—' }}
                        </td> -->
                        <td class="border border-gray-700 px-3 py-2">
                            <div class="flex gap-2">
                                <a href="{{ route('cost_centers.show', $cc->id) }}" class="text-blue-400 hover:text-blue-300 text-xs" title="View"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('cost_centers.edit', $cc->id) }}" class="text-yellow-400 hover:text-yellow-300 text-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                @if(auth()->user()->hasRole(['IT', 'Admin', 'Information System']))
                                <form action="{{ route('cost_centers.destroy', $cc->id) }}" method="POST" onsubmit="return confirm('Delete this cost center?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-xs" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="border border-gray-700 px-3 py-6 text-center text-gray-400">No cost centers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $costCenters->links() }}</div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60">
    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md border border-gray-700">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-white">Import Cost Centers</h2>
            <button onclick="document.getElementById('importModal').classList.add('hidden')" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>

        <div id="importResult" class="hidden mb-4 p-3 rounded text-sm"></div>

        <form id="importForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-semibold mb-2">FILE (CSV / XLS / XLSX)</label>
                <input type="file" name="file" id="importFile" accept=".csv,.xls,.xlsx" required
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                <p class="text-xs text-gray-400 mt-1">Max 10MB. Expected columns (all optional except CC Code):</p>
                <p class="text-xs text-gray-500 mt-1">Cost Center Code, Cost Center Name, Dimension, Cost Center Owner, Division, Department, CC, Lookup, OPEX Mapping</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">Cancel</button>
                <button type="submit" id="importBtn" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-upload mr-1"></i> Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('importForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('importBtn');
    const result = document.getElementById('importResult');
    const file = document.getElementById('importFile').files[0];
    if (!file) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Importing...';
    result.className = 'hidden mb-4 p-3 rounded text-sm';

    const fd = new FormData(this);

    try {
        const res = await fetch('{{ route("cost_centers.import") }}', { method: 'POST', body: fd });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            result.classList.remove('hidden');
            result.className = 'mb-4 p-3 rounded text-sm bg-red-800 border border-red-600 text-red-100';
            result.innerHTML = `<i class="fas fa-times-circle mr-1"></i> Server error (HTTP ${res.status}). Check that you are logged in and the file is valid.`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Import';
            return;
        }

        result.classList.remove('hidden');
        if (data.success) {
            const nothingImported = (data.created === 0 && data.updated === 0);
           if (nothingImported) {
                result.className = 'mb-4 p-3 rounded text-sm bg-yellow-800 border border-yellow-600 text-yellow-100';
                result.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> ${data.message}`;
            } else {
                result.className = 'mb-4 p-3 rounded text-sm bg-green-800 border border-green-600 text-green-100';
                result.innerHTML = `<i class="fas fa-check-circle mr-1"></i> ${data.message}`;
                setTimeout(() => location.reload(), 2000);
            }
        } else {
            result.className = 'mb-4 p-3 rounded text-sm bg-red-800 border border-red-600 text-red-100';
            result.innerHTML = `<i class="fas fa-times-circle mr-1"></i> ${data.message}`;
            if (data.errors && data.errors.length) {
                result.innerHTML += '<ul class="list-disc list-inside mt-1 text-xs">' + data.errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
            }
        }
    } catch (err) {
        result.classList.remove('hidden');
        result.className = 'mb-4 p-3 rounded text-sm bg-red-800 border border-red-600 text-red-100';
        result.innerHTML = `<i class="fas fa-times-circle mr-1"></i> Upload failed: ${err.message}`;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Import';
});
</script>
@endsection
