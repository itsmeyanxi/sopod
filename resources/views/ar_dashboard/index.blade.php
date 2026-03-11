@extends('layouts.app')

@section('title', 'AR Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-white">Accounts Receivable Dashboard</h1>
                <p class="text-gray-400 text-sm mt-1">Last updated: {{ now()->format('M d, Y • h:i A') }}</p>
            </div>
            <div class="flex gap-3">
                <button onclick="downloadExport('{{ route('ar_dashboard.export_summary') }}')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition flex items-center gap-2" title="Download CSV summary report">
                    <i class="fas fa-file-csv"></i> Export Summary
                </button>
                <button onclick="downloadExport('{{ route('ar_dashboard.export_details') }}')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition flex items-center gap-2" title="Download detailed CSV report">
                    <i class="fas fa-file-csv"></i> Export Details
                </button>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-red-900 rounded-lg p-4 border-l-4 border-red-500">
                <p class="text-gray-300 text-sm font-semibold">Overdue Count</p>
                <p class="text-3xl font-bold text-red-400">{{ $overdueCount }}</p>
            </div>
            <div class="bg-red-900 rounded-lg p-4 border-l-4 border-red-500">
                <p class="text-gray-300 text-sm font-semibold">Total Overdue Amount</p>
                <p class="text-2xl font-bold text-red-400">₱{{ number_format($totalOverdue, 2) }}</p>
            </div>
            <div class="bg-yellow-900 rounded-lg p-4 border-l-4 border-yellow-500">
                <p class="text-gray-300 text-sm font-semibold">Upcoming Due Count</p>
                <p class="text-3xl font-bold text-yellow-400">{{ $upcomingCount }}</p>
            </div>
            <div class="bg-yellow-900 rounded-lg p-4 border-l-4 border-yellow-500">
                <p class="text-gray-300 text-sm font-semibold">Total Upcoming Amount</p>
                <p class="text-2xl font-bold text-yellow-400">₱{{ number_format($totalUpcoming, 2) }}</p>
            </div>
        </div>

        <!-- Overdue Invoices Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-red-400 mb-4 border-b border-gray-700 pb-2">
                <i class="fas fa-exclamation-circle mr-2"></i> Overdue Invoices
            </h2>
            @if($overdueInvoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-700">
                        <thead class="bg-red-700 text-white">
                            <tr>
                                <th class="border border-gray-700 px-4 py-2">APV No</th>
                                <th class="border border-gray-700 px-4 py-2">Vendor</th>
                                <th class="border border-gray-700 px-4 py-2">Document Date</th>
                                <th class="border border-gray-700 px-4 py-2">Due Date</th>
                                <th class="border border-gray-700 px-4 py-2 text-center">Days Overdue</th>
                                <th class="border border-gray-700 px-4 py-2 text-right">Amount</th>
                                <th class="border border-gray-700 px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdueInvoices as $invoice)
                            <tr class="hover:bg-gray-700 border-b border-gray-700">
                                <td class="border border-gray-700 px-4 py-2">
                                    <a href="{{ route('accounts_payable_invoices.show', $invoice->id) }}" class="text-blue-400 hover:underline font-mono">
                                        {{ $invoice->apv_no }}
                                    </a>
                                </td>
                                <td class="border border-gray-700 px-4 py-2">{{ $invoice->vendor_name }}</td>
                                <td class="border border-gray-700 px-4 py-2 text-sm">{{ $invoice->document_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-2 text-sm font-semibold">{{ $invoice->due_date->format('M d, Y') }}</td>
                                <td class="border border-gray-700 px-4 py-2 text-center text-red-400 font-bold">
                                    <span class="bg-red-900 px-2 py-1 rounded">{{ now()->diffInDays($invoice->due_date) }}</span>
                                </td>
                                <td class="border border-gray-700 px-4 py-2 text-right font-semibold">₱{{ number_format($invoice->grand_total, 2) }}</td>
                                <td class="border border-gray-700 px-4 py-2">
                                    <span class="bg-red-600 text-white px-3 py-1 rounded text-sm">{{ ucfirst($invoice->status) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-gray-700 rounded-lg p-6 text-center">
                    <i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i>
                    <p class="text-gray-300">No overdue invoices. Great job!</p>
                </div>
            @endif
        </div>

        <!-- Upcoming Due Invoices Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-yellow-400 mb-4 border-b border-gray-700 pb-2">
                <i class="fas fa-clock mr-2"></i> Upcoming Due Invoices (Within 7 Days)
            </h2>
            @if($upcomingDue7Days->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-700">
                        <thead class="bg-yellow-700 text-white">
                            <tr>
                                <th class="border border-gray-700 px-4 py-2">APV No</th>
                                <th class="border border-gray-700 px-4 py-2">Vendor</th>
                                <th class="border border-gray-700 px-4 py-2">Document Date</th>
                                <th class="border border-gray-700 px-4 py-2">Due Date</th>
                                <th class="border border-gray-700 px-4 py-2 text-center">Days Until Due</th>
                                <th class="border border-gray-700 px-4 py-2 text-right">Amount</th>
                                <th class="border border-gray-700 px-4 py-2">Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingDue7Days as $invoice)
                            <tr class="hover:bg-gray-700 border-b border-gray-700">
                                <td class="border border-gray-700 px-4 py-2">
                                    <a href="{{ route('accounts_payable_invoices.show', $invoice->id) }}" class="text-blue-400 hover:underline font-mono">
                                        {{ $invoice->apv_no }}
                                    </a>
                                </td>
                                <td class="border border-gray-700 px-4 py-2">{{ $invoice->vendor_name }}</td>
                                <td class="border border-gray-700 px-4 py-2 text-sm">{{ $invoice->document_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="border border-gray-700 px-4 py-2 text-sm font-semibold">{{ $invoice->due_date->format('M d, Y') }}</td>
                                <td class="border border-gray-700 px-4 py-2 text-center font-bold">
                                    <span class="bg-yellow-900 px-2 py-1 rounded">{{ $invoice->days_until_due }}</span>
                                </td>
                                <td class="border border-gray-700 px-4 py-2 text-right font-semibold">₱{{ number_format($invoice->grand_total, 2) }}</td>
                                <td class="border border-gray-700 px-4 py-2">
                                    @if($invoice->urgency === 'urgent')
                                        <span class="bg-red-600 text-white px-3 py-1 rounded text-sm font-semibold">🔴 URGENT (≤3 days)</span>
                                    @else
                                        <span class="bg-yellow-600 text-white px-3 py-1 rounded text-sm font-semibold">🟡 WARNING (4-7 days)</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-gray-700 rounded-lg p-6 text-center">
                    <i class="fas fa-thumbs-up text-blue-400 text-3xl mb-2"></i>
                    <p class="text-gray-300">No invoices due in the next 7 days.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function downloadExport(url) {
    // Show loading indicator
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';
    btn.disabled = true;

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'text/csv'
        },
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.blob();
    })
    .then(blob => {
        // Create download link from blob
        const downloadUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = downloadUrl;

        // Extract filename from Content-Disposition header or create one
        const filename = url.includes('export-summary')
            ? `ar_summary_${new Date().toISOString().split('T')[0]}.csv`
            : `ar_details_${new Date().toISOString().split('T')[0]}.csv`;

        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(downloadUrl);
    })
    .catch(error => {
        console.error('Download error:', error);
        alert('Failed to download file. Please try again.');
    })
    .finally(() => {
        // Restore button state
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
@endsection
