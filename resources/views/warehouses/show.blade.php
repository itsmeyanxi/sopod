@extends('layouts.app')
@section('title', 'View Warehouse')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">{{ $warehouse->warehouse_name }}</h1>
            <div class="flex gap-2">
                <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition"><i class="fas fa-edit mr-1"></i> Edit</a>
                <a href="{{ route('warehouses.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 transition"><i class="fas fa-arrow-left mr-1"></i> Back</a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-900 border border-gray-700 rounded p-4 space-y-3">
                <h3 class="font-semibold text-white border-b border-gray-700 pb-2">Basic Information</h3>
                <div><span class="text-gray-500">Code:</span> <span class="ml-2 font-mono text-purple-700">{{ $warehouse->warehouse_code }}</span></div>
                <div><span class="text-gray-500">Name:</span> <span class="ml-2">{{ $warehouse->warehouse_name }}</span></div>
                <div><span class="text-gray-500">Address:</span> <span class="ml-2">{{ $warehouse->address ?? '—' }}</span></div>
                <div><span class="text-gray-500">Email:</span> <span class="ml-2">{{ $warehouse->email ?? '—' }}</span></div>
                <div><span class="text-gray-500">Contact:</span> <span class="ml-2">{{ $warehouse->contact_number ?? '—' }}</span></div>
                <div><span class="text-gray-500">TIN:</span> <span class="ml-2">{{ $warehouse->tin ?? '—' }}</span></div>
                <div><span class="text-gray-500">Status:</span>
                    <span class="ml-2 px-2 py-1 rounded text-xs font-semibold {{ $warehouse->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ strtoupper($warehouse->status) }}
                    </span>
                </div>
            </div>
            <div class="bg-gray-900 border border-gray-700 rounded p-4 space-y-3">
                <h3 class="font-semibold text-white border-b border-gray-700 pb-2">Bank Information</h3>
                <div><span class="text-gray-500">Bank:</span> <span class="ml-2">{{ $warehouse->bank ?? '—' }}</span></div>
                <div><span class="text-gray-500">Account Name:</span> <span class="ml-2">{{ $warehouse->account_name ?? '—' }}</span></div>
                <div><span class="text-gray-500">Account Number:</span> <span class="ml-2">{{ $warehouse->account_number ?? '—' }}</span></div>
            </div>
        </div>

        @if($warehouse->documents && count($warehouse->documents) > 0)
        <div class="bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-4"><i class="fas fa-folder-open mr-2"></i>Documents</h3>
            <div class="space-y-2">
                @foreach($warehouse->documents as $doc)
                <div class="flex items-center justify-between bg-gray-800 rounded px-3 py-2">
                    <span class="text-gray-500"><i class="fas fa-file mr-2 text-purple-700"></i>{{ $doc['name'] }}</span>
                    <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="text-blue-700 hover:text-blue-700 text-sm"><i class="fas fa-download mr-1"></i>Download</a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection