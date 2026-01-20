@extends('layouts.app')

@section('title', 'Duplicate CR Numbers')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">Duplicate CR Numbers</h1>
            <a href="{{ route('payments.entry') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                Back to Payments
            </a>
        </div>

        <div class="bg-blue-900 border border-blue-700 rounded p-4 mb-6">
            <p class="text-blue-200">
                <i class="fas fa-info-circle mr-2"></i>
                This page shows all CR numbers that have multiple payment records. Click on any CR number to see the details of all records with that number.
            </p>
        </div>

        @if(count($duplicates) > 0)
            <div class="bg-gray-700 rounded-lg p-4">
                <p class="text-gray-300 mb-4">
                    <strong>Found {{ count($duplicates) }} CR numbers with duplicates</strong>
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800 rounded-lg">
                        <thead>
                            <tr class="bg-gray-900 text-gray-300 text-sm">
                                <th class="px-4 py-3 text-left">CR Number</th>
                                <th class="px-4 py-3 text-center">Number of Records</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-300">
                            @foreach($duplicates as $duplicate)
                            <tr class="border-b border-gray-700 hover:bg-gray-750">
                                <td class="px-4 py-3 font-semibold">
                                    {{ $duplicate->collection_receipt_number }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="
                                        @if($duplicate->count > 20) bg-red-600
                                        @elseif($duplicate->count > 10) bg-orange-600
                                        @else bg-yellow-600
                                        @endif
                                        px-3 py-1 rounded-full text-xs font-semibold
                                    ">
                                        {{ $duplicate->count }} records
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('payments.duplicateCR', ['cr_number' => $duplicate->collection_receipt_number]) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm inline-block">
                                        <i class="fas fa-eye mr-1"></i>View Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-gray-700 rounded-lg p-8 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                <p class="text-gray-300 text-lg">No duplicate CR numbers found!</p>
            </div>
        @endif
    </div>
</div>
@endsection
