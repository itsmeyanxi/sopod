@extends('layouts.app')
@section('title', 'Loan — ' . $loan->loan_no)

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-white">LOAN DETAILS</h1>
                <p class="text-sm text-gray-500 mt-1 font-mono font-bold">{{ $loan->loan_no }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($loan->status === 'Active')
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm font-semibold">Active</span>
                @elseif($loan->status === 'Paid')
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm font-semibold">Paid</span>
                @else
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm font-semibold">Void</span>
                @endif
                @if($loan->status === 'Active')
                    <a href="{{ route('loans.edit', $loan->id) }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 text-sm">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
                <a href="{{ route('loans.index') }}" class="bg-gray-600 text-gray-200 px-4 py-2 rounded hover:bg-gray-600 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded p-3 text-center">
                <p class="text-xs text-blue-600 font-semibold">Principal</p>
                <p class="text-lg font-bold text-blue-800">{{ number_format($loan->principal_amount, 2) }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded p-3 text-center">
                <p class="text-xs text-red-600 font-semibold">Outstanding</p>
                <p class="text-lg font-bold text-red-800">{{ number_format($loan->outstanding_principal, 2) }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded p-3 text-center">
                <p class="text-xs text-green-600 font-semibold">Principal Paid</p>
                <p class="text-lg font-bold text-green-800">{{ number_format($loan->total_principal_paid, 2) }}</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-center">
                <p class="text-xs text-yellow-600 font-semibold">Est. Monthly Payment</p>
                <p class="text-lg font-bold text-yellow-800">{{ number_format($loan->monthly_payment, 2) }}</p>
            </div>
        </div>

        <!-- Payoff Progress -->
        @if($loan->principal_amount > 0)
        <div class="mb-6">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Payoff Progress</span>
                <span>{{ $loan->payoff_percentage }}%</span>
            </div>
            <div class="w-full bg-gray-600 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all" style="width: {{ min(100, $loan->payoff_percentage) }}%"></div>
            </div>
        </div>
        @endif

        <!-- Loan Details -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Loan Date</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ $loan->loan_date->format('M d, Y') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Disbursement Date</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ $loan->disbursement_date ? $loan->disbursement_date->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Maturity Date</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm {{ $loan->status === 'Active' && $loan->maturity_date < now() ? 'text-red-600 font-semibold' : '' }}">
                    {{ $loan->maturity_date->format('M d, Y') }}
                    @if($loan->status === 'Active' && $loan->maturity_date >= now())
                        <span class="text-gray-400 ml-1">({{ $loan->remaining_months }} months left)</span>
                    @elseif($loan->status === 'Active')
                        <span class="text-red-500 ml-1">(Overdue)</span>
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Lender</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm font-semibold">{{ $loan->lender_name }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Loan Type</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ $loan->loan_type }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Payment Frequency</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ $loan->payment_frequency }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Interest Rate</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ number_format($loan->interest_rate, 4) }}% per annum</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Loan Term</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ $loan->loan_term_months }} months</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Total Interest Paid</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ number_format($loan->total_interest_paid, 2) }}</p>
            </div>
        </div>

        @if($loan->purpose)
        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Purpose</label>
            <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm">{{ $loan->purpose }}</p>
        </div>
        @endif

        <!-- GL Accounts -->
        @if($loan->gl_loan_account || $loan->gl_cash_account || $loan->gl_interest_expense)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Loans Payable GL</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm font-mono">{{ $loan->gl_loan_account ?? '—' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Cash / Bank GL</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm font-mono">{{ $loan->gl_cash_account ?? '—' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Interest Expense GL</label>
                <p class="px-3 py-2 bg-gray-900 border border-gray-700 rounded text-sm font-mono">{{ $loan->gl_interest_expense ?? '—' }}</p>
            </div>
        </div>
        @endif

        <!-- Record Payment Form -->
        @if($loan->status === 'Active')
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
            <h2 class="text-base font-semibold text-blue-800 mb-4"><i class="fas fa-money-bill-wave mr-2"></i>Record Payment</h2>
            <form method="POST" action="{{ route('loans.payment', $loan->id) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Payment Date <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Principal Paid <span class="text-red-500">*</span></label>
                        <input type="number" name="principal_paid" value="0" step="0.01" min="0" required
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Interest Paid <span class="text-red-500">*</span></label>
                        <input type="number" name="interest_paid" value="0" step="0.01" min="0" required
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Reference No.</label>
                        <input type="text" name="reference_no"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm" placeholder="Check/Voucher No.">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                            <option value="">— Select —</option>
                            <option value="Cash">Cash</option>
                            <option value="Check">Check</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Remarks</label>
                        <input type="text" name="remarks"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm">
                    <i class="fas fa-check mr-1"></i> Record Payment
                </button>
            </form>
        </div>
        @endif

        <!-- Payment History -->
        <h2 class="text-lg font-semibold text-gray-200 mb-3 border-b pb-2">Payment History</h2>
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-3 py-2 text-center">#</th>
                        <th class="border border-gray-700 px-3 py-2 text-center">Date</th>
                        <th class="border border-gray-700 px-3 py-2 text-right">Principal</th>
                        <th class="border border-gray-700 px-3 py-2 text-right">Interest</th>
                        <th class="border border-gray-700 px-3 py-2 text-right">Total</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">Reference</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">Method</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">Remarks</th>
                        <th class="border border-gray-700 px-3 py-2 text-left">By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->payments as $i => $pmt)
                    <tr class="hover:bg-gray-900 border-b border-gray-100">
                        <td class="border border-gray-700 px-3 py-2 text-center text-gray-400 text-xs">{{ $i + 1 }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-center text-xs">{{ $pmt->payment_date->format('M d, Y') }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($pmt->principal_paid, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($pmt->interest_paid, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right font-semibold text-blue-700">{{ number_format($pmt->total_payment, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-xs font-mono">{{ $pmt->reference_no ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-xs">{{ $pmt->payment_method ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-xs text-gray-500">{{ $pmt->remarks ?? '—' }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-xs text-gray-500">{{ $pmt->created_by ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-400 text-sm">No payments recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($loan->payments->count() > 0)
                <tfoot class="bg-gray-900 font-semibold text-sm">
                    <tr>
                        <td colspan="2" class="border border-gray-700 px-3 py-2 text-right">Totals:</td>
                        <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($loan->total_principal_paid, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right">{{ number_format($loan->total_interest_paid, 2) }}</td>
                        <td class="border border-gray-700 px-3 py-2 text-right text-blue-700">{{ number_format($loan->total_principal_paid + $loan->total_interest_paid, 2) }}</td>
                        <td colspan="4" class="border border-gray-700"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- Actions -->
        @if($loan->status === 'Active' && $loan->payments->count() === 0)
        <div class="flex gap-3 border-t border-gray-700 pt-4">
            <form method="POST" action="{{ route('loans.void', $loan->id) }}"
                  onsubmit="return confirm('Void this loan record?')">
                @csrf
                <button type="submit" class="bg-red-600 text-white px-5 py-2 rounded hover:bg-red-700 text-sm">
                    <i class="fas fa-ban mr-1"></i> Void Loan
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
