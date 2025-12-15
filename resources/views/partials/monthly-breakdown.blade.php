<div class="section-card">
    <div class="section-header">
        <h5>📅 Monthly Sales Breakdown ({{ $year }})</h5>
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
                    
                    // Use annualData if year matches selectedAnnualYear, otherwise use monthlyDataPHP/KG
                    $useAnnualData = ($year == $selectedAnnualYear);
                @endphp
                @foreach($months as $index => $monthName)
                    @php
                        if ($useAnnualData) {
                            // Use data from MonthlySale model (same as Annual Sales section)
                            $salesPHP = $annualData[$index]['php'] ?? 0;
                            $salesKG = $annualData[$index]['kg'] ?? 0;
                        } else {
                            // Use data from Deliveries (current behavior)
                            $salesPHP = $monthlyDataPHP[$index] ?? 0;
                            $salesKG = $monthlyDataKG[$index] ?? 0;
                        }
                        
                        $prevSales = $index > 0 ? ($useAnnualData ? ($annualData[$index - 1]['php'] ?? 0) : ($monthlyDataPHP[$index - 1] ?? 0)) : 0;
                        $trend = $prevSales > 0 ? (($salesPHP - $prevSales) / $prevSales) * 100 : 0;
                        $trendIcon = $trend > 0 ? '📈' : ($trend < 0 ? '📉' : '➖');
                        $trendClass = $trend > 0 ? 'trend-up' : ($trend < 0 ? 'trend-down' : 'trend-neutral');
                        $avgPerDay = $salesPHP > 0 ? $salesPHP / $daysInMonth[$index] : 0;
                    @endphp
                    <tr>
                        <td class="month-name">{{ $monthName }}</td>
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
                    <td class="text-right">
                        ₱{{ number_format($useAnnualData ? array_sum(array_column($annualData, 'php')) : array_sum($monthlyDataPHP), 2) }}
                    </td>
                    <td class="text-right">
                        {{ number_format($useAnnualData ? array_sum(array_column($annualData, 'kg')) : array_sum($monthlyDataKG), 2) }} KG
                    </td>
                    <td class="text-right">-</td>
                    <td class="text-center">-</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>