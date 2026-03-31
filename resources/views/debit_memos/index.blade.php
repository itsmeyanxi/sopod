@extends('layouts.app')

@section('title', 'Debit Memos')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">Debit Memos</h1>
            <a href="{{ route('debit_memos.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                <i class="fas fa-plus mr-1"></i> Create Debit Memo
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-4 py-3 text-left">DM No.</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Supplier</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Invoice</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">Date</th>
                        <th class="border border-gray-700 px-4 py-3 text-right">Amount</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">Reason</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">Status</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($memos as $memo)
                    <tr class="hover:bg-gray-900">
                        <td class="border border-gray-700 px-4 py-3 font-bold text-blue-600">
                            <a href="{{ route('debit_memos.show', $memo) }}">{{ $memo->dm_no }}</a>
                        </td>
                        <td class="border border-gray-700 px-4 py-3 text-gray-200">{{ $memo->supplier_name }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-gray-500">{{ $memo->invoice_no ?? '-' }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-center text-gray-200">{{ $memo->memo_date->format('M d, Y') }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-right font-semibold text-white">{{ number_format($memo->amount, 2) }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-gray-300">{{ Str::limit($memo->reason, 30) }}</td>
                        <td class="border border-gray-700 px-4 py-3 text-center">
                            @if($memo->status === 'Open')
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-semibold">Open</span>
                            @elseif($memo->status === 'Applied')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Applied</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Voided</span>
                            @endif
                        </td>
                        <td class="border border-gray-700 px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('debit_memos.show', $memo) }}" class="text-gray-300 hover:text-white text-xs">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('debit_memos.edit', $memo) }}" class="text-blue-600 hover:text-blue-800 text-xs">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('debit_memos.destroy', $memo) }}" method="POST" class="inline" onsubmit="return confirm('Delete this debit memo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="border border-gray-700 px-4 py-8 text-center text-gray-500">No debit memos found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $memos->links() }}</div>
    </div>
</div>
@endsection
