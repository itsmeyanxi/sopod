@extends('layouts.app')

@section('title', 'Liquidation Forms')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">LIQUIDATION FORMS</h1>
            <a href="{{ route('liquidation_forms.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                <i class="fas fa-plus mr-1"></i> Create New Liquidation Form
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

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-200">
                <thead class="bg-gray-100 text-gray-500 uppercase text-sm">
                    <tr>
                        <th class="border border-gray-200 px-4 py-3">LIQ NO</th>
                        <th class="border border-gray-200 px-4 py-3">NAME</th>
                        <th class="border border-gray-200 px-4 py-3">DEPARTMENT</th>
                        <th class="border border-gray-200 px-4 py-3">TOTAL AMOUNT</th>
                        <th class="border border-gray-200 px-4 py-3">LINKED CAR</th>
                        <th class="border border-gray-200 px-4 py-3">DATE APPLIED</th>
                        <th class="border border-gray-200 px-4 py-3">STATUS</th>
                        <th class="border border-gray-200 px-4 py-3">CREATED BY</th>
                        <th class="border border-gray-200 px-4 py-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="text-gray-500">
                    @forelse($liquidations as $liquidation)
                        <tr class="hover:bg-gray-100/40">
                            <td class="border border-gray-200 px-4 py-3">{{ $liquidation->liq_no }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $liquidation->name }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $liquidation->department }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-right">&#8369;{{ number_format($liquidation->total_amount_spent, 2) }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                @if($liquidation->cashAdvanceRequest)
                                    <a href="{{ route('cash_advance_requests.show', $liquidation->cashAdvanceRequest->id) }}" class="text-purple-700 hover:text-purple-700">
                                        {{ $liquidation->cashAdvanceRequest->car_no }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="border border-gray-200 px-4 py-3">{{ $liquidation->date_applied->format('M d, Y') }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <span class="px-3 py-1 rounded text-xs font-semibold
                                    @if($liquidation->status === 'pending') bg-yellow-600 text-white
                                    @elseif($liquidation->status === 'approved') bg-green-600 text-white
                                    @elseif($liquidation->status === 'rejected') bg-red-600 text-white
                                    @else bg-blue-600 text-white
                                    @endif">
                                    {{ ucfirst($liquidation->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-3">{{ $liquidation->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('liquidation_forms.show', $liquidation->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    <a href="{{ route('liquidation_forms.edit', $liquidation->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('liquidation_forms.destroy', $liquidation->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this Liquidation Form?');">
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
                            <td colspan="9" class="border border-gray-200 px-4 py-8 text-center text-gray-500">
                                No liquidation forms found. <a href="{{ route('liquidation_forms.create') }}" class="text-purple-700 hover:text-purple-700">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $liquidations->links() }}
        </div>
    </div>
</div>
@endsection
