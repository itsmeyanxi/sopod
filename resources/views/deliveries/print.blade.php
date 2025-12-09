<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Receipt - {{ $delivery->dr_no ?? $delivery->sales_order_number }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            color: #000; 
            margin: 40px; 
            line-height: 1.6;
        }

        .header { 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 2px solid #c00000;
            padding-bottom: 15px;
        }

        .logo { 
            width: 100px; 
            margin-bottom: 10px;
        }

        .company-name { 
            font-size: 22px; 
            font-weight: bold; 
            margin: 5px 0;
        }

        .company-info { 
            font-size: 12px; 
            color: #555;
        }

        .document-title { 
            color: #c00000; 
            font-size: 20px; 
            font-weight: bold; 
            margin: 20px 0 10px 0;
            text-align: center;
        }

        .info-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #333;
            display: inline-block;
            width: 180px;
        }

        .info-value {
            color: #666;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
        }

        th, td { 
            border: 1px solid #333; 
            padding: 10px; 
            font-size: 12px; 
        }

        th { 
            background: #c00000; 
            color: white; 
            text-align: center;
            font-weight: bold;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .item-note {
            font-size: 11px;
            color: #666;
            font-style: italic;
            margin-top: 3px;
            padding: 5px;
            background: #f9f9f9;
            border-left: 3px solid #c00000;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
        }

        .status-pending { background: #ffc107; color: #000; }
        .status-completed { background: #28a745; color: white; }
        .status-in-transit { background: #17a2b8; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .status-delivered { background: #007bff; color: white; }

        .total-section {
            margin-top: 20px;
            text-align: right;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .total-amount {
            font-size: 18px;
            font-weight: bold;
            color: #c00000;
        }

        .instructions-section {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
        }

        .instructions-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .instructions-content {
            color: #856404;
            font-size: 13px;
            line-height: 1.5;
        }

        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 12px;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            color: #555;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        @media print {
            body { margin: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <img src="{{ asset('images/meatplus-logo.png') }}" class="logo" alt="Logo">
        <div class="company-name">Meatplus Trading Corp</div>
        <div class="company-info">
            12F Victoria Building, United Nations Avenue, Ermita, Manila, Philippines, 1004<br>
            VAT Reg. TIN 006-873-989-000
        </div>
    </div>

    <div class="document-title">DELIVERY RECEIPT</div>

    <!-- Delivery Information -->
    <div class="info-section">
        <div class="info-grid">
            <div>
                <div class="info-row">
                    <span class="info-label">DR Number:</span>
                    <span class="info-value">{{ $delivery->dr_no ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">SO Number:</span>
                    <span class="info-value">{{ $delivery->sales_order_number ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">
                        {{ $delivery->customer_name 
                           ?? $delivery->salesOrder?->customer?->customer_name 
                           ?? $delivery->salesOrder?->client_name 
                           ?? 'N/A' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Code:</span>
                    <span class="info-value">{{ $delivery->customer_code ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">TIN:</span>
                    <span class="info-value">{{ $delivery->salesOrder->customer->tin ?? 'N/A' }}</span>
                 </div>
                <div class="info-row">
                    <span class="info-label">Branch:</span>
                    <span class="info-value">{{ $delivery->branch ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">PO Number:</span>
                    <span class="info-value">{{ $delivery->po_number ?? '—' }}</span>
                </div>
            </div>
            <div>
                <div class="info-row">
                   <span class="info-label">Delivery Date:</span>
                    <span class="info-value">
                        {{ $delivery->request_delivery_date 
                            ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('F d, Y') 
                            : ($delivery->salesOrder?->request_delivery_date 
                                ? \Carbon\Carbon::parse($delivery->salesOrder->request_delivery_date)->format('F d, Y') 
                                : '—') }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Representative:</span>
                    <span class="info-value">{{ $delivery->sales_representative ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Executive:</span>
                    <span class="info-value">{{ $delivery->sales_executive ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Plate No:</span>
                    <span class="info-value">{{ $delivery->plate_no ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Invoice No:</span>
                    <span class="info-value">{{ $delivery->sales_invoice_no ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        @php
                            $statusClass = 'status-pending';
                            if ($delivery->status === 'Completed') $statusClass = 'status-completed';
                            elseif ($delivery->status === 'In Transit') $statusClass = 'status-in-transit';
                            elseif ($delivery->status === 'Cancelled') $statusClass = 'status-cancelled';
                            elseif ($delivery->status === 'Delivered') $statusClass = 'status-delivered';
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $delivery->status ?? 'Pending' }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ ADDITIONAL DELIVERY INSTRUCTIONS -->
    @if($delivery->additional_instructions || $delivery->salesOrder?->additional_instructions)
    <div class="instructions-section">
        <div class="instructions-title">📋 Additional Delivery Instructions:</div>
        <div class="instructions-content">
            {{ $delivery->additional_instructions ?? $delivery->salesOrder?->additional_instructions }}
        </div>
    </div>
    @endif

<table>
    <thead>
        <tr>
            <th style="width: 10%;">Item Code</th>
            <th style="width: 12%;">Category</th>
            <th style="width: 12%;">Brand</th>
            <th style="width: 20%;">Description</th>
            <th style="width: 8%;">UOM</th>
            <th style="width: 8%;">SO Qty</th>
            <th style="width: 8%;">DR Qty</th>
            <th style="width: 7%;">Variance</th>
        </tr>
    </thead>
    <tbody>
        @php
            $hasItems = $delivery->items && $delivery->items->count() > 0;
            $totalAmount = 0;
            
            // Get SO items for comparison
            $soItems = $delivery->salesOrder?->items ?? collect();
            $soItemsMap = $soItems->keyBy('item_code');
        @endphp

        @if($hasItems)
            @foreach($delivery->items as $item)
                @php
                    $itemTotal = $item->total_amount ?? ($item->quantity * $item->unit_price);
                    $totalAmount += $itemTotal;
                    
                    // Get corresponding SO item for quantity comparison
                    $soItem = $soItemsMap->get($item->item_code);
                    $soQty = $soItem ? $soItem->quantity : 0;
                    $drQty = $item->quantity ?? 0;
                    $variance = $drQty - $soQty;
                    
                    // Variance styling
                    $varianceColor = $variance < 0 ? 'color: #dc3545; font-weight: bold;' : ($variance > 0 ? 'color: #28a745;' : 'color: #666;');
                @endphp
                <tr>
                    <td class="text-center">{{ $item->item_code ?? '—' }}</td>
                    <td>{{ $item->item_category ?? $item->item?->item_category ?? '—' }}</td>
                    <td>{{ $item->brand ?? $item->item?->brand ?? '—' }}</td>
                    <td>
                        {{ $item->item_description ?? $item->item?->item_description ?? '—'  }}
                        @if($item->notes)
                            <div class="item-note">
                                📝 Note: {{ $item->notes }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->uom ?? 'Kgs' }}</td>
                    <td class="text-right">{{ number_format($soQty, 2) }}</td>
                    <td class="text-right">{{ number_format($drQty, 2) }}</td>
                    <td class="text-right" style="{{ $varianceColor }}">
                        {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }}
                    </td>
                </tr>
            @endforeach
        @else
            {{-- Fallback: Show single item from deliveries table (legacy) --}}
            @php
                $unitPrice = ($delivery->unit_price) 
                    ?? (($delivery->quantity > 0) ? ($delivery->total_amount / $delivery->quantity) : 0);
                $totalAmount = $delivery->total_amount ?? 0;
                
                $soItem = $delivery->salesOrder?->items->where('item_code', $delivery->item_code)->first();
                $soQty = $soItem ? $soItem->quantity : 0;
                $drQty = $delivery->quantity ?? 0;
                $variance = $drQty - $soQty;
                $varianceColor = $variance < 0 ? 'color: #dc3545; font-weight: bold;' : ($variance > 0 ? 'color: #28a745;' : 'color: #666;');
            @endphp
            <tr>
                <td class="text-center">{{ $delivery->item_code ?? '—' }}</td>
                <td>{{ $delivery->item_category ?? '—' }}</td>
                <td>{{ $delivery->brand ?? '—' }}</td>
                <td>{{ $delivery->item_description ?? '—' }}</td>
                <td class="text-center">{{ $delivery->uom ?? 'Kgs' }}</td>
                <td class="text-right">{{ number_format($soQty, 2) }}</td>
                <td class="text-right">{{ number_format($drQty, 2) }}</td>
                <td class="text-right" style="{{ $varianceColor }}">
                    {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }}
                </td>
            </tr>
        @endif

        {{-- Empty state if no items at all --}}
        @if(!$hasItems && !$delivery->item_code)
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #999;">
                    No items found for this delivery.
                </td>
            </tr>
        @endif
    </tbody>
</table>

    <!-- Total -->
    <div class="total-section">
        <div style="font-size: 14px; margin-bottom: 5px;">Total Amount:</div>
        <div class="total-amount">₱{{ number_format($totalAmount, 2) }}</div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                {{ $delivery->approved_by ?? '—' }}<br>
                <small>Prepared By</small>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <br>
                <small>Delivered By</small>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <br>
                <small>Received By</small>
            </div>
        </div>
    </div>

    <div class="footer">
        This is a system generated document. No signature is required.<br>
        Generated on: {{ now()->format('F d, Y h:i A') }}
    </div>

</body>
</html>