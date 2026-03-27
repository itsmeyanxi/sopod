@extends('layouts.app')

@section('title', 'Aging Reports View')

@section('content')
<div class="container mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-6">

        <!-- Filters Section -->
        <div class="bg-gray-100 rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
                <div class="flex gap-2">
                    <a href="{{ route('aging_reports.summary') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded font-medium transition whitespace-nowrap flex items-center space-x-2">
                        <i class="fas fa-list"></i>
                        <span>View ALL Records</span>
                    </a>
                    <button type="submit" form="filter-form" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium transition whitespace-nowrap">
                        Apply Filters & View Summary
                    </button>
                </div>
            </div>
            <form id="filter-form" method="GET" action="{{ route('aging_reports.summary') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Record Date Filter (filters which records to show) -->
                <div>
                    <label for="filter_date" class="block text-sm font-medium text-gray-500 mb-2">
                        Record Date (On or Before)
                        <span class="text-xs text-gray-500 ml-1">(leave empty to show ALL records)</span>
                    </label>
                    <input type="date" id="filter_date" name="filter_date"
                           value="{{ request('filter_date') ?? now()->format('Y-m-d') }}"
                           class="w-full bg-white text-gray-800 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Leave empty for all records">
                </div>
            </form>

            {{-- Active Filters Display --}}
            @if(request()->has('filter_date') || request()->has('aging_date'))
            <div class="mt-4 flex items-center gap-2 flex-wrap">
                <span class="text-sm text-gray-500">Active filters:</span>

                @if(request()->has('filter_date'))
                <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs flex items-center gap-2">
                    Record Date ≤ {{ request('filter_date') }}
                </span>
                @endif

                @if(request()->has('aging_date'))
                <span class="bg-yellow-600 text-white px-3 py-1 rounded-full text-xs flex items-center gap-2">
                    ⚡ Aging Date: {{ request('aging_date') }}
                </span>
                @endif

                <a href="{{ route('aging_reports.view') }}"
                   class="text-xs text-red-700 hover:text-red-700 ml-2">
                    Clear all filters
                </a>
            </div>
            @endif
        </div>

        <!-- Search and Table Section -->
        <div class="bg-gray-100 rounded-lg p-6">
            <!-- Aging Date Filter (calculates age dynamically) -->
            <div class="mb-4">
                <label for="aging_date" class="block text-sm font-medium text-gray-500 mb-2">
                    Aging Date (As of Date)
                    <span class="text-xs text-yellow-700 ml-1">⚡ (age = days since counter date; leave empty to use each record's own record date)</span>
                </label>
                <input type="date" id="aging_date" name="aging_date" form="filter-form"
                       value="{{ request('aging_date') }}"
                       class="bg-white text-gray-800 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                       placeholder="Leave empty for record date">
            </div>

            <!-- Enhanced Search Bar -->
            <div class="mb-4 flex items-center space-x-4">
                <div class="flex-1 flex space-x-2">
                    <select id="search_type" class="bg-white text-gray-800 border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="customer">Customer / Branch</option>
                        <option value="invoice">Invoice No</option>
                        <option value="dr">DR Number</option>
                    </select>
                    <input type="text" id="search_input" placeholder="Search by Customer Name, Invoice No, or DR Number..."
                           class="flex-1 bg-white text-gray-800 border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="button" id="search_button" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>
                <button type="button" id="export_excel_btn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export to Excel</span>
                </button>
            </div>

            {{-- Record count --}}
            @if(isset($agingReports) && count($agingReports) > 0)
            <div class="mb-4">
                <p class="text-gray-500 text-sm">
                    <i class="fas fa-info-circle mr-1"></i>
                    Showing <strong>{{ count($agingReports) }}</strong> record(s)
                    @if(request('filter_date'))
                        with record date ≤ <strong>{{ request('filter_date') }}</strong>
                    @endif
                </p>
            </div>
            @endif

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-sm">
                            <th class="px-4 py-3 text-left">Aging Date</th>
                            <th class="px-4 py-3 text-left">Counter Date</th>
                            <th class="px-4 py-3 text-left">Invoice Date</th>
                            <th class="px-4 py-3 text-left">Record Date</th>
                            <th class="px-4 py-3 text-left">Sales Executive</th>
                            <th class="px-4 py-3 text-left">Client Name</th>
                            <th class="px-4 py-3 text-center">Invoice No</th>
                            <th class="px-4 py-3 text-right">Net AR</th>
                            <th class="px-4 py-3 text-center">Age</th>
                            <th class="px-4 py-3 text-center">Include</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-500">
                        @if(isset($agingReports) && count($agingReports) > 0)
                            @foreach($agingReports as $report)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $report['aging_date'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $report['counter_date'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $report['invoice_date'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $report['record_date'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $report['sales_executive'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $report['customer_name'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="bg-gray-100 px-2 py-1 rounded text-xs">
                                        {{ $report['invoice_no'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    ₱{{ number_format($report['net_ar'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php $age = $report['age'] ?? 0; @endphp
                                    <span class="
                                        @if($age <= 30) bg-green-600
                                        @elseif($age <= 60) bg-yellow-600
                                        @elseif($age <= 90) bg-orange-600
                                        @else bg-red-600
                                        @endif
                                        px-2 py-1 rounded text-xs font-semibold">
                                        {{ $age }} days
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if(($report['include_flag'] ?? 'yes') === 'yes')
                                        <span class="bg-green-600 text-white px-2 py-1 rounded text-xs font-medium">Yes</span>
                                    @else
                                        <span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-medium">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('aging_reports.ar_profile', ['id' => $report['id'] ?? '']) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs inline-block">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No aging reports found.</p>
                                    <p class="text-sm mt-2">
                                        @if(request()->has('filter_date'))
                                            Try adjusting your filters or <a href="{{ route('aging_reports.view') }}" class="text-blue-700 hover:underline">clear all filters</a>
                                        @else
                                            Apply filters above to view data.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('search_type').addEventListener('change', function() {
        const searchInput = document.getElementById('search_input');
        if (this.value === 'customer') {
            searchInput.placeholder = 'Search for customers...';
        } else if (this.value === 'invoice') {
            searchInput.placeholder = 'Search for invoice numbers...';
        } else if (this.value === 'dr') {
            searchInput.placeholder = 'Search for DR numbers...'; // ✅ NEW: DR search placeholder
        }
    });

    function generateArProfileUrl(reportId) {
        return `/aging-reports/ar-profile/${reportId}`;
    }
    
    // Search functionality
    document.getElementById('search_button').addEventListener('click', function() {
        const searchValue = document.getElementById('search_input').value;
        const searchType = document.getElementById('search_type').value;
        const filterDate = document.getElementById('filter_date').value;
        const agingDate = document.getElementById('aging_date').value;

        if (searchValue.trim() === '') {
            const searchTypeLabel = searchType === 'customer' ? 'customer name' : (searchType === 'invoice' ? 'invoice number' : 'DR number'); // ✅ NEW: DR type label
            Swal.fire({
                icon: 'warning',
                title: 'Empty Search',
                text: `Please enter a ${searchTypeLabel} to search.`,
                background: '#ffffff',
                color: '#1f2937'
            });
            return;
        }

        // Build query parameters
        const params = new URLSearchParams();
        params.append(searchType, searchValue);

        if (filterDate) params.append('filter_date', filterDate);
        if (agingDate) params.append('aging_date', agingDate);

        // AJAX search implementation
        fetch(`/aging-reports/search?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTable(data.data);
                    Swal.fire({
                        icon: 'success',
                        title: 'Search Complete',
                        text: `Found ${data.data.length} record(s)`,
                        background: '#ffffff',
                        color: '#1f2937',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Search Failed',
                        text: data.message,
                        background: '#ffffff',
                        color: '#1f2937'
                    });
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to search. Please try again.',
                    background: '#ffffff',
                    color: '#1f2937'
                });
            });
    });

    // Allow Enter key to trigger search
    document.getElementById('search_input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('search_button').click();
        }
    });

    // Auto-reload when aging date changes (recalculate ages dynamically)
    document.getElementById('aging_date').addEventListener('change', function() {
        // Reload the current page with the new aging_date parameter
        const agingDate = this.value;
        const filterDate = document.getElementById('filter_date').value;
        const include = document.getElementById('include_filter')?.value || 'all';

        // Build URL with current filters
        let url = window.location.pathname + '?';
        if (filterDate) url += 'filter_date=' + filterDate + '&';
        if (agingDate) url += 'aging_date=' + agingDate + '&';
        url += 'include=' + include;

        console.log('Reloading with aging_date:', agingDate);
        console.log('Full URL:', url);

        // Reload page with new parameters
        window.location.href = url;
    });

    // Export to Excel
    document.getElementById('export_excel_btn').addEventListener('click', function() {
        const filterDate = document.getElementById('filter_date').value;
        
        if (!filterDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Filter',
                text: 'Please select a record date filter before exporting.',
                background: '#ffffff',
                color: '#1f2937'
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Exporting...',
            text: 'Your Excel file will be downloaded shortly',
            background: '#ffffff',
            color: '#1f2937',
            timer: 2000,
            showConfirmButton: false
        });

        // Redirect to export route with filters
        window.location.href = `/aging-reports/export?filter_date=${filterDate}`;
    });
    
    function formatNumber(number) {
        return parseFloat(number).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Update table function
    function updateTable(data) {
        const tbody = document.querySelector('tbody');
        
        if (!data || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No aging reports found.</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        let rows = '';
        data.forEach(report => {
            const age = report.age || 0;
            let ageClass = 'bg-green-600';
            if (age > 90) ageClass = 'bg-red-600';
            else if (age > 60) ageClass = 'bg-orange-600';
            else if (age > 30) ageClass = 'bg-yellow-600';

            const includeFlag = report.include_flag || 'yes';
            const includeBadge = includeFlag === 'yes'
                ? '<span class="bg-green-600 text-white px-2 py-1 rounded text-xs font-medium">Yes</span>'
                : '<span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-medium">No</span>';

            const reportId = report.id || '';
            const viewUrl = reportId ? generateArProfileUrl(reportId) : '#';

            rows += `
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-4 py-3">${report.aging_date || 'N/A'}</td>
                    <td class="px-4 py-3">${report.counter_date || 'N/A'}</td>
                    <td class="px-4 py-3">${report.invoice_date || 'N/A'}</td>
                    <td class="px-4 py-3">${report.record_date || 'N/A'}</td>
                    <td class="px-4 py-3">${report.sales_executive || 'N/A'}</td>
                    <td class="px-4 py-3">${report.customer_name || 'N/A'}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs">
                            ${report.invoice_no || 'N/A'}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold">
                        ₱${formatNumber(report.net_ar || 0)}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="${ageClass} px-2 py-1 rounded text-xs font-semibold">
                            ${age} days
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        ${includeBadge}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="${viewUrl}"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs inline-block">
                            <i class="fas fa-eye mr-1"></i>View
                        </a>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = rows;
    }
</script>
@endsection