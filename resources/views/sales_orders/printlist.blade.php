<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Orders List - {{ $dateFrom ?? 'All' }} to {{ $dateTo ?? 'All' }}</title>
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

        .report-title { 
            color: #c00000; 
            font-size: 20px; 
            font-weight: bold; 
            margin: 20px 0 10px 0;
        }

        .date-range {
            font-size: 14px;
            color: #333;
            margin-bottom: 20px;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
        }

        th, td { 
            border: 1px solid #333; 
            padding: 8px; 
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

        .status-pending { 
            background: #ffc107; 
            color: #000; 
            padding: 2px 8px; 
            border-radius: 3px;
            font-weight: bold;
        }

        .status-approved { 
            background: #28a745; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 3px;
            font-weight: bold;
        }

        .status-declined { 
            background: #dc3545; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 3px;
            font-weight: bold;
        }

        .status-cancelled { 
            background: #6c757d; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 3px;
            font-weight: bold;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #c00000;
            padding-top: 10px;
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            color: #555;
            margin-top: 30px;
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

    <div class="report-title">SALES ORDERS LIST</div>
    
    <div class="date-range">
        <strong>Period:</strong> 
        @if($dateFrom && $dateTo)
            {{ date('F d, Y', strtotime($dateFrom)) }} to {{ date('F d, Y', strtotime($dateTo)) }}
        @elseif($dateFrom)
            From {{ date('F d, Y', strtotime($dateFrom)) }}
        @elseif($dateTo)
            Up to {{ date('F d, Y', strtotime($dateTo)) }}
        @else
            All Records
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">SO Number</th>
                <th style="width: 20%;">Customer</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 15%;">Total Amount</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 15%;">Prepared By</th>
                <th style="width: 15%;">Approved By</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalAmount = 0; 
            @endphp
            @foreach($salesOrders as $order)
                @php 
                    $totalAmount += $order->total_amount ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $order->sales_order_number }}</td>
                    <td>{{ $order->customer->customer_name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $order->created_at->format('m/d/Y') }}</td>
                    <td class="text-right">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="text-center">
                        @if($order->status === 'Pending')
                            <span class="status-pending">Pending</span>
                        @elseif($order->status === 'Approved')
                            <span class="status-approved">Approved</span>
                        @elseif($order->status === 'Declined')
                            <span class="status-declined">Declined</span>
                        @elseif($order->status === 'Cancelled')
                            <span class="status-cancelled">Cancelled</span>
                        @else
                            <span>{{ $order->status ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ optional($order->preparer)->name ?? '—' }}</td>
                    <td class="text-center">{{ optional($order->approver)->name ?? '—' }}</td>
                </tr>
            @endforeach
            
            @if($salesOrders->isEmpty())
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">
                        No sales orders found for the selected period.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($salesOrders->isNotEmpty())
    <div class="summary">
        <div class="summary-row">
            <span>Total Sales Orders:</span>
            <span>{{ $salesOrders->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Pending:</span>
            <span>{{ $salesOrders->where('status', 'Pending')->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Approved:</span>
            <span>{{ $salesOrders->where('status', 'Approved')->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Declined:</span>
            <span>{{ $salesOrders->where('status', 'Declined')->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Cancelled:</span>
            <span>{{ $salesOrders->where('status', 'Cancelled')->count() }}</span>
        </div>
        <div class="summary-row total">
            <span>GRAND TOTAL:</span>
            <span>₱{{ number_format($totalAmount, 2) }}</span>
        </div>
    </div>
    @endif

    <div class="footer">
        This is a system generated document. No signature is required.<br>
        Generated on: {{ now()->format('F d, Y h:i A') }}
    </div>

</body>
</html>