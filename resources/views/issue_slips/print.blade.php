<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Slip - {{ $issueSlip->issue_slip_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #333;
            background: #fff;
        }

        .page-container {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 6mm;
        }

        .slip-copy {
            flex: 1;
            border: 1.5px solid #999;
            padding: 4mm 5mm;
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
        }

        /* ========== HEADER ========== */
        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3mm;
            border-bottom: 1.5px solid #c00;
            padding-bottom: 2mm;
        }

        .slip-header-left {
            display: flex;
            align-items: center;
            gap: 3mm;
        }

        .slip-header-left img {
            width: 50px;
            height: auto;
        }

        .slip-header-left .company-info {
            line-height: 1.3;
        }

        .slip-header-left .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #c00;
        }

        .slip-header-left .company-sub {
            font-size: 7px;
            color: #666;
        }

        .slip-header-right {
            text-align: right;
        }

        .slip-title {
            font-size: 14px;
            font-weight: bold;
            color: #c00;
            letter-spacing: 1px;
        }

        .slip-number {
            font-size: 10px;
            margin-top: 1mm;
            font-weight: bold;
        }

        /* ========== META INFO ========== */
        .slip-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 9px;
        }

        .meta-row {
            display: flex;
            gap: 4mm;
        }

        .meta-label {
            font-weight: bold;
            color: #555;
        }

        .meta-value {
            border-bottom: 1px solid #999;
            min-width: 30mm;
            padding: 0 2mm;
        }

        /* ========== ITEMS TABLE ========== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
            flex: 1;
        }

        .items-table th {
            background: #c00;
            color: white;
            font-size: 8px;
            padding: 2px 3px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #999;
            text-transform: uppercase;
        }

        .items-table td {
            border: 1px solid #ccc;
            padding: 2px 3px;
            font-size: 8.5px;
            text-align: center;
            vertical-align: middle;
        }

        .items-table td.text-left {
            text-align: left;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        /* Empty rows for manual writing */
        .items-table .empty-row td {
            height: 5mm;
        }

        /* ========== SIGNATURES ========== */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 2mm;
            border-top: 1px solid #ddd;
        }

        .sig-block {
            text-align: center;
            width: 23%;
        }

        .sig-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 10mm;
        }

        .sig-line {
            border-top: 1px solid #333;
            margin-top: 1mm;
            padding-top: 1mm;
            font-size: 7px;
            color: #999;
        }

        /* ========== COPY LABEL ========== */
        .copy-label {
            font-size: 7px;
            color: #aaa;
            text-align: right;
            font-style: italic;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-container { height: auto; }
            .slip-copy { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <div class="no-print" style="position:fixed; top:10px; right:10px; z-index:999;">
        <button onclick="window.print()" style="background:#c00; color:white; padding:8px 20px; border:none; border-radius:4px; cursor:pointer; font-size:14px; font-weight:bold;">
            Print
        </button>
        <button onclick="window.close()" style="background:#666; color:white; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; font-size:14px; margin-left:5px;">
            Close
        </button>
    </div>

    <div class="page-container">
        @for($copy = 1; $copy <= 2; $copy++)
        <div class="slip-copy">
            <!-- Header -->
            <div class="slip-header">
                <div class="slip-header-left">
                    <img src="{{ asset('images/sopod-logo.png') }}" alt="Logo">
                    <div class="company-info">
                        <div class="company-name">MEATPLUS</div>
                        <div class="company-sub">Pacific Magalang Agriventures Inc.</div>
                    </div>
                </div>
                <div class="slip-header-right">
                    <div class="slip-title">ISSUE SLIP</div>
                    <div class="slip-number">{{ $issueSlip->issue_slip_number }}</div>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="slip-meta">
                <div class="meta-row">
                    <span class="meta-label">DATE:</span>
                    <span class="meta-value">{{ $issueSlip->date->format('d-M-y') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">ORIGIN:</span>
                    <span class="meta-value">{{ $issueSlip->origin ?? '' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">DESTINATION:</span>
                    <span class="meta-value">{{ $issueSlip->destination ?? $issueSlip->customer_name ?? '' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">SO#:</span>
                    <span class="meta-value">{{ $issueSlip->sales_order_number }}</span>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:4%">NO.</th>
                        <th style="width:10%">ITEM CODE</th>
                        <th style="width:20%">DESCRIPTION</th>
                        <th style="width:10%">BRAND</th>
                        <th style="width:10%">CATEGORY</th>
                        <th style="width:8%">SO QTY</th>
                        <th style="width:12%">NUMBER OF BOXES</th>
                        <th style="width:12%">NET WEIGHT</th>
                        <th style="width:12%">ACTUAL WEIGHT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($issueSlip->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->item_code }}</td>
                        <td class="text-left">{{ $item->item_description }}</td>
                        <td>{{ $item->brand }}</td>
                        <td>{{ $item->item_category }}</td>
                        <td>{{ $item->so_quantity ? number_format($item->so_quantity, 2) : '' }}</td>
                        <td>{{ $item->number_of_boxes ? number_format($item->number_of_boxes, 2) : '' }}</td>
                        <td>{{ $item->net_weight ? number_format($item->net_weight, 4) : '' }}</td>
                        <td>{{ $item->actual_weight ? number_format($item->actual_weight, 4) : '' }}</td>
                    </tr>
                    @endforeach
                    @for($i = $issueSlip->items->count(); $i < 8; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    @endfor
                </tbody>
            </table>

            <!-- Signatures -->
            <div class="signatures">
                <div class="sig-block">
                    <div class="sig-label">Issued By</div>
                    <div class="sig-line">Signature over printed name</div>
                </div>
                <div class="sig-block">
                    <div class="sig-label">Transport</div>
                    <div class="sig-line">Signature over printed name</div>
                </div>
                <div class="sig-block">
                    <div class="sig-label">Service Providers Checker</div>
                    <div class="sig-line">Signature over printed name</div>
                </div>
                <div class="sig-block">
                    <div class="sig-label">Received By</div>
                    <div class="sig-line">Signature over printed name</div>
                </div>
            </div>

            <div class="copy-label">{{ $copy === 1 ? 'Original Copy' : 'Duplicate Copy' }}</div>
        </div>
        @endfor
    </div>
</body>
</html>
