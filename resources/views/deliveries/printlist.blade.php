<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deliveries List - {{ $dateFrom ?? 'All' }} to {{ $dateTo ?? 'All' }}</title>
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

        .status-delivered { 
            background: #28a745; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 3px;
            font-weight: bold;
        }

        .status-in-transit { 
            background: #17a2b8; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 3px;
            font-weight: bold;
        }

        .status-cancelled { 
            background: #dc3545; 
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

    <div class="report-title">DELIVERIES LIST</div>
    
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
                <th style="width: 10%;">DR Number</th>
                <th style="width: 12%;">SO Number</th>
                <th style="width: 18%;">Customer</th>
                <th style="width: 10%;">Delivery Date</th>
                <th style="width: 10%;">Quantity</th>
                <th style="width: 12%;">Amount</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalAmount = 0; 
                $totalQuantity = 0;
            @endphp
            @foreach($deliveries as $delivery)
                @php 
                    $totalAmount += $delivery->total_amount ?? 0;
                    $totalQuantity += $delivery->quantity ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $delivery->dr_no ?? 'N/A' }}</td>
                    <td class="text-center">{{ $delivery->sales_order_number ?? 'N/A' }}</td>
                    <td>
                        {{-- ✅ Fixed: Use customer_name from deliveries table first --}}
                        {{ $delivery->customer_name 
                           ?? $delivery->salesOrder?->customer?->customer_name 
                           ?? $delivery->salesOrder?->client_name 
                           ?? 'N/A' }}
                    </td>
                    <td class="text-center">
                        {{-- ✅ Fixed: Check delivery table first, then sales order --}}
                        {{ $delivery->request_delivery_date 
                            ? \Carbon\Carbon::parse($delivery->request_delivery_date)->format('m/d/Y') 
                            : ($delivery->salesOrder?->request_delivery_date 
                                ? \Carbon\Carbon::parse($delivery->salesOrder->request_delivery_date)->format('m/d/Y') 
                                : '—') }}
                    </td>
                    <td class="text-right">{{ number_format($delivery->quantity ?? 0, 2) }}</td>
                    <td class="text-right">₱{{ number_format($delivery->total_amount ?? 0, 2) }}</td>
                    <td class="text-center">
                        {{-- ✅ Fixed: Corrected status condition and class name --}}
                        @if($delivery->status === 'Pending')
                            <span class="status-pending">Pending</span>
                        @elseif($delivery->status === 'Delivered')
                            <span class="status-delivered">Delivered</span>
                        @elseif($delivery->status === 'In Transit')
                            <span class="status-in-transit">In Transit</span>
                        @elseif($delivery->status === 'Cancelled')
                            <span class="status-cancelled">Cancelled</span>
                        @else
                            <span>{{ $delivery->status ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            
            @if($deliveries->isEmpty())
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">
                        No deliveries found for the selected period.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($deliveries->isNotEmpty())
    <div class="summary">
        <div class="summary-row">
            <span>Total Deliveries:</span>
            <span>{{ $deliveries->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Pending:</span>
            <span>{{ $deliveries->where('status', 'Pending')->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Delivered:</span>
            <span>{{ $deliveries->where('status', 'Delivered')->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Cancelled:</span>
            <span>{{ $deliveries->where('status', 'Cancelled')->count() }}</span>
        </div>
        <!-- <div class="summary-row">
            <span>Total Quantity:</span>
            <span>{{ number_format($totalQuantity, 2) }}</span>
        </div> -->
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