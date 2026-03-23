@extends('layouts.app')

@section('title', 'Reimbursement Forms')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">REIMBURSEMENT FORMS</h1>
            <a href="{{ route('reimbursement_forms.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-gray-800 px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                <i class="fas fa-plus mr-1"></i> Create New Reimbursement Form
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
                        <th class="border border-gray-200 px-4 py-3">RI NO</th>
                        <th class="border border-gray-200 px-4 py-3">DEPARTMENT</th>
                        <th class="border border-gray-200 px-4 py-3">TOTAL SPENT</th>
                        <th class="border border-gray-200 px-4 py-3">AMOUNT TO REIMBURSE</th>
                        <th class="border border-gray-200 px-4 py-3">DATE APPLIED</th>
                        <th class="border border-gray-200 px-4 py-3">STATUS</th>
                        <th class="border border-gray-200 px-4 py-3">CREATED BY</th>
                        <th class="border border-gray-200 px-4 py-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="text-gray-500">
                    @forelse($reimbursements as $reimbursement)
                        <tr class="hover:bg-gray-100/40">
                            <td class="border border-gray-200 px-4 py-3">{{ $reimbursement->ri_no }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $reimbursement->department }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-right">&#8369;{{ number_format($reimbursement->total_amount_spent, 2) }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-right">&#8369;{{ number_format($reimbursement->amount_to_reimburse, 2) }}</td>
                            <td class="border border-gray-200 px-4 py-3">{{ $reimbursement->date_applied->format('M d, Y') }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <span class="px-3 py-1 rounded text-xs font-semibold
                                    @if($reimbursement->status === 'pending') bg-yellow-600 text-white
                                    @elseif($reimbursement->status === 'approved') bg-green-600 text-white
                                    @elseif($reimbursement->status === 'rejected') bg-red-600 text-white
                                    @else bg-blue-600 text-white
                                    @endif">
                                    {{ ucfirst($reimbursement->status) }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-3">{{ $reimbursement->creator->name ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('reimbursement_forms.show', $reimbursement->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        View
                                    </a>
                                    <a href="{{ route('reimbursement_forms.edit', $reimbursement->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('reimbursement_forms.destroy', $reimbursement->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this reimbursement form?');">
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
                            <td colspan="8" class="border border-gray-200 px-4 py-8 text-center text-gray-400">
                                No reimbursement forms found. <a href="{{ route('reimbursement_forms.create') }}" class="text-purple-400 hover:text-purple-300">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $reimbursements->links() }}
        </div>
    </div>
</div>
@endsection
