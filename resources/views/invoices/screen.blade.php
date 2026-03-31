@extends('layouts.app')

@section('title', 'AR Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        
        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-chart-line text-blue-700"></i>
                AR Aging Dashboard
            </h2>
            <p class="text-gray-300 text-sm mt-1">Comprehensive overview of accounts receivable aging</p>
        </div>

        {{-- Week Selection & Date Display --}}
        <div class="bg-gray-700 rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Select Week End Date</label>
                    <input type="date" id="week_end_date" value="{{ now()->format('Y-m-d') }}"
                           class="w-full bg-gray-600 text-white border border-gray-600 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="bg-gray-800 rounded-lg p-4 flex flex-col justify-center">
                    <p class="text-gray-300 text-xs mb-1">Week Number</p>
                    <p class="text-white text-xl font-bold" id="week_number">{{ now()->format('Y') }} - {{ now()->weekOfYear }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 flex flex-col justify-center">
                    <p class="text-gray-300 text-xs mb-1">Week Beginning Date</p>
                    <p class="text-white text-xl font-bold" id="week_beg_date">{{ now()->startOfWeek()->format('F j, Y') }}</p>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="button" id="load_dashboard_btn" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium transition">
                    <i class="fas fa-sync-alt mr-2"></i>Load Dashboard
                </button>
            </div>
        </div>

        {{-- Main Dashboard Content --}}
        <div id="dashboard_content" class="space-y-6">
            
            {{-- Loading State --}}
            <div id="loading_state" class="text-center py-12 hidden">
                <i class="fas fa-spinner fa-spin text-blue-700 text-4xl mb-4"></i>
                <p class="text-gray-300">Loading dashboard data...</p>
            </div>

            {{-- AR Balance Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="summary_cards">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-blue-700">Total Records</h3>
                        <i class="fas fa-users text-blue-700 text-xl"></i>
                    </div>
                    <p class="text-white text-3xl font-bold" id="trader_balance">-</p>
                    <p class="text-gray-300 text-xs mt-1">Active AR records</p>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-green-700">Outstanding AR</h3>
                        <i class="fas fa-dollar-sign text-green-700 text-xl"></i>
                    </div>
                    <p class="text-white text-3xl font-bold" id="credit_balance">-</p>
                    <p class="text-gray-300 text-xs mt-1">Total outstanding receivables</p>
                </div>

                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-purple-700">Total AR</h3>
                        <i class="fas fa-chart-bar text-purple-700 text-xl"></i>
                    </div>
                    <p class="text-white text-3xl font-bold" id="total_ar">-</p>
                    <p class="text-gray-300 text-xs mt-1">As of <span id="ar_date">{{ now()->format('F j, Y') }}</span></p>
                </div>
            </div>

            {{-- Two Column Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- Left Column: AR Aging Breakdown --}}
                <div class="bg-gray-700 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-hourglass-half text-orange-700"></i>
                        AR Aging Breakdown
                    </h3>
                    <div class="space-y-3" id="aging_breakdown">
                        <div class="text-center text-gray-300 py-8">
                            <i class="fas fa-info-circle text-3xl mb-2"></i>
                            <p>Click "Load Dashboard" to view data</p>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Charts and Stats --}}
                <div class="space-y-6">
                    {{-- Aging Distribution Chart --}}
                    <div class="bg-gray-700 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-chart-pie text-purple-700"></i>
                            Aging Distribution
                        </h3>
                        <div class="space-y-2" id="aging_distribution">
                            <div class="text-center text-gray-300 py-8">
                                <i class="fas fa-chart-bar text-3xl mb-2"></i>
                                <p>Load data to view distribution</p>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" id="view_summary_btn" class="bg-gradient-to-br from-indigo-900/40 to-indigo-800/30 border border-indigo-200 rounded-lg p-4 hover:bg-indigo-800/50 transition">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-table text-indigo-700 text-2xl"></i>
                                <span class="text-xs text-indigo-700 font-semibold">VIEW</span>
                            </div>
                            <p class="text-white text-sm font-bold">Summary Report</p>
                            <p class="text-gray-300 text-xs mt-1">Pivot table view</p>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Export Buttons --}}
            <div class="flex justify-end gap-3">
                <button type="button" id="export_summary_btn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded font-medium transition">
                    <i class="fas fa-file-excel mr-2"></i>Export Summary
                </button>
                <button type="button" id="export_details_btn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium transition">
                    <i class="fas fa-download mr-2"></i>Export Details
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const weekEndDateInput = document.getElementById('week_end_date');
    const loadDashboardBtn = document.getElementById('load_dashboard_btn');
    const viewSummaryBtn = document.getElementById('view_summary_btn');
    const exportSummaryBtn = document.getElementById('export_summary_btn');
    const exportDetailsBtn = document.getElementById('export_details_btn');

    // Auto-calculate week number from date
    weekEndDateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const start = new Date(selectedDate.getFullYear(), 0, 1);
        const diff = selectedDate - start;
        const oneWeek = 1000 * 60 * 60 * 24 * 7;
        const weekNum = Math.ceil(diff / oneWeek);
        
        document.getElementById('week_number').textContent = `${selectedDate.getFullYear()} - ${weekNum}`;
        
        const weekBeg = new Date(selectedDate);
        weekBeg.setDate(selectedDate.getDate() - 6);
        document.getElementById('week_beg_date').textContent = weekBeg.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    });

    // Load Dashboard
    loadDashboardBtn.addEventListener('click', function() {
        const weekEndDate = weekEndDateInput.value;
        
        if (!weekEndDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Date',
                text: 'Please select a week end date.',
                background: '#ffffff',
                color: '#1f2937'
            });
            return;
        }

        showLoading();
        loadDashboardData(weekEndDate);
    });

    // View Summary Report
    viewSummaryBtn.addEventListener('click', function() {
        const weekEndDate = weekEndDateInput.value;
        
        if (!weekEndDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Date',
                text: 'Please select a date before viewing the summary.',
                background: '#ffffff',
                color: '#1f2937'
            });
            return;
        }

        window.location.href = `/aging-reports/summary?filter_date=${weekEndDate}&aging_date=${weekEndDate}`;
    });

    // View Details Report
    viewDetailsBtn.addEventListener('click', function() {
        const weekEndDate = weekEndDateInput.value;

        if (!weekEndDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Date',
                text: 'Please select a date before viewing details.',
                background: '#ffffff',
                color: '#1f2937'
            });
            return;
        }

        window.location.href = `/aging-reports?filter_date=${weekEndDate}`;
    });

    // Export Summary
    exportSummaryBtn.addEventListener('click', function() {
        const weekEndDate = weekEndDateInput.value;

        if (!weekEndDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Date',
                text: 'Please select a date before exporting.',
                background: '#ffffff',
                color: '#1f2937'
            });
            return;
        }

        performExport(`/aging-reports/export-ar-aging?filter_date=${weekEndDate}`, 'ar_aging_summary', exportSummaryBtn);
    });

    // Export Details
    exportDetailsBtn.addEventListener('click', function() {
        const weekEndDate = weekEndDateInput.value;

        if (!weekEndDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Date',
                text: 'Please select a date before exporting.',
                background: '#ffffff',
                color: '#1f2937'
            });
            return;
        }

        performExport(`/aging-reports/export?filter_date=${weekEndDate}`, 'ar_aging_details', exportDetailsBtn);
    });

    // Robust export function
    function performExport(url, filename, btn) {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
        btn.disabled = true;

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel, text/csv'
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

            // Generate filename with date
            const date = new Date().toISOString().split('T')[0];
            const ext = filename.includes('details') ? '.xlsx' : '.xlsx';
            link.download = `${filename}_${date}${ext}`;

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);

            Swal.fire({
                icon: 'success',
                title: 'Export Successful!',
                text: 'Your file has been downloaded.',
                background: '#1f2937',
                color: '#fff',
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error('Export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'Failed to download file. Please try again or check your connection.',
                background: '#1f2937',
                color: '#fff'
            });
        })
        .finally(() => {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }

    // Load Dashboard Data
    function loadDashboardData(filterDate) {
        fetch(`/aging-reports/ar-aging?filter_date=${filterDate}&aging_date=${filterDate}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    updateDashboard(data);
                    Swal.fire({
                        icon: 'success',
                        title: 'Dashboard Loaded',
                        text: 'AR aging data has been updated',
                        background: '#ffffff',
                        color: '#1f2937',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to load dashboard data',
                        background: '#ffffff',
                        color: '#1f2937'
                    });
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch dashboard data',
                    background: '#ffffff',
                    color: '#1f2937'
                });
            });
    }

    // Update Dashboard UI
    function updateDashboard(data) {
        const grandTotals = data.grand_totals;
        const agingData = data.aging_data;

        // Update summary cards
        document.getElementById('trader_balance').textContent = agingData.length.toLocaleString();
        document.getElementById('credit_balance').textContent = formatCurrency(grandTotals.total);
        document.getElementById('total_ar').textContent = formatCurrency(grandTotals.total);
        document.getElementById('ar_date').textContent = new Date(weekEndDateInput.value).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Update aging breakdown
        updateAgingBreakdown(grandTotals);

        // Update aging distribution
        updateAgingDistribution(grandTotals);
    }

    // Update Aging Breakdown
    function updateAgingBreakdown(totals) {
        const breakdownHTML = `
            <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <p class="text-gray-300 text-sm">Current</p>
                    <p class="text-white text-2xl font-bold">${formatCurrency(totals.current)}</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-green-600/20 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-700 text-2xl"></i>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <p class="text-gray-300 text-sm">1-30 days</p>
                    <p class="text-white text-2xl font-bold">${formatCurrency(totals['1_30'])}</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-yellow-600/20 flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-700 text-2xl"></i>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <p class="text-gray-300 text-sm">31-60 days</p>
                    <p class="text-white text-2xl font-bold">${formatCurrency(totals['31_60'])}</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-orange-600/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-orange-700 text-2xl"></i>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <p class="text-gray-300 text-sm">61-90 days</p>
                    <p class="text-white text-2xl font-bold">${formatCurrency(totals['61_90'])}</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-red-600/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-700 text-2xl"></i>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <p class="text-gray-300 text-sm">91-120 days</p>
                    <p class="text-white text-2xl font-bold">${formatCurrency(totals['91_120'])}</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-red-700/20 flex items-center justify-center">
                    <i class="fas fa-ban text-red-500 text-2xl"></i>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <p class="text-gray-300 text-sm">More than 120 Days</p>
                    <p class="text-white text-2xl font-bold">${formatCurrency(totals.over_120)}</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-900/50 to-purple-900/50 rounded-lg p-4 flex justify-between items-center border-2 border-blue-500">
                <div>
                    <p class="text-blue-700 text-sm font-semibold">Total</p>
                    <p class="text-white text-3xl font-bold">${formatCurrency(totals.total)}</p>
                </div>
                <i class="fas fa-calculator text-blue-700 text-3xl"></i>
            </div>
        `;
        
        document.getElementById('aging_breakdown').innerHTML = breakdownHTML;
    }

    // Update Aging Distribution
    function updateAgingDistribution(totals) {
        const total = totals.total || 1;
        
        const distributionHTML = `
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">Current</span>
                    <span class="text-white font-semibold">${((totals.current / total) * 100).toFixed(1)}%</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-3">
                    <div class="bg-green-500 h-3 rounded-full" style="width: ${(totals.current / total) * 100}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">1-30 days</span>
                    <span class="text-white font-semibold">${((totals['1_30'] / total) * 100).toFixed(1)}%</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-3">
                    <div class="bg-yellow-500 h-3 rounded-full" style="width: ${(totals['1_30'] / total) * 100}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">31-60 days</span>
                    <span class="text-white font-semibold">${((totals['31_60'] / total) * 100).toFixed(1)}%</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-3">
                    <div class="bg-orange-500 h-3 rounded-full" style="width: ${(totals['31_60'] / total) * 100}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">61-90 days</span>
                    <span class="text-white font-semibold">${((totals['61_90'] / total) * 100).toFixed(1)}%</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-3">
                    <div class="bg-red-500 h-3 rounded-full" style="width: ${(totals['61_90'] / total) * 100}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">91-120 days</span>
                    <span class="text-white font-semibold">${((totals['91_120'] / total) * 100).toFixed(1)}%</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-3">
                    <div class="bg-red-600 h-3 rounded-full" style="width: ${(totals['91_120'] / total) * 100}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">>120 days</span>
                    <span class="text-white font-semibold">${((totals.over_120 / total) * 100).toFixed(1)}%</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-3">
                    <div class="bg-red-700 h-3 rounded-full" style="width: ${(totals.over_120 / total) * 100}%"></div>
                </div>
            </div>
        `;
        
        document.getElementById('aging_distribution').innerHTML = distributionHTML;
    }

    // Helper Functions
    function formatCurrency(amount) {
        return '₱' + Number(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function showLoading() {
        document.getElementById('loading_state').classList.remove('hidden');
        document.getElementById('summary_cards').classList.add('opacity-50');
    }

    function hideLoading() {
        document.getElementById('loading_state').classList.add('hidden');
        document.getElementById('summary_cards').classList.remove('opacity-50');
    }
});
</script>
@endsection