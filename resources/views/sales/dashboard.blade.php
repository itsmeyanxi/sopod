@extends('layouts.app')

@section('content')
<!-- Link CSS -->
<link rel="stylesheet" href="{{ asset('css/sales-dashboard.css') }}?v={{ filemtime(public_path('css/sales-dashboard.css')) }}">

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <!-- Header with Tabs -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Sales Analytics Dashboard</h1>
            
            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" onclick="switchTab('overview')">📊 Overview</button>
                <button class="tab-btn" onclick="switchTab('annual')">📅 Annual Report</button>
            </div>
        </div>

        <!-- OVERVIEW TAB -->
        <div id="overview-tab" class="tab-content active">
            <!-- Filter Section -->
            <form method="GET" action="{{ route('sales.dashboard') }}" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Year</label>
                    <select name="year" class="filter-select">
                        @for($i = 2020; $i <= date('Y') + 1; $i++)
                            <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}" class="filter-select">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}" class="filter-select">
                </div>
                <button type="submit" class="filter-button">Apply</button>
                <a href="{{ route('sales.dashboard') }}" class="filter-button" style="background:#6b7280;text-decoration:none;">Reset</a>
            </form>

            <!-- Metrics Grid -->
            <div class="metrics-grid">
                @php $rangeLabel = $dateFrom->format('M d, Y') . ' – ' . $dateTo->format('M d, Y'); @endphp

                <div class="metric-card blue-card">
                    <div class="metric-content">
                        <p class="metric-label">Sales (Selected Range)</p>
                        <h3 class="metric-value">₱{{ number_format($rangeSalesPHP ?? 0, 2) }}</h3>
                        <p class="metric-subtitle">{{ number_format($rangeSalesKG ?? 0, 2) }} KG</p>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>

                <div class="metric-card green-card">
                    <div class="metric-content">
                        <p class="metric-label">Weekly Sales</p>
                        <h3 class="metric-value">₱{{ number_format($weeklySalesPHP ?? 0, 2) }}</h3>
                        <p class="metric-subtitle">{{ number_format($weeklySalesKG ?? 0, 2) }} KG</p>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>

                <div class="metric-card purple-card">
                    <div class="metric-content">
                        <p class="metric-label">YTD Sales</p>
                        <h3 class="metric-value">₱{{ number_format($ytdSalesPHP ?? 0, 2) }}</h3>
                        <p class="metric-subtitle">{{ number_format($ytdSalesKG ?? 0, 2) }} KG</p>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>

                <div class="metric-card orange-card">
                    <div class="metric-content">
                        <p class="metric-label">Total Sales Orders</p>
                        <h3 class="metric-value">{{ number_format($totalSalesOrders ?? 0) }}</h3>
                        <p class="metric-subtitle">{{ $rangeLabel }}</p>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                </div>

                <div class="metric-card teal-card">
                    <div class="metric-content">
                        <p class="metric-label">Delivered Orders</p>
                        <h3 class="metric-value">{{ number_format($deliveredCount ?? 0) }}</h3>
                        <p class="metric-subtitle">{{ $rangeLabel }}</p>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>

                <div class="metric-card red-card">
                    <div class="metric-content">
                        <p class="metric-label">Pending Orders</p>
                        <h3 class="metric-value">{{ number_format($pendingSO ?? 0) }}</h3>
                        <p class="metric-subtitle">{{ $rangeLabel }}</p>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="status-section">
                <div class="section-card">
                    <div class="section-header">
                        <h5>📊 Delivery Status Overview</h5>
                    </div>
                    <div class="status-grid">
                        @php
                            $statusConfig = [
                                'pending' => ['icon' => '⏳', 'color' => '#f59e0b', 'label' => 'Pending'],
                                'processing' => ['icon' => '🔄', 'color' => '#3b82f6', 'label' => 'Processing'],
                                'delivered' => ['icon' => '✅', 'color' => '#10b981', 'label' => 'Delivered'],
                                'completed' => ['icon' => '🎉', 'color' => '#8b5cf6', 'label' => 'Completed'],
                                'cancelled' => ['icon' => '❌', 'color' => '#ef4444', 'label' => 'Cancelled'],
                            ];
                            $salesByStatus = $salesByStatus ?? collect();
                            $totalDeliveries = $salesByStatus->sum('count');
                        @endphp
                        
                        @forelse($salesByStatus as $status)
                            @php
                                $statusKey = strtolower($status->status ?? '');
                                $config = $statusConfig[$statusKey] ?? ['icon' => '📋', 'color' => '#6b7280', 'label' => ucfirst($statusKey)];
                                $percentage = $totalDeliveries > 0 ? ($status->count / $totalDeliveries) * 100 : 0;
                            @endphp
                            <div class="status-item" style="border-color: {{ $config['color'] }};">
                                <div class="status-icon">{{ $config['icon'] }}</div>
                                <div class="status-label">{{ $config['label'] }}</div>
                                <div class="status-count">{{ $status->count }}</div>
                                <div class="status-percent">{{ number_format($percentage, 1) }}%</div>
                            </div>
                        @empty
                            <p class="empty-state">No status data available</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="two-column-grid">
                @include('partials.top-customers', ['topCustomers' => $topCustomers ?? collect(), 'year' => $year ?? date('Y')])
                @include('partials.top-items', ['topItems' => $topItems ?? collect(), 'year' => $year ?? date('Y')])
            </div>

            <!-- Monthly Breakdown -->
            @include('partials.monthly-breakdown', [
                'monthlyDataPHP' => $monthlyDataPHP ?? array_fill(0, 12, 0),
                'monthlyDataKG' => $monthlyDataKG ?? array_fill(0, 12, 0),
                'year' => $year ?? date('Y'),
                'monthlyInternalPHP' => $monthlyInternalPHP ?? array_fill(0, 12, 0),
                'monthlyInternalKG'  => $monthlyInternalKG  ?? array_fill(0, 12, 0),
                'monthlyExternalPHP' => $monthlyExternalPHP ?? array_fill(0, 12, 0),
                'monthlyExternalKG'  => $monthlyExternalKG  ?? array_fill(0, 12, 0),
            ])

            <!-- Recent Deliveries -->
            @include('partials.recent-deliveries', ['recentDeliveries' => $recentDeliveries ?? collect()])
        </div>

        <!-- ANNUAL REPORT TAB -->
        <div id="annual-tab" class="tab-content">
            @include('partials.annual-report', [
                'selectedAnnualYear' => $selectedAnnualYear ?? date('Y'),
                'annualData' => $annualData ?? array_fill(0, 12, ['php' => 0, 'kg' => 0, 'orders' => 0]),
                'annualTotalPHP' => $annualTotalPHP ?? 0,
                'annualTotalKG' => $annualTotalKG ?? 0
            ])
        </div>
    </div>
</div>

<!-- Link JavaScript -->
<script src="{{ asset('js/sales-dashboard.js') }}"></script>
@endsection