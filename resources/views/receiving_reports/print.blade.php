<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receiving Report - {{ $receivingReport->rr_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: white;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 8.5in;
            height: 11in;
            margin: 0 auto;
            padding: 0.5in;
            background: white;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .header-left h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .header-left p {
            font-size: 11px;
            color: #666;
        }
        .header-right {
            text-align: right;
        }
        .header-right p {
            font-size: 11px;
            margin-bottom: 3px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border: 1px solid #333;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid #999;
            padding-bottom: 3px;
            margin-bottom: 8px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            font-size: 10px;
            margin-bottom: 10px;
        }
        .info-item label {
            color: #666;
            font-weight: bold;
            font-size: 9px;
        }
        .info-item p {
            margin: 2px 0;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 10px;
        }
        table thead {
            background: #f0f0f0;
            border: 1px solid #333;
        }
        table thead th {
            border: 1px solid #999;
            padding: 5px;
            text-align: left;
            font-weight: bold;
        }
        table tbody td {
            border: 1px solid #ddd;
            padding: 4px;
        }
        table tfoot {
            background: #f0f0f0;
            font-weight: bold;
        }
        table tfoot td {
            border: 1px solid #999;
            padding: 5px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 10px;
        }
        .signature-area {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 20px;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 100%;
                height: auto;
                padding: 0.5in;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>RECEIVING REPORT</h1>
                <p><strong>RR Number:</strong> {{ $receivingReport->rr_number }}</p>
            </div>
            <div class="header-right">
                <p><strong>Date:</strong> {{ now()->format('M d, Y') }}</p>
                <p><strong>Time:</strong> {{ now()->format('h:i A') }}</p>
                <div class="status-badge">{{ $receivingReport->status }}</div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="section">
            <div class="section-title">CUSTOMER INFORMATION</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Customer Name</label>
                    <p>{{ $receivingReport->customer_name ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Customer Code</label>
                    <p>{{ $receivingReport->customer_code ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>TIN</label>
                    <p>{{ $receivingReport->tin_no ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Branch</label>
                    <p>{{ $receivingReport->branch ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Sales Representative</label>
                    <p>{{ $receivingReport->sales_representative ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Sales Executive</label>
                    <p>{{ $receivingReport->sales_executive ?? '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Order Information -->
        <div class="section">
            <div class="section-title">ORDER INFORMATION</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Sales Order No</label>
                    <p>{{ $receivingReport->sales_order_number ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>PO Number</label>
                    <p>{{ $receivingReport->po_number ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Sales Invoice No</label>
                    <p>{{ $receivingReport->sales_invoice_no ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Delivery Batch</label>
                    <p>{{ $receivingReport->delivery_batch ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Plate No</label>
                    <p>{{ $receivingReport->plate_no ?? '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Delivery Type</label>
                    <p>{{ $receivingReport->delivery_type ?? '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Receiving Details -->
        <div class="section">
            <div class="section-title">RECEIVING DETAILS</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Received Date</label>
                    <p>{{ $receivingReport->received_date ? $receivingReport->received_date->format('M d, Y h:i A') : '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Request Delivery Date</label>
                    <p>{{ $receivingReport->request_delivery_date ? \Carbon\Carbon::parse($receivingReport->request_delivery_date)->format('M d, Y') : '—' }}</p>
                </div>
                <div class="info-item">
                    <label>Received By</label>
                    <p>{{ $receivingReport->received_by ?? '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="section">
            <div class="section-title">RECEIVED ITEMS</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 12%">Item Code</th>
                        <th style="width: 20%">Description</th>
                        <th style="width: 10%">Brand</th>
                        <th style="width: 10%">Category</th>
                        <th style="width: 8%; text-align: right;">Orig Qty</th>
                        <th style="width: 8%; text-align: right;">Rec Qty</th>
                        <th style="width: 6%">UOM</th>
                        <th style="width: 11%; text-align: right;">Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalAmount = 0; @endphp
                    @forelse($receivingReport->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->item_code }}</td>
                        <td>{{ $item->item_description ?? '—' }}</td>
                        <td>{{ $item->brand ?? '—' }}</td>
                        <td>{{ $item->item_category ?? '—' }}</td>
                        <td class="text-right">{{ number_format($item->original_quantity ?? 0, 2) }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-center">{{ $item->uom ?? 'Kgs' }}</td>
                        <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                    </tr>
                    @php $totalAmount += $item->total_amount; @endphp
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">No items found</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="8" class="text-right">GRAND TOTAL:</td>
                        <td class="text-right">₱{{ number_format($totalAmount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Additional Information -->
        @if($receivingReport->additional_instructions)
        <div class="section">
            <div class="section-title">ADDITIONAL INSTRUCTIONS</div>
            <p style="font-size: 10px; background: #f9f9f9; padding: 5px; border-left: 2px solid #333;">
                {{ $receivingReport->additional_instructions }}
            </p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Created: {{ $receivingReport->created_at->format('M d, Y h:i A') }}
            @if($receivingReport->updated_at != $receivingReport->created_at)
                | Last Updated: {{ $receivingReport->updated_at->format('M d, Y h:i A') }}
            @endif
            </p>
            <p>Created By: {{ $receivingReport->created_by ?? 'System' }}</p>
        </div>

        <!-- Signature Area -->
        <div class="signature-area">
            <div class="signature-line">
                <p>Received By</p>
            </div>
            <div class="signature-line">
                <p>Verified By</p>
            </div>
            <div class="signature-line">
                <p>Approved By</p>
            </div>
        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>
