<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->srr_code }} - Supply Receiving Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 10mm 12mm;
        }
        @media print {
            body { background: #fff; }
            .page { padding: 5mm 10mm; margin: 0; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 5mm; }
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3px 20px;
            margin-bottom: 8px;
            font-size: 11px;
        }
        .info-row {
            display: flex;
            align-items: baseline;
            padding: 2px 0;
        }
        .info-label {
            font-weight: bold;
            min-width: 80px;
            white-space: nowrap;
        }
        .info-value {
            border-bottom: 1px solid #000;
            flex: 1;
            padding-left: 5px;
            min-height: 16px;
        }

        /* Type checkboxes */
        .type-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
            padding: 3px 0;
        }
        .type-label {
            font-weight: bold;
            margin-right: 5px;
        }
        .type-option {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 10px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: center;
            vertical-align: middle;
        }
        .items-table th {
            background: #e5e5e5;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        .items-table td {
            min-height: 18px;
        }
        .items-table .text-left {
            text-align: left;
        }
        .items-table .text-right {
            text-align: right;
        }
        .totals-row td {
            font-weight: bold;
            background: #f0f0f0;
        }

        /* Note */
        .note-section {
            margin: 8px 0;
            font-size: 11px;
        }
        .note-label {
            font-weight: bold;
        }
        .note-value {
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 2px 5px;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }
        .sig-block {
            text-align: center;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            min-height: 35px;
            margin-bottom: 3px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 3px;
            font-size: 11px;
        }
        .sig-label {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }

        /* Print button */
        .print-controls {
            text-align: center;
            margin-bottom: 15px;
        }
        .print-btn {
            background: #6b21a8;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 5px;
            margin: 0 5px;
        }
        .print-btn:hover {
            background: #581c87;
        }
        .back-btn {
            background: #4b5563;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 5px;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        .back-btn:hover {
            background: #374151;
        }
    </style>
</head>
<body>
    <div class="no-print print-controls" style="padding: 15px;">
        <a href="{{ route('supplier_receiving_reports.show', $report->id) }}" class="back-btn">Back to Report</a>
        <button onclick="window.print()" class="print-btn">Print Report</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="company-name">PACIFIC MAGALANG AGRIVENTURES INC</div>
            <div class="report-title">RECEIVING REPORT</div>
        </div>

        <!-- Info Section -->
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">RR CODE:</span>
                <span class="info-value">{{ $report->srr_code }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">STORAGE:</span>
                <span class="info-value">{{ $report->storage ?? '' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">DATE:</span>
                <span class="info-value">{{ $report->report_date->format('M d, Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">PO NO:</span>
                <span class="info-value">{{ $report->po_no ?? '' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">SUPPLY:</span>
                <span class="info-value">{{ $report->supplier_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">CV NO:</span>
                <span class="info-value">{{ $report->cv_no ?? '' }}</span>
            </div>
        </div>

        <!-- Type -->
        <div class="type-row">
            <span class="type-label">TYPE:</span>
            <span class="type-option">
                <span class="checkbox">{{ $report->report_type === 'purchased' ? 'X' : '' }}</span>
                <span>PURCHASED</span>
            </span>
            <span class="type-option">
                <span class="checkbox">{{ $report->report_type === 'stock_transfer' ? 'X' : '' }}</span>
                <span>STOCK TRANSFER</span>
            </span>
            <span class="type-option">
                <span class="checkbox">{{ $report->report_type === 'backload' ? 'X' : '' }}</span>
                <span>BACKLOAD</span>
            </span>
            <span class="type-option">
                <span class="checkbox">{{ $report->report_type === 'returned' ? 'X' : '' }}</span>
                <span>RETURNED</span>
            </span>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30px">NO.</th>
                    <th style="width: 70px">ITEM CODE</th>
                    <th>ITEM DESCRIPTION</th>
                    <th style="width: 70px">BRAND</th>
                    <th style="width: 65px">NO. OF BOXES</th>
                    <th style="width: 60px">NET WEIGHT</th>
                    <th style="width: 55px">PRODUCTION DATE</th>
                    <th style="width: 70px">EXPIRATION DATE</th>
                    <th style="width: 60px">PALLET NO.</th>
                    <th style="width: 80px">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->items as $item)
                    <tr>
                        <td>{{ $item->item_no }}</td>
                        <td class="text-left">{{ $item->item_code ?? '' }}</td>
                        <td class="text-left">{{ $item->item_description }}</td>
                        <td class="text-left">{{ $item->brand ?? '' }}</td>
                        <td>{{ $item->no_of_boxes }}</td>
                        <td>{{ number_format($item->net_weight, 2) }}</td>
                        <td>{{ $item->pd ? $item->pd->format('m/d/Y') : '' }}</td>
                        <td>{{ $item->expiry_date ? $item->expiry_date->format('m/d/Y') : '' }}</td>
                        <td>{{ $item->pallet_no ?? '' }}</td>
                        <td class="text-left">{{ $item->remarks ?? '' }}</td>
                    </tr>
                @endforeach
                {{-- Add empty rows to fill minimum 15 rows --}}
                @for($i = $report->items->count(); $i < 15; $i++)
                    <tr>
                        <td>&nbsp;</td>
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
            <tfoot>
                <tr class="totals-row">
                    <td colspan="4" style="text-align: right; padding-right: 10px;">TOTAL:</td>
                    <td>{{ $report->items->sum('no_of_boxes') }}</td>
                    <td>{{ number_format($report->items->sum('net_weight'), 2) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Note -->
        <div class="note-section">
            <span class="note-label">NOTE:</span>
            <div class="note-value">{{ $report->note ?? '' }}</div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-line">{{ $report->prepared_by ?? '' }}</div>
                <div class="sig-label">PREPARED BY</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">{{ $report->checked_by ?? '' }}</div>
                <div class="sig-label">CHECKED BY</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">{{ $report->received_by ?? '' }}</div>
                <div class="sig-label">RECEIVED BY</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">{{ $report->verified_by ?? '' }}</div>
                <div class="sig-label">VERIFIED BY</div>
            </div>
        </div>
    </div>
</body>
</html>
