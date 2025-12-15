<div class="section-card">
    <div class="section-header">
        <h5>📦 Top 5 Items by Quantity ({{ $year }})</h5>
        @if(isset($topItems) && $topItems->isNotEmpty() && $topItems->sum('total_quantity') > 0)
            <span class="header-badge">{{ $topItems->count() }} items</span>
        @endif
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
                    $ranks = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                    $hasData = isset($topItems) && $topItems->isNotEmpty() && $topItems->sum('total_quantity') > 0;
                @endphp
                @if($hasData)
                    @foreach($topItems as $index => $item)
                    <tr>
                        <td class="text-center badge-cell">{{ $ranks[$index] ?? '📍' }}</td>
                        <td class="item-name">{{ $item->item_description ?? 'N/A' }}</td>
                        <td class="text-right">
                            <strong>{{ number_format($item->total_quantity ?? 0, 2) }} KG</strong>
                        </td>
                        <td class="text-right">
                            <span class="code-badge">{{ $item->item_code ?? 'N/A' }}</span>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="empty-state">
                            <div style="padding: 2rem; text-align: center;">
                                <i class="fas fa-box-open" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.5rem;"></i>
                                <p style="margin: 0; color: #6b7280;">No item data available for {{ $year }}</p>
                                <small style="color: #9ca3af;">Try selecting a different year</small>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>