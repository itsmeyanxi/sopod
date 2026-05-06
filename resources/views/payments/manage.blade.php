@extends('layouts.app')

@section('title', 'Manage Payments')

@section('content')
<div class="w-full px-2 sm:px-4">

    @if(session('success'))
        <div class="mb-4 bg-green-900/40 border border-green-600 text-green-300 rounded-lg px-4 py-3 flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-900/40 border border-red-600 text-red-300 rounded-lg px-4 py-3 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg shadow-lg p-4 sm:p-5">

        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center mb-5 gap-3">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-shield-alt text-red-400"></i> Manage Payments
                    <span class="bg-red-700 text-white text-xs px-2 py-0.5 rounded font-semibold ml-1">IT Only</span>
                </h2>
                <p class="text-gray-400 text-xs mt-1">Delete and manage payment records. All deletions are logged.</p>
            </div>
            <a href="{{ route('payments.entry') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded text-sm flex items-center gap-1.5">
                <i class="fas fa-arrow-left"></i> Back to Collections
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('payments.manage') }}" class="bg-gray-700 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                           class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                           class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Customer</label>
                    <input type="text" name="customer" value="{{ $customer }}" placeholder="Name or code"
                           class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">DR No</label>
                    <input type="text" name="dr_no" value="{{ $drFilter }}" placeholder="e.g. 138698"
                           class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">CR No</label>
                    <input type="text" name="cr_no" value="{{ $crFilter }}" placeholder="Receipt no"
                           class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="flex items-end gap-1">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded text-xs">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="{{ route('payments.manage') }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-2 py-1.5 rounded text-xs text-center">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        <!-- Summary -->
        <div class="flex items-center justify-between mb-3 text-xs text-gray-400">
            <span>Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} payments</span>
            <span>Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</span>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-lg">
            <table class="min-w-full bg-gray-900 text-xs">
                <thead>
                    <tr class="bg-gray-950 text-gray-400 uppercase tracking-wide text-left">
                        <th class="px-3 py-2 whitespace-nowrap">ID</th>
                        <th class="px-3 py-2 whitespace-nowrap">Post Date</th>
                        <th class="px-3 py-2 whitespace-nowrap">Customer</th>
                        <th class="px-3 py-2 whitespace-nowrap">DR No</th>
                        <th class="px-3 py-2 whitespace-nowrap">Invoice No</th>
                        <th class="px-3 py-2 whitespace-nowrap">CR No</th>
                        <th class="px-3 py-2 whitespace-nowrap">CR Date</th>
                        <th class="px-3 py-2 text-right whitespace-nowrap">Amount</th>
                        <th class="px-3 py-2 text-right whitespace-nowrap">EWT</th>
                        <th class="px-3 py-2 text-right whitespace-nowrap">Net</th>
                        <th class="px-3 py-2 whitespace-nowrap">Method</th>
                        <th class="px-3 py-2 whitespace-nowrap">Status</th>
                        <th class="px-3 py-2 text-center whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-800 transition {{ $payment->status === 'Voided' ? 'opacity-50' : '' }}">
                        <td class="px-3 py-2 text-gray-500 font-mono">{{ $payment->id }}</td>
                        <td class="px-3 py-2 text-gray-300 whitespace-nowrap">
                            {{ $payment->payment_posting_date ? \Carbon\Carbon::parse($payment->payment_posting_date)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="text-white font-medium">{{ $payment->customer_name ?? '—' }}</div>
                            <div class="text-gray-500 text-xs font-mono">{{ $payment->customer_code ?? '' }}</div>
                        </td>
                        <td class="px-3 py-2">
                            <a href="{{ route('payments.show', $payment->id) }}"
                               class="text-blue-400 hover:text-blue-300 font-mono font-semibold">
                                {{ $payment->dr_no ?? '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-2 text-gray-400 font-mono">{{ $payment->invoice_no ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-200 font-mono">{{ $payment->collection_receipt_number ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-400 whitespace-nowrap">
                            {{ $payment->collection_receipt_date ? \Carbon\Carbon::parse($payment->collection_receipt_date)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right font-semibold text-orange-400 whitespace-nowrap">
                            ₱{{ number_format((float)$payment->amount, 2) }}
                        </td>
                        <td class="px-3 py-2 text-right text-gray-400 whitespace-nowrap">
                            @if((float)($payment->ewt ?? 0) > 0)
                                ₱{{ number_format((float)$payment->ewt, 2) }}
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right text-green-400 whitespace-nowrap">
                            ₱{{ number_format((float)($payment->net ?? $payment->check_amount ?? $payment->amount), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @php
                                $method = $payment->payment_method ?? $payment->payment_option ?? null;
                                $methodLabels = ['check' => 'Check', 'bank_transfer' => 'Transfer', 'cash' => 'Cash'];
                                $methodColors = ['check' => 'bg-blue-900/40 text-blue-300 border-blue-700', 'bank_transfer' => 'bg-green-900/40 text-green-300 border-green-700', 'cash' => 'bg-yellow-900/40 text-yellow-300 border-yellow-700'];
                                $label = $methodLabels[$method] ?? ucfirst($method ?? '—');
                                $color = $methodColors[$method] ?? 'bg-gray-700 text-gray-300 border-gray-600';
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs border {{ $color }}">{{ $label }}</span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @if($payment->status === 'Voided')
                                <span class="px-2 py-0.5 rounded text-xs bg-red-900/40 text-red-400 border border-red-700">Voided</span>
                            @elseif($payment->status === 'Bounced')
                                <span class="px-2 py-0.5 rounded text-xs bg-red-900/40 text-red-400 border border-red-700">Bounced</span>
                            @elseif($payment->status === 'Posted')
                                <span class="px-2 py-0.5 rounded text-xs bg-green-900/40 text-green-400 border border-green-700">Posted</span>
                            @elseif($payment->is_short_payment)
                                <span class="px-2 py-0.5 rounded text-xs bg-orange-900/40 text-orange-400 border border-orange-700">Short</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs bg-yellow-900/40 text-yellow-400 border border-yellow-700">Clearing</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('payments.show', $payment->id) }}"
                                   class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs"
                                   title="View details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($payment->status !== 'Voided')
                                <button onclick="confirmDelete({{ $payment->id }}, '{{ addslashes($payment->collection_receipt_number) }}', '{{ addslashes($payment->dr_no) }}', '{{ number_format((float)$payment->amount, 2) }}')"
                                        class="bg-red-700 hover:bg-red-600 text-white px-2 py-1 rounded text-xs"
                                        title="Delete payment">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-file-invoice text-4xl mb-3 block text-gray-600"></i>
                            No payments found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($payments->count() > 0)
                <tfoot>
                    <tr class="bg-gray-950 border-t-2 border-orange-500">
                        <td colspan="7" class="px-3 py-2 text-right text-gray-400 text-xs font-semibold">PAGE TOTAL:</td>
                        <td class="px-3 py-2 text-right font-bold text-orange-400 whitespace-nowrap">
                            ₱{{ number_format($payments->sum('amount'), 2) }}
                        </td>
                        <td class="px-3 py-2 text-right font-bold text-gray-400 whitespace-nowrap">
                            ₱{{ number_format($payments->sum('ewt'), 2) }}
                        </td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- Pagination -->
        @if($payments->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $payments->links() }}
        </div>
        @endif

    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete_modal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-gray-800 border border-red-700 rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-5 border-b border-gray-700 flex items-center gap-3">
            <div class="bg-red-900/50 rounded-full p-2">
                <i class="fas fa-trash text-red-400 text-lg"></i>
            </div>
            <div>
                <h3 class="text-white font-bold text-base">Delete Payment</h3>
                <p class="text-gray-400 text-xs">This action is permanent and cannot be undone.</p>
            </div>
        </div>
        <div class="p-5">
            <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm space-y-1">
                <div class="flex justify-between">
                    <span class="text-gray-400">CR No:</span>
                    <span id="modal_cr_no" class="text-white font-mono font-semibold"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">DR No:</span>
                    <span id="modal_dr_no" class="text-white font-mono"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Amount:</span>
                    <span id="modal_amount" class="text-orange-400 font-bold"></span>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-300 mb-1">
                    Reason for deletion <span class="text-red-400">*</span>
                </label>
                <textarea id="delete_reason" rows="3" placeholder="Required: explain why this payment is being deleted..."
                          class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"></textarea>
                <p id="reason_error" class="text-red-400 text-xs mt-1 hidden">Please provide a reason.</p>
            </div>
            <form id="delete_form" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="delete_reason" id="delete_reason_input">
                <input type="hidden" name="redirect_back" value="{{ request()->fullUrl() }}">
                <div class="flex gap-2">
                    <button type="button" onclick="closeDeleteModal()"
                            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 rounded-lg text-sm font-medium">
                        Cancel
                    </button>
                    <button type="button" onclick="submitDelete()"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-2">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, crNo, drNo, amount) {
    document.getElementById('modal_cr_no').textContent = crNo;
    document.getElementById('modal_dr_no').textContent = drNo;
    document.getElementById('modal_amount').textContent = '₱' + amount;
    document.getElementById('delete_form').action = `/payments/${id}`;
    document.getElementById('delete_reason').value = '';
    document.getElementById('reason_error').classList.add('hidden');
    document.getElementById('delete_modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('delete_reason').focus(), 100);
}

function closeDeleteModal() {
    document.getElementById('delete_modal').classList.add('hidden');
}

function submitDelete() {
    const reason = document.getElementById('delete_reason').value.trim();
    if (!reason) {
        document.getElementById('reason_error').classList.remove('hidden');
        document.getElementById('delete_reason').focus();
        return;
    }
    document.getElementById('reason_error').classList.add('hidden');
    document.getElementById('delete_reason_input').value = reason;
    document.getElementById('delete_form').submit();
}

// Close modal on backdrop click
document.getElementById('delete_modal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>
@endsection
