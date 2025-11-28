<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sales Orders Batch Print - {{ now()->format('F d, Y') }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            color: #000; 
            margin: 40px; 
            line-height: 1.6;
        }

        .page-break {
            page-break-after: always;
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
        .status-cancelled { background: #6c757d; color: white; }
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
<body>

    @foreach($orders as $order)
    <div class="{{ $loop->last ? '' : 'page-break' }}">
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
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 50%; vertical-align: top; border: none; padding: 0 15px 0 0;">
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
                            <span class="info-value">{{ $order->customer->tin ?? $order->tin ?? 'N/A' }}</span>
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
                    <td style="width: 50%; vertical-align: top; border: none; padding: 0 0 0 15px;">
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
            <div class="total-amount">₱{{ number_format($order->total_amount, 2) }}</div>
        </div>

        <!-- Signatures -->
        <table style="width: 100%; margin-top: 60px; border: none;">
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