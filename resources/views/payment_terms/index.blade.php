@extends('layouts.app')

@section('title', 'Payment Terms')

@section('content')
<div class="container mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">Payment Terms</h1>
            <a href="{{ route('payment_terms.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                <i class="fas fa-plus mr-1"></i> Add Payment Term
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-200 px-4 py-3 text-left">Code</th>
                        <th class="border border-gray-200 px-4 py-3 text-left">Description</th>
                        <th class="border border-gray-200 px-4 py-3 text-center">Net Days</th>
                        <th class="border border-gray-200 px-4 py-3 text-center">Discount %</th>
                        <th class="border border-gray-200 px-4 py-3 text-center">Discount Days</th>
                        <th class="border border-gray-200 px-4 py-3 text-center">Suppliers Using</th>
                        <th class="border border-gray-200 px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($terms as $term)
                    <tr class="hover:bg-gray-50">
                        <td class="border border-gray-200 px-4 py-3 font-bold text-blue-600">{{ $term->code }}</td>
                        <td class="border border-gray-200 px-4 py-3 text-gray-700">{{ $term->description }}</td>
                        <td class="border border-gray-200 px-4 py-3 text-center text-gray-700">{{ $term->net_days }}</td>
                        <td class="border border-gray-200 px-4 py-3 text-center text-gray-700">{{ $term->discount_pct > 0 ? $term->discount_pct . '%' : '-' }}</td>
                        <td class="border border-gray-200 px-4 py-3 text-center text-gray-700">{{ $term->discount_days > 0 ? $term->discount_days : '-' }}</td>
                        <td class="border border-gray-200 px-4 py-3 text-center">
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">{{ $term->suppliers_using_count }}</span>
                        </td>
                        <td class="border border-gray-200 px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('payment_terms.edit', $term) }}" class="text-blue-600 hover:text-blue-800 text-xs">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('payment_terms.destroy', $term) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payment term?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="border border-gray-200 px-4 py-8 text-center text-gray-500">No payment terms found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
