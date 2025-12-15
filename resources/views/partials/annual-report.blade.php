<!-- Year Selector -->
<form method="GET" action="{{ route('sales.dashboard') }}" class="filter-form annual-filter">
    <div class="filter-group">
        <label class="filter-label">Select Year</label>
        <select name="annual_year" class="filter-select">
            @for($i = 2020; $i <= date('Y') + 1; $i++)
                <option value="{{ $i }}" {{ $selectedAnnualYear == $i ? 'selected' : '' }}>{{ $i }}</option>
            @endfor
        </select>
    </div>
    <button type="submit" class="filter-button">Apply</button>
</form>

<!-- Year Total Summary -->
<div class="annual-total-card">
    <h2 class="annual-title">Total Sales for {{ $selectedAnnualYear }}</h2>
    <p class="annual-value">₱{{ number_format($annualTotalPHP, 2) }}</p>
    <p class="annual-subtitle">{{ number_format($annualTotalKG, 2) }} KG</p>
</div>

<!-- Annual Monthly Breakdown -->
<div class="section-card">
    <div class="section-header">
        <h5>📅 Annual Sales Breakdown by Month</h5>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Total Sales (PHP)</th>
                    <th class="text-right">Total Sales (KG)</th>
                    <th class="text-right">% of Year</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                              'July', 'August', 'September', 'October', 'November', 'December'];
                    $yearTotalPHP = array_sum(array_column($annualData, 'php'));
                @endphp
                @foreach($annualData as $index => $data)
                <tr>
                    <td class="month-name">{{ $months[$index] }}</td>
                    <td class="text-right">{{ number_format($data['orders']) }}</td>
                    <td class="text-right">₱{{ number_format($data['php'], 2) }}</td>
                    <td class="text-right">{{ number_format($data['kg'], 2) }} KG</td>
                    <td class="text-right">
                        @if($yearTotalPHP > 0)
                            <span class="percent-badge">
                                {{ number_format(($data['php'] / $yearTotalPHP) * 100, 1) }}%
                            </span>
                        @else
                            <span class="trend-neutral">0%</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="month-name">TOTAL</td>
                    <td class="text-right">{{ number_format(array_sum(array_column($annualData, 'orders'))) }}</td>
                    <td class="text-right">₱{{ number_format($yearTotalPHP, 2) }}</td>
                    <td class="text-right">{{ number_format(array_sum(array_column($annualData, 'kg')), 2) }} KG</td>
                    <td class="text-right">100%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Visual Chart -->
<div class="section-card">
    <div class="section-header">
        <h5>📊 Visual Overview</h5>
    </div>
    <div style="padding: 1.5rem;">
        <div class="visual-chart">
            @foreach($annualData as $index => $data)
                <div class="chart-row">
                    <div class="chart-label">{{ $months[$index] }}</div>
                    <div class="chart-bar-container">
                        <div class="chart-bar" style="width: {{ $yearTotalPHP > 0 ? ($data['php'] / $yearTotalPHP) * 100 : 0 }}%">
                            <span class="chart-value">₱{{ number_format($data['php'], 2) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
