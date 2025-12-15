<div class="section-card">
    <div class="section-header">
        <h5>🚚 Recent Deliveries</h5>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>DR No</th>
                    <th>Customer</th>
                    <th class="text-right">Total Items</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentDeliveries as $delivery)
                <tr>
                    <td>{{ $delivery->created_at->format('M d, Y') }}</td>
                    <td>{{ $delivery->dr_no ?? 'N/A' }}</td>
                    <td>{{ $delivery->customer_name ?? 'N/A' }}</td>
                    <td class="text-right">{{ $delivery->items->count() ?? 0 }} items</td>
                    <td class="text-center">
                        @php
                            $statusKey = strtolower($delivery->status ?? 'pending');
                            $statusClasses = [
                                'pending' => 'status-warning',
                                'processing' => 'status-info',
                                'delivered' => 'status-success',
                                'completed' => 'status-primary',
                                'cancelled' => 'status-danger',
                            ];
                            $statusClass = $statusClasses[$statusKey] ?? 'status-secondary';
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ ucfirst($delivery->status ?? 'Pending') }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">No recent deliveries</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>