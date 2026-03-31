@extends('layouts.app')
@section('title', 'Asset Classes')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">ASSET CLASSES</h1>
            <a href="{{ route('asset_classes.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Asset Class
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-6">
            <select name="asset_group" class="border border-gray-600 rounded px-3 py-2 text-sm">
                <option value="">All Groups</option>
                @foreach($assetGroups as $g)
                    <option value="{{ $g }}" {{ request('asset_group') == $g ? 'selected' : '' }}>{{ $g }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search asset class..." class="border border-gray-600 rounded px-3 py-2 text-sm w-64">
            <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('asset_classes.index') }}" class="bg-gray-600 text-gray-200 px-4 py-2 rounded text-sm">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-700 text-gray-300 uppercase text-xs">
                        <th class="px-4 py-3 text-left">Asset Group</th>
                        <th class="px-4 py-3 text-left">Asset Class</th>
                        <th class="px-4 py-3 text-right">Useful Life (Months)</th>
                        <th class="px-4 py-3 text-left">GL Account</th>
                        <th class="px-4 py-3 text-left">Depreciation Account</th>
                        <th class="px-4 py-3 text-left">Dep. Type</th>
                        <th class="px-4 py-3 text-center">Active</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($classes as $class)
                        <tr class="hover:bg-gray-900">
                            <td class="px-4 py-3">{{ $class->asset_group }}</td>
                            <td class="px-4 py-3 font-medium">{{ $class->asset_class }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($class->useful_life_months) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $class->gl_account ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $class->depreciation_account ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $class->dep_type }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($class->is_active)
                                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Active</span>
                                @else
                                    <span class="bg-gray-700 text-gray-500 px-2 py-0.5 rounded text-xs">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('asset_classes.edit', $class->id) }}" class="text-blue-600 hover:underline text-xs mr-2">Edit</a>
                                <form action="{{ route('asset_classes.destroy', $class->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this asset class?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">No asset classes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $classes->links() }}</div>
    </div>
</div>
@endsection
