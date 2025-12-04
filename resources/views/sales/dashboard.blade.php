@extends('layouts.app')

@section('content')
<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Sales Analytics Dashboard</h1>
            
            <!-- Filter Section -->
            <form method="GET" action="{{ route('sales.dashboard') }}" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Year</label>
                    <select name="year" class="filter-select">
                        @for($i = 2020; $i <= date('Y') + 1; $i++)
                            <option value="{{ $i }}" {{ (isset($year) && $year == $i) ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Month</label>
                    <select name="month" class="filter-select">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ (isset($month) && $month == $i) ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="filter-button">Apply</button>
            </form>
        </div>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <!-- Monthly Sales -->
            <div class="metric-card blue-card">
                <div class="metric-content">
                    <p class="metric-label">Monthly Sales</p>
                    <h3 class="metric-value">₱{{ number_format($monthlySalesPHP ?? 0, 2) }}</h3>
                    <p class="metric-subtitle">{{ number_format($monthlySalesKG ?? 0, 2) }} KG</p>
                </div>
                <div class="metric-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>

            <!-- Weekly Sales -->
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

            <!-- YTD Sales -->
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

            <!-- Total Sales Orders -->
            <div class="metric-card orange-card">
                <div class="metric-content">
                    <p class="metric-label">Total Sales Orders</p>
                    <h3 class="metric-value">{{ number_format($totalSalesOrders ?? 0) }}</h3>
                    <p class="metric-subtitle">All time</p>
                </div>
                <div class="metric-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </div>

            <!-- Delivered Orders -->
            <div class="metric-card teal-card">
                <div class="metric-content">
                    <p class="metric-label">Delivered Orders</p>
                    <h3 class="metric-value">{{ number_format($deliveredCount ?? 0) }}</h3>
                    <p class="metric-subtitle">Completed</p>
                </div>
                <div class="metric-icon">
                    <i class="fas fa-truck"></i>
                </div>
            </div>

            <!-- Pending Orders -->
            <div class="metric-card red-card">
                <div class="metric-content">
                    <p class="metric-label">Pending Orders</p>
                    <h3 class="metric-value">{{ number_format($pendingSO ?? 0) }}</h3>
                    <p class="metric-subtitle">Awaiting processing</p>
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
                            $config = $statusConfig[$status->status] ?? ['icon' => '📋', 'color' => '#6b7280', 'label' => ucfirst($status->status)];
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
            <!-- Top Customers -->
            <div class="section-card">
                <div class="section-header">
                    <h5>🏆 Top 5 Customers ({{ $year ?? date('Y') }})</h5>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Customer Name</th>
                                <th class="text-right">Total Sales</th>
                                <th class="text-right">% Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $topCustomers = $topCustomers ?? collect();
                                $totalRevenue = $topCustomers->sum('total_sales');
                                $badges = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                            @endphp
                            @forelse($topCustomers as $index => $customer)
                            <tr>
                                <td class="text-center badge-cell">{{ $badges[$index] ?? '📍' }}</td>
                                <td class="customer-name">{{ $customer->customer_name }}</td>
                                <td class="text-right">₱{{ number_format($customer->total_sales, 2) }}</td>
                                <td class="text-right">
                                    <span class="percent-badge">
                                        {{ $totalRevenue > 0 ? number_format(($customer->total_sales / $totalRevenue) * 100, 1) : 0 }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="empty-state">No customer data available</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Items -->
            <div class="section-card">
                <div class="section-header">
                    <h5>📦 Top 5 Items by Quantity ({{ $year ?? date('Y') }})</h5>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Item Description</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $topItems = $topItems ?? collect();
                                $ranks = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                            @endphp
                            @forelse($topItems as $index => $item)
                            <tr>
                                <td class="text-center badge-cell">{{ $ranks[$index] ?? '📍' }}</td>
                                <td class="item-name">{{ $item->item_description }}</td>
                                <td class="text-right">{{ number_format($item->total_quantity, 2) }} KG</td>
                                <td class="text-right">
                                    <span class="code-badge">{{ $item->item_code }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="empty-state">No item data available</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <div class="section-card">
            <div class="section-header">
                <h5>📅 Monthly Sales Breakdown ({{ $year ?? date('Y') }})</h5>
            </div>
            <div class="table-container">
                <table class="data-table monthly-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Sales (PHP)</th>
                            <th class="text-right">Sales (KG)</th>
                            <th class="text-right">Avg/Day (PHP)</th>
                            <th class="text-center">Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                      'July', 'August', 'September', 'October', 'November', 'December'];
                            $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                            $monthlyDataPHP = $monthlyDataPHP ?? array_fill(0, 12, 0);
                            $monthlyDataKG = $monthlyDataKG ?? array_fill(0, 12, 0);
                        @endphp
                        @foreach($monthlyDataPHP as $index => $salesPHP)
                            @php
                                $salesKG = $monthlyDataKG[$index] ?? 0;
                                $prevSales = $index > 0 ? ($monthlyDataPHP[$index - 1] ?? 0) : 0;
                                $trend = $prevSales > 0 ? (($salesPHP - $prevSales) / $prevSales) * 100 : 0;
                                $trendIcon = $trend > 0 ? '📈' : ($trend < 0 ? '📉' : '➖');
                                $trendClass = $trend > 0 ? 'trend-up' : ($trend < 0 ? 'trend-down' : 'trend-neutral');
                                $avgPerDay = $salesPHP > 0 ? $salesPHP / $daysInMonth[$index] : 0;
                            @endphp
                            <tr>
                                <td class="month-name">{{ $months[$index] }}</td>
                                <td class="text-right">₱{{ number_format($salesPHP, 2) }}</td>
                                <td class="text-right">{{ number_format($salesKG, 2) }} KG</td>
                                <td class="text-right">₱{{ number_format($avgPerDay, 2) }}</td>
                                <td class="text-center">
                                    @if($index > 0 && $prevSales > 0)
                                        <span class="trend-badge {{ $trendClass }}">
                                            {{ $trendIcon }} {{ number_format(abs($trend), 1) }}%
                                        </span>
                                    @else
                                        <span class="trend-neutral">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td class="month-name">TOTAL</td>
                            <td class="text-right">₱{{ number_format(array_sum($monthlyDataPHP), 2) }}</td>
                            <td class="text-right">{{ number_format(array_sum($monthlyDataKG), 2) }} KG</td>
                            <td class="text-right">-</td>
                            <td class="text-center">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Deliveries -->
        <div class="section-card">
            <div class="section-header">
                <h5>🚚 Recent Deliveries</h5>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Item</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $recentDeliveries = $recentDeliveries ?? collect();
                        @endphp
                        @forelse($recentDeliveries as $delivery)
                        <tr>
                            <td>{{ $delivery->created_at->format('M d, Y') }}</td>
                            <td>{{ $delivery->customer_name }}</td>
                            <td>{{ $delivery->item_description }}</td>
                            <td class="text-right">{{ number_format($delivery->quantity, 2) }} KG</td>
                            <td class="text-right">₱{{ number_format($delivery->total_amount, 2) }}</td>
                            <td class="text-center">
                                @php
                                    $statusClasses = [
                                        'pending' => 'status-warning',
                                        'processing' => 'status-info',
                                        'delivered' => 'status-success',
                                        'completed' => 'status-primary',
                                        'cancelled' => 'status-danger',
                                    ];
                                    $statusClass = $statusClasses[$delivery->status] ?? 'status-secondary';
                                @endphp
                                <span class="status-badge {{ $statusClass }}">{{ ucfirst($delivery->status) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="empty-state">No recent deliveries</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-wrapper {
        background-color: #1a1d2e;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    /* Header */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .dashboard-title {
        color: white;
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
    }
    
    .filter-form {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        color: #9ca3af;
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
        font-weight: 500;
    }
    
    .filter-select {
        background-color: #22263a;
        border: 1px solid #374151;
        color: #e5e7eb;
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        min-width: 120px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .filter-button {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 0.375rem;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .filter-button:hover {
        transform: translateY(-1px);
    }
    
    /* Metrics Grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .metric-card {
        border-radius: 0.75rem;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        min-height: 130px;
    }
    
    .metric-content {
        position: relative;
        z-index: 2;
    }
    
    .metric-label {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.875rem;
        font-weight: 500;
        margin: 0 0 0.5rem 0;
    }
    
    .metric-value {
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.25rem 0;
        line-height: 1;
    }
    
    .metric-subtitle {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.875rem;
        margin: 0;
    }
    
    .metric-icon {
        font-size: 3rem;
        opacity: 0.25;
        position: absolute;
        right: 1.5rem;
        z-index: 1;
    }
    
    .blue-card { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .green-card { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .purple-card { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    .orange-card { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
    .teal-card { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); }
    .red-card { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    
    /* Status Section */
    .status-section {
        margin-bottom: 2rem;
    }
    
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .status-item {
        background-color: #22263a;
        border: 2px solid;
        border-radius: 0.5rem;
        padding: 1.25rem;
        text-align: center;
    }
    
    .status-icon {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    
    .status-label {
        color: #9ca3af;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    
    .status-count {
        color: white;
        font-size: 1.875rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .status-percent {
        color: #6b7280;
        font-size: 0.875rem;
    }
    
    /* Section Cards */
    .section-card {
        background-color: #22263a;
        border: 1px solid #374151;
        border-radius: 0.75rem;
        margin-bottom: 2rem;
        overflow: hidden;
    }
    
    .section-header {
        background-color: #1e2235;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #374151;
    }
    
    .section-header h5 {
        color: white;
        font-size: 1.125rem;
        font-weight: 600;
        margin: 0;
    }
    
    /* Two Column Grid */
    .two-column-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    /* Tables */
    .table-container {
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table thead th {
        background-color: #1e2235;
        color: #9ca3af;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.875rem 1rem;
        text-align: left;
        border-bottom: 1px solid #374151;
    }
    
    .data-table tbody tr {
        border-bottom: 1px solid #2d3246;
        transition: background-color 0.2s;
    }
    
    .data-table tbody tr:hover {
        background-color: #2a2d3e;
    }
    
    .data-table tbody td {
        color: #e5e7eb;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    
    .badge-cell {
        font-size: 1.5rem;
    }
    
    .customer-name,
    .item-name,
    .month-name {
        font-weight: 600;
        color: white;
    }
    
    .percent-badge {
        background-color: #3b82f6;
        color: white;
        padding: 0.25rem 0.625rem;
        border-radius: 0.375rem;
        font-size: 0.813rem;
        font-weight: 600;
    }
    
    .code-badge {
        background-color: #6b7280;
        color: white;
        padding: 0.25rem 0.625rem;
        border-radius: 0.375rem;
        font-size: 0.813rem;
        font-weight: 500;
    }
    
    .trend-badge {
        padding: 0.25rem 0.625rem;
        border-radius: 0.375rem;
        font-size: 0.813rem;
        font-weight: 600;
    }
    
    .trend-up { background-color: #10b981; color: white; }
    .trend-down { background-color: #ef4444; color: white; }
    .trend-neutral { color: #6b7280; }
    
    .status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.813rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-warning { background-color: #f59e0b; color: white; }
    .status-info { background-color: #3b82f6; color: white; }
    .status-success { background-color: #10b981; color: white; }
    .status-primary { background-color: #8b5cf6; color: white; }
    .status-danger { background-color: #ef4444; color: white; }
    .status-secondary { background-color: #6b7280; color: white; }
    
    .total-row {
        background-color: #2a2d3e;
        font-weight: 700;
    }
    
    .total-row td {
        color: white !important;
        padding: 1rem;
    }
    
    .empty-state {
        text-align: center;
        color: #6b7280;
        padding: 2rem 1rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .metrics-grid {
            grid-template-columns: 1fr;
        }
        
        .two-column-grid {
            grid-template-columns: 1fr;
        }
        
        .filter-form {
            width: 100%;
        }
        
        .filter-select {
            flex: 1;
        }
    }
</style>
@endsection