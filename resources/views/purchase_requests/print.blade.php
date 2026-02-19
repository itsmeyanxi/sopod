<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PR {{ $purchaseRequest->pr_no }} - Purchase Requisition</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 10mm 12mm; }
        @media print {
            body { background: #fff; }
            .page { padding: 5mm 10mm; margin: 0; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 5mm; }
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-logo { width: 70px; height: 70px; object-fit: contain; margin-right: 12px; }
        .header-company { flex: 1; }
        .company-name { font-size: 16px; font-weight: bold; }
        .company-address { font-size: 9px; color: #444; margin-top: 2px; }
        .header-right { text-align: right; }
        .doc-title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .doc-no { font-size: 12px; font-weight: bold; margin-top: 4px; }

        /* Info Grid */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 11px; }
        .info-table td { padding: 2px 4px; vertical-align: top; }
        .info-label { font-weight: bold; white-space: nowrap; width: 110px; }
        .info-value { border-bottom: 1px solid #000; padding-left: 5px; min-height: 16px; }
        .info-section-left { width: 50%; }
        .info-section-right { width: 50%; padding-left: 15px; }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
        }
        .items-table th {
            background: #e5e5e5;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center;
        }
        .items-table td { text-align: center; }
        .items-table td.text-left { text-align: left; }
        .items-table td.text-right { text-align: right; }
        .totals-row td { font-weight: bold; background: #f0f0f0; }

        /* Section labels */
        .section-label { font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .section-value {
            border: 1px solid #000;
            min-height: 40px;
            padding: 4px 6px;
            font-size: 11px;
            margin-bottom: 8px;
        }

        /* Signatures */
        .signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 25px; }
        .sig-block { text-align: center; }
        .sig-line {
            border-bottom: 1px solid #000;
            min-height: 35px;
            margin-bottom: 3px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .sig-label { font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .e-signature { font-size: 8px; color: #333; margin-top: 4px; font-style: normal; font-weight: 500; }
        .e-signature-detail { font-size: 7px; color: #666; margin-top: 2px; font-style: italic; }

        /* Print controls */
        .print-controls { text-align: center; margin-bottom: 15px; }
        .print-btn {
            background: #6b21a8; color: white; border: none;
            padding: 10px 30px; font-size: 14px; cursor: pointer;
            border-radius: 5px; margin: 0 5px;
        }
        .print-btn:hover { background: #581c87; }
        .back-btn {
            background: #4b5563; color: white; border: none;
            padding: 10px 30px; font-size: 14px; cursor: pointer;
            border-radius: 5px; margin: 0 5px; text-decoration: none; display: inline-block;
        }
        .back-btn:hover { background: #374151; }
    </style>
</head>
<body>
    <div class="no-print print-controls" style="padding: 15px;">
        <a href="{{ route('purchase_requests.show', $purchaseRequest->id) }}" class="back-btn">Back to PR</a>
        <button onclick="window.print()" class="print-btn">Print</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('images/sopod-logo.png') }}" class="header-logo" alt="Logo">
            <div class="header-company">
                <div class="company-name">{{ $purchaseRequest->company }}</div>
            </div>
            <div class="header-right">
                <div class="doc-title">Purchase Requisition</div>
                <div class="doc-no">No.: {{ $purchaseRequest->pr_no }}</div>
            </div>
        </div>

        <!-- Info Fields -->
        <table class="info-table">
            <tr>
                <td class="info-section-left">
                    <table width="100%">
                        <tr>
                            <td class="info-label">Requisitioner:</td>
                            <td class="info-value">{{ $purchaseRequest->requisitioner }}</td>
                        </tr>
                        <tr><td colspan="2" style="height:4px"></td></tr>
                        <tr>
                            <td class="info-label">Department:</td>
                            <td class="info-value">{{ $purchaseRequest->department ?? '' }}</td>
                        </tr>
                        <tr><td colspan="2" style="height:4px"></td></tr>
                        <tr>
                            <td class="info-label">Delivery Address:</td>
                            <td class="info-value" style="min-height:30px">{{ $purchaseRequest->delivery_address ?? '' }}</td>
                        </tr>
                    </table>
                </td>
                <td class="info-section-right">
                    <table width="100%">
                        <tr>
                            <td class="info-label">Date of Request:</td>
                            <td class="info-value">{{ $purchaseRequest->date_of_request->format('m/d/Y') }}</td>
                        </tr>
                        <tr><td colspan="2" style="height:4px"></td></tr>
                        <tr>
                            <td class="info-label">Date Needed:</td>
                            <td class="info-value">{{ $purchaseRequest->date_needed ? $purchaseRequest->date_needed->format('m/d/Y') : '' }}</td>
                        </tr>
                        <tr><td colspan="2" style="height:4px"></td></tr>
                        <tr>
                            <td class="info-label">Supplier:</td>
                            <td class="info-value">{{ $purchaseRequest->supplier->supplier_name ?? ($purchaseRequest->supplier ?? '') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Particulars Label -->
        <div class="section-label">Particulars</div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:70px">Item Code</th>
                    <th>Description</th>
                    <th style="width:80px">Quantity</th>
                    <th style="width:60px">UOM</th>
                    <th style="width:80px">Unit Price</th>
                    <th style="width:90px">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseRequest->items as $item)
                    <tr>
                        <td class="text-left">{{ $item->item_code ?? '' }}</td>
                        <td class="text-left">{{ $item->description }}</td>
                        <td>{{ number_format($item->qty, 2) }}</td>
                        <td>{{ $item->uom }}</td>
                        <td class="text-right">{{ $item->unit_price ? number_format($item->unit_price, 2) : '' }}</td>
                        <td class="text-right">{{ $item->amount ? number_format($item->amount, 2) : '' }}</td>
                    </tr>
                @endforeach
                @for($i = $purchaseRequest->items->count(); $i < 8; $i++)
                    <tr>
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="5" style="text-align:right; padding-right:10px;">TOTAL:</td>
                    <td class="text-right">{{ number_format($purchaseRequest->items->sum('amount'), 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Purpose -->
        <div class="section-label">Purpose:</div>
        <div class="section-value">{{ $purchaseRequest->reason_for_requisition ?? '' }}</div>

        <!-- Other Remarks -->
        <div class="section-label">Other Remarks:</div>
        <div class="section-value">{{ $purchaseRequest->remarks ?? '' }}</div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-line">{{ $purchaseRequest->requisitioner }}</div>
                <div class="sig-label">Requested By</div>
                @if($purchaseRequest->requisitioner)
                    <div class="e-signature">Digitally Approved</div>
                    <div class="e-signature-detail">Date/Time: {{ now()->format('d F Y | H:i') }} PHT (UTC+8)</div>
                @endif
            </div>
            <div class="sig-block">
                <div class="sig-line">{{ $purchaseRequest->creator->name ?? '' }}</div>
                <div class="sig-label">Reviewed and Endorsed By</div>
                @if($purchaseRequest->creator->name)
                    <div class="e-signature">Digitally Approved</div>
                    <div class="e-signature-detail">Date/Time: {{ now()->format('d F Y | H:i') }} PHT (UTC+8)</div>
                @endif
            </div>
            <div class="sig-block">
                <div class="sig-line">{{ $purchaseRequest->approver->name ?? '' }}</div>
                <div class="sig-label">Approved By</div>
                @if($purchaseRequest->approver->name)
                    <div class="e-signature">Digitally Approved</div>
                    <div class="e-signature-detail">Date/Time: {{ now()->format('d F Y | H:i') }} PHT (UTC+8)</div>
                @endif
            </div>
        </div>

        <!-- Footer note -->
        <div style="text-align:center; margin-top:20px; font-size:9px; color:#555;">
            This is a system generated document.
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Print Date/Time: {{ now()->format('n/j/Y g:i:sA') }}
        </div>
    </div>
</body>
</html>
