<div class="section-card">
    <div class="section-header">
        <h5>📅 Monthly Sales Breakdown ({{ $year }})</h5>
    </div>
    <div class="table-container">
        <table class="data-table monthly-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align:middle;">Month</th>
                    <th colspan="2" class="text-center" style="background:#1e3a5f;color:#93c5fd;border-bottom:2px solid #3b82f6;">Internal</th>
                    <th colspan="2" class="text-center" style="background:#14532d;color:#86efac;border-bottom:2px solid #22c55e;">External</th>
                    <th colspan="2" class="text-center" style="background:#1f2937;color:#9ca3af;border-bottom:2px solid #6b7280;">Total</th>
                    <th rowspan="2" class="text-right" style="vertical-align:middle;">Avg/Day (PHP)</th>
                    <th rowspan="2" class="text-center" style="vertical-align:middle;">Trend</th>
                </tr>
                <tr>
                    <th class="text-right" style="background:#172554;color:#bfdbfe;font-size:0.75rem;">PHP</th>
                    <th class="text-right" style="background:#172554;color:#bfdbfe;font-size:0.75rem;">KG</th>
                    <th class="text-right" style="background:#052e16;color:#bbf7d0;font-size:0.75rem;">PHP</th>
                    <th class="text-right" style="background:#052e16;color:#bbf7d0;font-size:0.75rem;">KG</th>
                    <th class="text-right" style="background:#111827;color:#d1d5db;font-size:0.75rem;">PHP</th>
                    <th class="text-right" style="background:#111827;color:#d1d5db;font-size:0.75rem;">KG</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $months = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
                    $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

                    $monthlyInternalPHP = $monthlyInternalPHP ?? array_fill(0, 12, 0);
                    $monthlyInternalKG  = $monthlyInternalKG  ?? array_fill(0, 12, 0);
                    $monthlyExternalPHP = $monthlyExternalPHP ?? array_fill(0, 12, 0);
                    $monthlyExternalKG  = $monthlyExternalKG  ?? array_fill(0, 12, 0);
                @endphp
                @foreach($months as $index => $monthName)
                    @php
                        $salesPHP    = $monthlyBreakdownPHP[$index] ?? 0;
                        $salesKG     = $monthlyBreakdownKG[$index]  ?? 0;
                        $internalPHP = $monthlyInternalPHP[$index]  ?? 0;
                        $internalKG  = $monthlyInternalKG[$index]   ?? 0;
                        $externalPHP = $monthlyExternalPHP[$index]  ?? 0;
                        $externalKG  = $monthlyExternalKG[$index]   ?? 0;

                        $prevSales  = $index > 0 ? ($monthlyBreakdownPHP[$index - 1] ?? 0) : 0;
                        $trend      = $prevSales > 0 ? (($salesPHP - $prevSales) / $prevSales) * 100 : 0;
                        $trendIcon  = $trend > 0 ? '📈' : ($trend < 0 ? '📉' : '➖');
                        $trendClass = $trend > 0 ? 'trend-up' : ($trend < 0 ? 'trend-down' : 'trend-neutral');
                        $avgPerDay  = $salesPHP > 0 ? $salesPHP / $daysInMonth[$index] : 0;
                    @endphp
                    <tr>
                        <td class="month-name">{{ $monthName }}</td>
                        <td class="text-right" style="background:#172554;color:#93c5fd;">₱{{ number_format($internalPHP, 2) }}</td>
                        <td class="text-right" style="background:#172554;color:#93c5fd;">{{ number_format($internalKG, 2) }} KG</td>
                        <td class="text-right" style="background:#052e16;color:#86efac;">₱{{ number_format($externalPHP, 2) }}</td>
                        <td class="text-right" style="background:#052e16;color:#86efac;">{{ number_format($externalKG, 2) }} KG</td>
                        <td class="text-right" style="font-weight:600;">₱{{ number_format($salesPHP, 2) }}</td>
                        <td class="text-right" style="font-weight:600;">{{ number_format($salesKG, 2) }} KG</td>
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
                    <td class="text-right" style="background:#1e3a5f;color:#93c5fd;">₱{{ number_format(array_sum($monthlyInternalPHP), 2) }}</td>
                    <td class="text-right" style="background:#1e3a5f;color:#93c5fd;">{{ number_format(array_sum($monthlyInternalKG), 2) }} KG</td>
                    <td class="text-right" style="background:#14532d;color:#86efac;">₱{{ number_format(array_sum($monthlyExternalPHP), 2) }}</td>
                    <td class="text-right" style="background:#14532d;color:#86efac;">{{ number_format(array_sum($monthlyExternalKG), 2) }} KG</td>
                    <td class="text-right">₱{{ number_format(array_sum($monthlyBreakdownPHP), 2) }}</td>
                    <td class="text-right">{{ number_format(array_sum($monthlyBreakdownKG), 2) }} KG</td>
                    <td class="text-right">-</td>
                    <td class="text-center">-</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
