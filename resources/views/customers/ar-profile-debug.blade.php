@extends('layouts.app')

@section('title', 'AR Profile Debug')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">🔍 AR Profile Debug Information</h2>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <h3 class="text-red-700 font-semibold mb-2">❌ No Records Found</h3>
            <p class="text-gray-500">Searched for customer: <strong>{{ $customerCode }}</strong></p>
        </div>

        <div class="space-y-6">
            {{-- Database Stats --}}
            <div class="bg-gray-100 rounded-lg p-4">
                <h3 class="text-gray-800 font-semibold mb-3">📊 Database Statistics</h3>
                <p class="text-gray-500">Total records in ar_aging table: <strong class="text-green-700">{{ $totalRecords }}</strong></p>
            </div>

            {{-- Query Results --}}
            <div class="bg-gray-100 rounded-lg p-4">
                <h3 class="text-gray-800 font-semibold mb-3">🔍 Query Results</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Query 1 (customer_code = '{{ $customerCode }}'):</span>
                        <span class="text-yellow-700 font-bold">{{ $query1Count }} records</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Query 2 (client_name = '{{ $customerCode }}'):</span>
                        <span class="text-yellow-700 font-bold">{{ $query2Count }} records</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Query 3 (client_name LIKE '%{{ $customerCode }}%'):</span>
                        <span class="text-yellow-700 font-bold">{{ $query3Count }} records</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Query 4 (OR condition):</span>
                        <span class="text-yellow-700 font-bold">{{ $query4Count }} records</span>
                    </div>
                </div>
            </div>

            {{-- Sample Records --}}
            <div class="bg-gray-100 rounded-lg p-4">
                <h3 class="text-gray-800 font-semibold mb-3">📝 Sample Records from Database (First 5)</h3>
                @if($sampleRecords->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500">
                                    <th class="px-3 py-2 text-left">Customer Code</th>
                                    <th class="px-3 py-2 text-left">Client Name</th>
                                    <th class="px-3 py-2 text-left">Invoice No</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-500">
                                @foreach($sampleRecords as $record)
                                <tr class="border-b border-gray-200">
                                    <td class="px-3 py-2">{{ $record->customer_code ?? 'NULL' }}</td>
                                    <td class="px-3 py-2">{{ $record->client_name ?? 'NULL' }}</td>
                                    <td class="px-3 py-2">{{ $record->invoice_no ?? 'NULL' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500">No records in the database</p>
                @endif
            </div>

            {{-- Recommendations --}}
            <div class="bg-blue-50 border border-blue-700 rounded-lg p-4">
                <h3 class="text-blue-700 font-semibold mb-3">💡 Recommendations</h3>
                <ul class="space-y-2 text-gray-500 text-sm">
                    <li>✓ Check if the customer code <strong>"{{ $customerCode }}"</strong> exactly matches the database</li>
                    <li>✓ The customer_code field might be case-sensitive</li>
                    <li>✓ The customer might be stored under a different code or name</li>
                    <li>✓ Compare the sample records above with your search</li>
                    <li>✓ Check your logs at <code class="bg-white px-2 py-1 rounded">storage/logs/laravel.log</code></li>
                </ul>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <a href="{{ route('aging_reports.view') }}" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded transition">
                    ← Back to Reports
                </a>
                <button onclick="window.location.reload()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">
                    🔄 Refresh
                </button>
            </div>
        </div>
    </div>
</div>
@endsection