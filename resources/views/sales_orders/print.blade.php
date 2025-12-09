<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Order - {{ $salesOrder->sales_order_number }}</title>
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

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 180px;
            color: #333;
        }

        .info-value {
            flex: 1;
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
        .status-approved { background: #28a745; color: white; }
        .status-declined { background: #dc3545; color: white; }
        .status-delivered { background: #17a2b8; color: white; }

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

    <div class="document-title">SALES ORDER</div>

    <!-- Sales Order Information -->
    <div class="info-section">
        <div style="display: flex; justify-content: space-between;">
            <div style="flex: 1;">
                <div class="info-row">
                    <span class="info-label">SO Number:</span>
                    <span class="info-value">{{ $salesOrder->sales_order_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">{{ $salesOrder->customer->customer_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Code:</span>
                    <span class="info-value">{{ $salesOrder->customer->customer_code ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">TIN:</span>
                    <span class="info-value">{{ $salesOrder->customer->tin ?? 'N/A' }}</span>
                 </div>
                <div class="info-row">
                    <span class="info-label">PO Number:</span>
                    <span class="info-value">{{ $salesOrder->po_number ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Branch:</span>
                    <span class="info-value">{{ $salesOrder->customer->branch ?? 'N/A' }}</span>
                </div>
            </div>
            <div style="flex: 1;">
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ $salesOrder->created_at->format('F d, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Request Delivery Date:</span>
                    <span class="info-value">{{ $salesOrder->request_delivery_date ? date('F d, Y', strtotime($salesOrder->request_delivery_date)) : '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Representative:</span>
                    <span class="info-value">{{ $salesOrder->sales_representative ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Executive:</span>
                    <span class="info-value">{{ $salesOrder->sales_executive ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        @php
                            $statusClass = 'status-pending';
                            if ($salesOrder->status === 'Approved') $statusClass = 'status-approved';
                            elseif ($salesOrder->status === 'Declined') $statusClass = 'status-declined';
                            elseif ($salesOrder->status === 'Delivered') $statusClass = 'status-delivered';
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ ucfirst($salesOrder->status) }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ ADDITIONAL DELIVERY INSTRUCTIONS -->
    @if($salesOrder->additional_instructions)
    <div class="instructions-section">
        <div class="instructions-title">📋 Additional Delivery Instructions:</div>
        <div class="instructions-content">
            {{ $salesOrder->additional_instructions }}
        </div>
    </div>
    @endif

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Item Code</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 15%;">Brand</th>
                <th style="width: 12%;">Category</th>
                <th style="width: 10%;">Quantity</th>
                <th style="width: 10%;">Unit Price</th>
                <th style="width: 15%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesOrder->items as $item)
                <tr>
                    <td class="text-center">{{ $item->item_code ?? '—' }}</td>
                    <td>
                        {{ $item->item_description ?: ($item->item->item_description ?? '') }}
                        @if($item->note)
                            <div class="item-note">
                                📝 Note: {{ $item->note }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $item->brand ?? '—' }}</td>
                    <td>{{ $item->item_category ?: ($item->item->item_category ?? '') }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }} {{ $item->unit ?? 'Kgs' }}</td>
                    <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">₱{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Total -->
    <div class="total-section">
        <div style="font-size: 14px; margin-bottom: 5px;">Total Amount:</div>
        <div class="total-amount">₱{{ number_format($salesOrder->total_amount, 2) }}</div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                {{ optional($salesOrder->preparer)->name ?? '—' }}<br>
                <small>Prepared By</small>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                {{ optional($salesOrder->approver)->name ?? '—' }}<br>
                <small>Approved By</small>
            </div>
        </div>
    </div>

    <div class="footer">
        This is a system generated document. No signature is required.<br>
        Generated on: {{ now()->format('F d, Y h:i A') }}
    </div>

</body>
</html>