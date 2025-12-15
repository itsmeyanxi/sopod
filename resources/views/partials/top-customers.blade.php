<div class="section-card">
    <div class="section-header">
        <h5>🏆 Top 5 Customers ({{ $year }})</h5>
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
                    $totalRevenue = $topCustomers->sum('total_sales');
                    $badges = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                @endphp
                @forelse($topCustomers as $index => $customer)
                <tr>
                    <td class="text-center badge-cell">{{ $badges[$index] ?? '📍' }}</td>
                    <td class="customer-name">{{ $customer->customer_name ?? 'N/A' }}</td>
                    <td class="text-right">₱{{ number_format($customer->total_sales ?? 0, 2) }}</td>
                    <td class="text-right">
                        <span class="percent-badge">
                            {{ $totalRevenue > 0 ? number_format((($customer->total_sales ?? 0) / $totalRevenue) * 100, 1) : 0 }}%
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