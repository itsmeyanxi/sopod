@extends('layouts.app')
@section('title', 'Fixed Asset Capitalization')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">FIXED ASSET CAPITALIZATION</h1>
            <div class="flex gap-2">
                <a href="{{ route('fixed_assets.summary') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition text-sm">
                    <i class="fas fa-chart-bar mr-1"></i> Lapsing Schedule
                </a>
                <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                    <i class="fas fa-file-import mr-1"></i> Import Excel
                </button>
                <a href="{{ route('fixed_assets.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Asset
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                <p class="text-xs text-blue-600 font-semibold">Total Assets</p>
                <p class="text-lg font-bold text-blue-800">{{ number_format($summary['total_assets']) }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                <p class="text-xs text-green-600 font-semibold">Active</p>
                <p class="text-lg font-bold text-green-800">{{ number_format($summary['active_count']) }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                <p class="text-xs text-red-600 font-semibold">Disposed</p>
                <p class="text-lg font-bold text-red-800">{{ number_format($summary['disposed_count']) }}</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
                <p class="text-xs text-yellow-600 font-semibold">Fully Depreciated</p>
                <p class="text-lg font-bold text-yellow-800">{{ number_format($summary['fully_dep_count']) }}</p>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 text-center">
                <p class="text-xs text-purple-600 font-semibold">Total Cost</p>
                <p class="text-sm font-bold text-purple-800">{{ number_format($summary['total_cost'], 2) }}</p>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-center">
                <p class="text-xs text-orange-600 font-semibold">Accum. Dep.</p>
                <p class="text-sm font-bold text-orange-800">{{ number_format($summary['total_accum_dep'], 2) }}</p>
            </div>
            <div class="bg-teal-50 border border-teal-200 rounded-lg p-3 text-center">
                <p class="text-xs text-teal-600 font-semibold">Net Book Value</p>
                <p class="text-sm font-bold text-teal-800">{{ number_format($summary['total_nbv'], 2) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('fixed_assets.index') }}" class="bg-gray-900 border border-gray-700 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search assets..." class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <select name="asset_group" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                        <option value="">All Asset Groups</option>
                        @foreach($assetGroups as $group)
                            <option value="{{ $group }}" {{ request('asset_group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="asset_class" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                        <option value="">All Asset Classes</option>
                        @foreach($assetClasses as $class)
                            <option value="{{ $class }}" {{ request('asset_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Disposed" {{ request('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                        <option value="Fully Depreciated" {{ request('status') == 'Fully Depreciated' ? 'selected' : '' }}>Fully Depreciated</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition flex-1">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="{{ route('fixed_assets.index') }}" class="bg-gray-600 text-gray-200 px-4 py-2 rounded text-sm hover:bg-gray-600 transition">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-3 py-3 text-left">Asset Code</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Description</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Asset Group</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Asset Class</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Acquisition</th>
                        <th class="border border-gray-700 px-3 py-3 text-right">Cost</th>
                        <th class="border border-gray-700 px-3 py-3 text-right">Accum. Dep.</th>
                        <th class="border border-gray-700 px-3 py-3 text-right">NBV</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Remaining (Mo)</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Status</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                    <tr class="hover:bg-gray-900 border-b border-gray-100">
                        <td class="border border-gray-700 px-3 py-2 font-mono text-xs text-blue-700">
                            <a href="{{ route('fixed_assets.show', $asset->id) }}" class="hover:underline">{{ $asset->asset_code ?: '—' }}</a>
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-white max-w-xs truncate" title="{{ $asset->asset_description }}">
                            {{ Str::limit($asset->asset_description, 40) }}
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-300 text-xs">{{ $asset->asset_group }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-gray-300 text-xs">{{ $asset->asset_class ?: '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-center text-gray-200 text-xs">{{ $asset->acquisition_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right font-semibold text-white">{{ number_format($asset->cost, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right text-orange-700">{{ number_format($asset->accumulated_depreciation, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right font-semibold {{ $asset->net_book_value > 0 ? 'text-green-700' : 'text-gray-400' }}">{{ number_format($asset->net_book_value, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @if($asset->remaining_life_months > 0)
                                <span class="text-blue-700 font-semibold">{{ $asset->remaining_life_months }}</span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @if($asset->disposal_date)
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Disposed</span>
                            @elseif($asset->remaining_life_months <= 0)
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-semibold">Fully Dep.</span>
                            @else
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Active</span>
                            @endif
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('fixed_assets.show', $asset->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('fixed_assets.edit', $asset->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('fixed_assets.destroy', $asset->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this asset?')">
                                    @csrf @method('DELETE')
                                    <button class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-box-open text-4xl mb-3 block"></i>
                            No fixed assets found. Import from Excel or add manually.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $assets->appends(request()->query())->links('vendor.pagination.elegant') }}
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-white">Import Lapsing Schedule</h3>
            <button onclick="document.getElementById('importModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('fixed_assets.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Upload Excel File (.xlsx)</label>
                <input type="file" name="file" accept=".xlsx,.xls" required class="w-full bg-gray-900 border border-gray-600 rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">Upload your Lapsing Schedule Excel file. The system will read the "Monthly Depreciation Sched" sheet.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="bg-gray-600 text-gray-200 px-4 py-2 rounded text-sm">Cancel</button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <i class="fas fa-upload mr-1"></i> Import
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
