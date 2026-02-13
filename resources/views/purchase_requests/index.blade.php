@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">Purchase Requests</h1>
            <a href="{{ route('purchase_requests.create') }}" class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition">
                <i class="fas fa-plus mr-2"></i>Create New PR
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-700">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-4 py-3 text-left">PR No</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Company</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Requisitioner</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Date of Request</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Status</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Created By</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-300 divide-y divide-gray-700">
                    @forelse($purchaseRequests as $pr)
                        <tr class="hover:bg-gray-700/40 transition">
                            <td class="border border-gray-700 px-4 py-3">{{ $pr->pr_no }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $pr->company }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $pr->requisitioner }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $pr->date_of_request->format('M d, Y') }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($pr->status === 'pending') bg-yellow-600 text-white
                                    @elseif($pr->status === 'approved') bg-green-600 text-white
                                    @elseif($pr->status === 'rejected') bg-red-600 text-white
                                    @else bg-blue-600 text-white
                                    @endif">
                                    {{ ucfirst($pr->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-700 px-4 py-3">{{ $pr->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('purchase_requests.show', $pr->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    <a href="{{ route('purchase_requests.edit', $pr->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('purchase_requests.destroy', $pr->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this PR?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-700 px-4 py-8 text-center text-gray-400">
                                No purchase requests found. <a href="{{ route('purchase_requests.create') }}" class="text-blue-400 hover:underline">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $purchaseRequests->links() }}
        </div>
    </div>
</div>
@endsection
