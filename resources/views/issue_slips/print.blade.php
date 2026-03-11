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
            height: auto;
            display: flex;
            flex-direction: column;
            gap: 12mm;
        }

        .slip-copy {
            border: 1.5px solid #999;
            padding: 3mm 4mm;
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
            margin-top: 12mm;
        }

        /* ========== HEADER ========== */
        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2mm;
            border-bottom: 1.5px solid #c00;
            padding-bottom: 1.5mm;
        }

        .slip-header-left {
            display: flex;
            align-items: center;
            gap: 2mm;
        }

        .slip-header-left img {
            width: 40px;
            height: auto;
        }

        .slip-header-left .company-info {
            line-height: 1.2;
        }

        .slip-header-left .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #c00;
        }

        .slip-header-left .company-sub {
            font-size: 8px;
            color: #666;
        }

        .slip-header-right {
            text-align: right;
        }

        .slip-title {
            font-size: 14px;
            font-weight: bold;
            color: #c00;
            letter-spacing: 0.5px;
        }

        .slip-number {
            font-size: 10px;
            margin-top: 0.5mm;
            font-weight: bold;
        }

        /* ========== META INFO ========== */
        .slip-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5mm;
            font-size: 9px;
        }

        .meta-row {
            display: flex;
            gap: 3mm;
        }

        .meta-label {
            font-weight: bold;
            color: #555;
        }

        .meta-value {
            border-bottom: 1px solid #999;
            min-width: 25mm;
            padding: 0.5mm 1.5mm;
        }

        /* ========== ITEMS TABLE ========== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5mm;
            flex: 1;
        }

        .items-table th {
            background: #c00;
            color: white;
            font-size: 8.5px;
            padding: 3px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #999;
            text-transform: uppercase;
        }

        .items-table td {
            border: 1px solid #ccc;
            padding: 3px 4px;
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
            height: 3.5mm;
        }

        /* ========== SIGNATURES ========== */
        .signatures {
            display: flex;
            justify-content: space-between;
            gap: 4mm;
            margin-top: auto;
            padding-top: 2mm;
            border-top: 1px solid #ddd;
        }

        .sig-block {
            text-align: center;
            flex: 1;
        }

        .sig-label {
            font-size: 8px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 7mm;
        }

        .sig-line {
            border-top: 1px solid #333;
            margin-top: 0.5mm;
            padding-top: 0.5mm;
            font-size: 8px;
            color: #999;
        }

        /* ========== COPY LABEL ========== */
        .copy-label {
            font-size: 9px;
            color: #aaa;
            text-align: right;
            font-style: italic;
        }

        /* ========== REMARKS ========== */
        .remarks-section {
            margin: 1.5mm 0;
            padding: 1.5mm 2mm;
            background: #fafafa;
            border: 1px solid #ddd;
        }

        .remarks-label {
            font-size: 8.5px;
            font-weight: bold;
            color: #555;
            margin-bottom: 0.5mm;
        }

        .remarks-content {
            font-size: 8.5px;
            color: #333;
            line-height: 1.3;
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
                    <img src="{{ asset('images/sopod-logo.PNG') }}" alt="Logo">
                    <div class="company-info">
                        <div class="company-name">MEATPLUS</div>
                        <div class="company-sub">Meatplus Trading Corp.</div>
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
                    <span class="meta-label">DESTINATION:</span>
                    <span class="meta-value">{{ $issueSlip->destination ?? $issueSlip->customer_name ?? '' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">BRANCH:</span>
                    <span class="meta-value">{{ $issueSlip->branch ?? optional($issueSlip->salesOrder)->branch ?? optional(optional($issueSlip->salesOrder)->customer)->branch ?? '' }}</span>
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
                        <th style="width:12%">ORIGIN</th>
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
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>{{ $item->origin ?? '-' }}</td>
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

            <!-- Remarks Section -->
            <div class="remarks-section">
                @if($issueSlip->salesOrder && $issueSlip->salesOrder->additional_instructions)
                <div class="remarks-label">DELIVERY INSTRUCTIONS:</div>
                <div class="remarks-content">{{ $issueSlip->salesOrder->additional_instructions }}</div>
                @endif
                @if($issueSlip->remarks)
                <div class="remarks-label" @if($issueSlip->salesOrder && $issueSlip->salesOrder->additional_instructions) style="margin-top: 8px;" @endif>ISSUE SLIP REMARKS:</div>
                <div class="remarks-content">{{ $issueSlip->remarks }}</div>
                @endif
            </div>

            <!-- Signatures -->
            <div class="signatures">
                <div class="sig-block">
                    <div class="sig-label">Issued By</div>
                    <div class="sig-line">{{ $issueSlip->issued_by ?? 'Signature over printed name' }}</div>
                </div>
                <div class="sig-block">
                    <div class="sig-label">Transport</div>
                    <div class="sig-line">{{ $issueSlip->transport ?? 'Signature over printed name' }}</div>
                </div>
                <div class="sig-block">
                    <div class="sig-label">Service Providers Checker</div>
                    <div class="sig-line">{{ $issueSlip->service_providers_checker ?? 'Signature over printed name' }}</div>
                </div>
                <div class="sig-block">
                    <div class="sig-label">Received By</div>
                    <div class="sig-line">{{ $issueSlip->received_by ?? 'Signature over printed name' }}</div>
                </div>
            </div>

            <div class="copy-label">{{ $copy === 1 ? 'Original Copy' : 'Duplicate Copy' }}</div>
        </div>
        @endfor
    </div>
</body>
</html>
