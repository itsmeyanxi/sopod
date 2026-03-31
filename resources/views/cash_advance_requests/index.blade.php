@extends('layouts.app')

@section('title', 'Cash Advance Requests')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">CASH ADVANCE REQUESTS</h1>
            <a href="{{ route('cash_advance_requests.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                <i class="fas fa-plus mr-1"></i> Create New CAR
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
            <table class="w-full border-collapse border border-gray-700">
                <thead class="bg-gray-700 text-gray-500 uppercase text-sm">
                    <tr>
                        <th class="border border-gray-700 px-4 py-3">CAR NO</th>
                        <th class="border border-gray-700 px-4 py-3">PAYEE</th>
                        <th class="border border-gray-700 px-4 py-3">DEPARTMENT</th>
                        <th class="border border-gray-700 px-4 py-3">AMOUNT</th>
                        <th class="border border-gray-700 px-4 py-3">DATE REQUESTED</th>
                        <th class="border border-gray-700 px-4 py-3">STATUS</th>
                        <th class="border border-gray-700 px-4 py-3">CREATED BY</th>
                        <th class="border border-gray-700 px-4 py-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="text-gray-500">
                    @forelse($cars as $car)
                        <tr class="hover:bg-gray-700/40">
                            <td class="border border-gray-700 px-4 py-3">{{ $car->car_no }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $car->payee }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $car->department }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-right">&#8369;{{ number_format($car->amount, 2) }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $car->date_requested ? $car->date_requested->format('M d, Y') : 'N/A' }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <span class="px-3 py-1 rounded text-xs font-semibold
                                    @if($car->status === 'pending') bg-yellow-600 text-white
                                    @elseif($car->status === 'approved') bg-green-600 text-white
                                    @elseif($car->status === 'rejected') bg-red-600 text-white
                                    @elseif($car->status === 'liquidated') bg-blue-600 text-white
                                    @else bg-gray-200 text-white
                                    @endif">
                                    {{ ucfirst($car->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-700 px-4 py-3">{{ $car->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('cash_advance_requests.show', $car->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    <a href="{{ route('cash_advance_requests.edit', $car->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('cash_advance_requests.destroy', $car->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this Cash Advance Request?');">
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
                            <td colspan="8" class="border border-gray-700 px-4 py-8 text-center text-gray-500">
                                No cash advance requests found. <a href="{{ route('cash_advance_requests.create') }}" class="text-purple-700 hover:text-purple-700">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $cars->links() }}
        </div>
    </div>
</div>
@endsection
