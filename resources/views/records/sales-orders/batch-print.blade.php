<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sales Orders Batch Print - {{ now()->format('F d, Y') }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            color: #000; 
            margin: 15px 25px;
            line-height: 1.3;
            font-size: 11px;
        }

        .page-break {
            page-break-after: always;
        }

        .header { 
            text-align: center; 
            margin-bottom: 15px;
            border-bottom: 2px solid #c00000;
            padding-bottom: 8px;
        }

        .logo { 
            width: 70px; 
            margin-bottom: 5px;
        }

        .company-name { 
            font-size: 18px; 
            font-weight: bold; 
            margin: 3px 0;
        }

        .company-info { 
            font-size: 9px; 
            color: #555;
            line-height: 1.2;
        }

        .document-title { 
            color: #c00000; 
            font-size: 16px; 
            font-weight: bold; 
            margin: 10px 0 8px 0;
            text-align: center;
        }

        .info-section {
            margin: 10px 0;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 3px;
        }

        .info-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 10px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #333;
        }

        .info-value {
            flex: 1;
            color: #666;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0;
        }

        th, td { 
            border: 1px solid #333; 
            padding: 5px 6px;
            font-size: 10px; 
        }

        th { 
            background: #c00000; 
            color: white; 
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 9px;
        }

        .status-pending { background: #ffc107; color: #000; }
        .status-approved { background: #28a745; color: white; }
        .status-declined { background: #dc3545; color: white; }
        .status-cancelled { background: #6c757d; color: white; }
        .status-delivered { background: #17a2b8; color: white; }

        .total-section {
            margin-top: 8px;
            text-align: right;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 3px;
        }

        .total-amount {
            font-size: 14px;
            font-weight: bold;
            color: #c00000;
        }

        .signatures {
            margin-top: 25px;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 3px;
            font-size: 10px;
        }

        .footer {
            text-align: center;
            font-size: 8px;
            color: #555;
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }

        @media print {
            body { margin: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    @php
        // Convert logo to base64
        $logoPath = public_path('images/meatplus-logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;
        }
    @endphp

    @foreach($orders as $order)
    <div class="{{ $loop->last ? '' : 'page-break' }}">
        <div class="header">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" class="logo" alt="Meatplus Logo">
            @endif
            <div class="company-name">Meatplus Trading Corp</div>
            <div class="company-info">
                12F Victoria Building, United Nations Avenue, Ermita, Manila, Philippines, 1004<br>
                VAT Reg. TIN 006-873-989-000
            </div>
        </div>

        <div class="document-title">SALES ORDER</div>

        <!-- Sales Order Information -->
        <div class="info-section">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 50%; vertical-align: top; border: none; padding: 0 10px 0 0;">
                        <div class="info-row">
                            <span class="info-label">SO Number:</span>
                            <span class="info-value">{{ $order->sales_order_number }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Customer:</span>
                            <span class="info-value">{{ $order->customer->customer_name ?? $order->customer_name ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Customer Code:</span>
                            <span class="info-value">{{ $order->customer->customer_code ?? $order->customer_code ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">TIN:</span>
                            <span class="info-value">{{ $order->customer->tin_no ?? $order->tin_no ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PO Number:</span>
                            <span class="info-value">{{ $order->po_number ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Branch:</span>
                            <span class="info-value">{{ $order->customer->branch ?? $order->branch ?? 'N/A' }}</span>
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; border: none; padding: 0 0 0 10px;">
                        <div class="info-row">
                            <span class="info-label">Date:</span>
                            <span class="info-value">{{ $order->created_at->format('F d, Y') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Request Delivery Date:</span>
                            <span class="info-value">{{ $order->request_delivery_date ? date('F d, Y', strtotime($order->request_delivery_date)) : '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sales Representative:</span>
                            <span class="info-value">{{ $order->sales_representative ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sales Executive:</span>
                            <span class="info-value">{{ $order->sales_executive ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value">
                                @php
                                    $statusClass = 'status-pending';
                                    if ($order->status === 'Approved') $statusClass = 'status-approved';
                                    elseif ($order->status === 'Declined') $statusClass = 'status-declined';
                                    elseif ($order->status === 'Cancelled') $statusClass = 'status-cancelled';
                                    elseif ($order->status === 'Delivered') $statusClass = 'status-delivered';
                                @endphp
                                <span class="status-badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

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
                @forelse($order->items as $item)
                    <tr>
                        <td class="text-center">{{ $item->item_code ?? '—' }}</td>
                        <td>{{ $item->item_description ?: ($item->item->item_description ?? '') }}</td>
                        <td>{{ $item->brand ?? '—' }}</td>
                        <td>{{ $item->item_category ?: ($item->item->item_category ?? '') }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }} {{ $item->unit ?? 'Kgs' }}</td>
                        <td class="text-right">PHP {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">PHP {{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 15px;">No items found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Total -->
        <div class="total-section">
            <div style="font-size: 11px; margin-bottom: 3px;">Total Amount:</div>
            <div class="total-amount">PHP {{ number_format($order->total_amount, 2) }}</div>
        </div>

        <!-- Signatures -->
        <table style="width: 100%; margin-top: 25px; border: none;">
            <tr>
                <td style="width: 50%; text-align: center; border: none;">
                    <div class="signature-box">
                        <div class="signature-line">
                            {{ optional($order->preparer)->name ?? '—' }}<br>
                            <small>Prepared By</small>
                        </div>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; border: none;">
                    <div class="signature-box">
                        <div class="signature-line">
                            {{ optional($order->approver)->name ?? '—' }}<br>
                            <small>Approved By</small>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            This is a system generated document. No signature is required.<br>
            Generated on: {{ now()->format('F d, Y h:i A') }}
        </div>
    </div>
    @endforeach

</body>
</html>