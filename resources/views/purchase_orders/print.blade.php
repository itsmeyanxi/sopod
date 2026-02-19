<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO {{ $purchaseOrder->po_no }} - Purchase Order</title>
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
        .doc-title { font-size: 16px; font-weight: bold; text-transform: uppercase; color: #b91c1c; }
        .doc-no { font-size: 13px; font-weight: bold; margin-top: 4px; }

        /* Info rows */
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 10px; }
        .info-grid td { padding: 2px 6px; border: 1px solid #ccc; vertical-align: top; }
        .info-grid .label { font-weight: bold; background: #f5f5f5; white-space: nowrap; width: 100px; }
        .info-grid .value { }

        /* Info header row (bold field names inline) */
        .hdr-row { display: flex; gap: 0; width: 100%; border: 1px solid #000; margin-bottom: -1px; font-size: 10px; }
        .hdr-cell { flex: 1; border-right: 1px solid #000; padding: 2px 5px; }
        .hdr-cell:last-child { border-right: none; }
        .hdr-cell .lbl { font-weight: bold; font-size: 9px; }
        .hdr-cell .val { }

        /* Supplier info table */
        .sup-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; font-size: 10px; }
        .sup-table td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
        .sup-table .lbl { font-weight: bold; font-size: 9px; text-transform: uppercase; }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; font-size: 10px; }
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

        /* Totals box */
        .totals-box { float: right; width: 55%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
        .totals-box td { padding: 2px 8px; border: 1px solid #000; }
        .totals-box .tlabel { text-align: right; }
        .totals-box .tvalue { text-align: right; font-weight: bold; width: 100px; }
        .totals-box .grand { font-weight: bold; font-size: 11px; background: #f0f0f0; }
        .clearfix::after { content: ''; display: table; clear: both; }

        /* Instructions */
        .instructions { font-size: 9px; margin-bottom: 8px; }
        .instructions p { margin-bottom: 2px; }

        /* Remarks + signatures bottom */
        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }
        .remarks-box { border: 1px solid #000; padding: 5px; min-height: 60px; }
        .remarks-label { font-weight: bold; font-size: 10px; margin-bottom: 4px; }
        .sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .sig-block { }
        .sig-line { border-bottom: 1px solid #000; min-height: 30px; padding-bottom: 3px; font-size: 10px; font-weight: bold; }
        .sig-label { font-weight: bold; font-size: 9px; text-transform: uppercase; margin-top: 2px; }

        /* Contact */
        .contact-row { margin-top: 8px; font-size: 10px; }

        /* Copy labels */
        .copy-labels { display: flex; justify-content: space-between; margin-top: 12px; border-top: 1px solid #000; padding-top: 6px; font-size: 9px; font-weight: bold; }

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
        <a href="{{ route('purchase_orders.show', $purchaseOrder->id) }}" class="back-btn">Back to PO</a>
        <button onclick="window.print()" class="print-btn">Print</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('images/sopod-logo.png') }}" class="header-logo" alt="Logo">
            <div class="header-company">
                <div class="company-name">{{ $purchaseOrder->company }}</div>
            </div>
            <div class="header-right">
                <div class="doc-title">Purchase Order</div>
                <div class="doc-no">P.O. {{ $purchaseOrder->po_no }}</div>
            </div>
        </div>

        <!-- Supplier + Date + PR No row -->
        <table class="sup-table">
            <tr>
                <td style="width:40%">
                    <div class="lbl">Supplier Name</div>
                    <div>{{ $purchaseOrder->supplier ?? '' }}</div>
                </td>
                <td style="width:20%">
                    <div class="lbl">Date</div>
                    <div>{{ $purchaseOrder->order_date->format('m/d/Y') }}</div>
                </td>
                <td style="width:20%">
                    <div class="lbl">PR No.</div>
                    <div>{{ $purchaseOrder->pr_no ?? '' }}</div>
                </td>
                <td style="width:20%">
                    <div class="lbl">Currency</div>
                    <div>{{ $purchaseOrder->currency ?? 'PHP' }}
                        @if(($purchaseOrder->currency ?? 'PHP') !== 'PHP')
                            <span style="font-size:9px; color:#555;">(Rate: {{ number_format($purchaseOrder->exchange_rate, 4) }} PHP)</span>
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="lbl">Supplier Address</div>
                    <div>{{ $purchaseOrder->supplier_address ?? '' }}</div>
                </td>
                <td colspan="2">
                    <div class="lbl">Payment Terms</div>
                    <div>{{ $purchaseOrder->payment_terms ?? '' }}</div>
                </td>
                <td>
                    <div class="lbl">Target Arrival</div>
                    <div>{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('m/d/Y') : '' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="lbl">Ship To</div>
                    <div>{{ $purchaseOrder->consignee ?? '' }}{{ $purchaseOrder->consignee_address ? ', ' . $purchaseOrder->consignee_address : '' }}</div>
                </td>
                <td colspan="2">
                    <div class="lbl">Location / House</div>
                    <div>{{ $purchaseOrder->location ?? '' }}{{ $purchaseOrder->house ? ' / ' . $purchaseOrder->house : '' }}</div>
                </td>
                <td>
                    <div class="lbl">Delivery Address</div>
                    <div>{{ $purchaseOrder->delivery_address ?? '' }}</div>
                </td>
            </tr>
        </table>

        <!-- Instruction line -->
        <p style="font-size:10px; margin-bottom:6px; font-style:italic;">
            Please enter our order in accordance with prices, delivery and specifications given below:
        </p>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:30px">No.</th>
                    <th style="width:70px">Item Code</th>
                    <th>Description</th>
                    <th style="width:70px">Quantity</th>
                    <th style="width:50px">UoM</th>
                    <th style="width:85px">Unit Price</th>
                    <th style="width:85px">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-left">{{ $item->item_code ?? '' }}</td>
                        <td class="text-left">{{ $item->description }}</td>
                        <td>{{ number_format($item->qty, 2) }}</td>
                        <td>{{ $item->uom }}</td>
                        <td class="text-right">{{ $item->unit_price ? number_format($item->unit_price, 2) : '' }}</td>
                        <td class="text-right">{{ $item->total ? number_format($item->total, 2) : '' }}</td>
                    </tr>
                @endforeach
                @for($i = $purchaseOrder->items->count(); $i < 8; $i++)
                    <tr>
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <!-- Totals -->
        @php
            $subtotal = $purchaseOrder->items->sum('total');
            $totalTax = $purchaseOrder->items->sum('tax');
            $grandTotal = $subtotal;
        @endphp
        <div class="clearfix">
            <table class="totals-box">
                <tr>
                    <td class="tlabel">Total Purchase Before VAT</td>
                    <td class="tvalue">{{ number_format($subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="tlabel">Discount 0.00 %</td>
                    <td class="tvalue">0.00</td>
                </tr>
                <tr>
                    <td class="tlabel">VAT Amount</td>
                    <td class="tvalue">{{ number_format($totalTax, 2) }}</td>
                </tr>
                <tr>
                    <td class="tlabel">Total Purchase After VAT</td>
                    <td class="tvalue">{{ number_format($subtotal + $totalTax, 2) }}</td>
                </tr>
                <tr>
                    <td class="tlabel">Withholding Tax</td>
                    <td class="tvalue">0.00</td>
                </tr>
                <tr class="grand">
                    <td class="tlabel">Grand Total</td>
                    <td class="tvalue">{{ number_format($subtotal + $totalTax, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <p>1. Materials must be not in excess of quantity ordered unless so indicated and must be of required quality and specifications, otherwise they will be returned at the seller's expense.</p>
            <p>2. Corresponding invoice(s) for this order must contain full description of goods/services without abbreviations and must show terms of purchase, PO number and correct price.</p>
            <p>3. The company reserves the right to cancel this order without any obligation on its part due to the failure of the supplier to meet delivery terms and or to fill up order within the specified time.</p>
            <p>4. The acceptance of this order implies acceptance of the conditions and instructions herein stipulated.</p>
            <p>5. No account shall be paid unless sales invoices and or delivery receipts are accompanied by this purchase order.</p>
        </div>

        <!-- Bottom: Remarks + Signatures -->
        <div class="bottom-grid">
            <div>
                <div class="remarks-label">REMARKS</div>
                <div class="remarks-box">{{ $purchaseOrder->remarks ?? '' }}</div>
            </div>
            <div class="sig-grid">
                <div class="sig-block">
                    <div class="sig-label">Prepared By:</div>
                    <div class="sig-line" style="min-height:28px;">{{ $purchaseOrder->creator->name ?? '' }}</div>
                </div>
                <div class="sig-block">
                    <div class="sig-label">Approved By:</div>
                    <div class="sig-line" style="min-height:28px;">{{ $purchaseOrder->approver->name ?? '' }}</div>
                </div>
                <div class="sig-block" style="margin-top:8px;">
                    <div class="sig-label">Supplier's Conforme:</div>
                    <div class="sig-line" style="min-height:28px;"></div>
                </div>
                <div class="sig-block" style="margin-top:8px;">
                    <div class="sig-label">Signature Over Printed Name &amp; Date</div>
                    <div class="sig-line" style="min-height:28px;"></div>
                </div>
            </div>
        </div>

        <!-- Contact Person -->
        <div class="contact-row">
            <strong>CONTACT PERSON:</strong> {{ $purchaseOrder->consignee ?? '-No Sales Employee-' }}
        </div>

        <!-- Footer -->
        <div style="text-align:center; margin-top:6px; font-size:9px; color:#555;">
            This is a system generated document. No signature is required.
            &nbsp;&nbsp;|&nbsp;&nbsp;
            This document is not valid for claiming Input Tax
        </div>

        <!-- Copy Labels -->
        <div class="copy-labels">
            <span>COPY 1 : VENDOR / SUPPLIER</span>
            <span>COPY 2 : ACCOUNTING</span>
            <span>COPY 3 : PURCHASING</span>
        </div>

        <div style="text-align:right; font-size:9px; color:#555; margin-top:4px;">
            Print Date/Time: {{ now()->format('n/j/Y g:i:sA') }}
        </div>
    </div>
</body>
</html>
