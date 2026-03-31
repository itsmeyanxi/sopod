@extends('layouts.app')
@section('title', 'Loans')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">LOANS</h1>
            <a href="{{ route('loans.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                <i class="fas fa-plus mr-1"></i> New Loan
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-3 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                <p class="text-xs text-blue-600 font-semibold">Total Loans</p>
                <p class="text-xl font-bold text-blue-800">{{ number_format($summary['total']) }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                <p class="text-xs text-green-600 font-semibold">Active</p>
                <p class="text-xl font-bold text-green-800">{{ number_format($summary['active']) }}</p>
            </div>
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-300 font-semibold">Paid</p>
                <p class="text-xl font-bold text-gray-200">{{ number_format($summary['paid']) }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                <p class="text-xs text-red-600 font-semibold">Total Outstanding</p>
                <p class="text-xl font-bold text-red-800">{{ number_format($summary['total_outstanding'], 2) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('loans.index') }}" class="bg-gray-900 border border-gray-700 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search loan #, lender, purpose..." class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                <select name="loan_type" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    @foreach(['Term Loan','Line of Credit','Mortgage Loan','Equipment Loan','Bridge Loan','Other'] as $t)
                        <option value="{{ $t }}" {{ request('loan_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
                <select name="status" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Paid"   {{ request('status') === 'Paid'   ? 'selected' : '' }}>Paid</option>
                    <option value="Void"   {{ request('status') === 'Void'   ? 'selected' : '' }}>Void</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex-1"><i class="fas fa-filter mr-1"></i> Filter</button>
                    <a href="{{ route('loans.index') }}" class="bg-gray-200 text-gray-200 px-3 py-2 rounded text-sm hover:bg-gray-300"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-3 py-3 text-left">Loan No.</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Date</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Lender</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">Type</th>
                        <th class="border border-gray-700 px-3 py-3 text-right">Principal</th>
                        <th class="border border-gray-700 px-3 py-3 text-right">Outstanding</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Rate %</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Maturity</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Status</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    @php
                        $isOverdue = $loan->status === 'Active' && $loan->maturity_date < now();
                    @endphp
                    <tr class="hover:bg-gray-900 border-b border-gray-100 {{ $isOverdue ? 'bg-red-50' : '' }}">
                        <td class="border border-gray-700 px-3 py-2">
                            <a href="{{ route('loans.show', $loan->id) }}" class="text-blue-700 hover:underline font-mono font-bold text-xs">{{ $loan->loan_no }}</a>
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-center text-xs">{{ $loan->loan_date->format('M d, Y') }}</td>
                        <td class="border border-gray-700 px-3 py-2 font-semibold">{{ $loan->lender_name }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-xs">
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-semibold">{{ $loan->loan_type }}</span>
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($loan->principal_amount, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right font-semibold {{ $isOverdue ? 'text-red-600' : '' }}">{{ number_format($loan->outstanding_principal, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-center text-xs">{{ number_format($loan->interest_rate, 2) }}%</td>
                        <td class="border border-gray-700 px-3 py-2 text-center text-xs {{ $isOverdue ? 'text-red-600 font-semibold' : '' }}">
                            {{ $loan->maturity_date->format('M d, Y') }}
                            @if($isOverdue)<span class="ml-1 text-red-500 text-xs">(Overdue)</span>@endif
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            @if($loan->status === 'Active')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Active</span>
                            @elseif($loan->status === 'Paid')
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-semibold">Paid</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Void</span>
                            @endif
                        </td>
                        <td class="border border-gray-700 px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('loans.show', $loan->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs" title="View"><i class="fas fa-eye"></i></a>
                                @if($loan->status === 'Active')
                                    <a href="{{ route('loans.edit', $loan->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-hand-holding-usd text-4xl mb-3 block"></i>
                            No loans found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $loans->appends(request()->query())->links('vendor.pagination.elegant') }}
        </div>
    </div>
</div>
@endsection
