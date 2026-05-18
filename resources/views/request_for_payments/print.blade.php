<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFP {{ $rfp->rfp_no ?? '' }} - Request for Payment</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 100%;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        @media screen {
            .page {
                width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
        @media print {
            html { margin: 0; padding: 0; }
            body { background: #fff; margin: 0; padding: 0; }
            .page { padding: 20px; margin: 0; width: 100%; box-sizing: border-box; page-break-after: avoid; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }

        /* Header Section */
        .header-top {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header-title-section {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .main-title {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #ff0000;
            line-height: 1.3;
        }

        .logo-section {
            text-align: right;
        }

        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .header-info-right {
            font-size: 10px;
            text-align: right;
            line-height: 1.6;
        }

        .header-info-right strong {
            font-weight: bold;
        }

        /* Payment Methods */
        .payment-methods {
            margin-bottom: 18px;
            padding: 10px 0;
            border-bottom: 1px solid #ccc;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
        }

        .payment-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
        }

        .checkbox {
            width: 12px;
            height: 12px;
            border: 1.5px solid #000;
            flex-shrink: 0;
            display: inline-block;
        }

        .checkbox.checked {
            background: #000;
        }

        /* Sections */
        .section-title {
            font-weight: bold;
            font-size: 11px;
            margin-top: 12px;
            margin-bottom: 6px;
            color: #000;
        }

        .payee-box {
            border: 1px solid #000;
            padding: 8px;
            min-height: 25px;
            font-size: 10px;
            margin-bottom: 12px;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        th {
            background: #f0f0f0;
            color: #000;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #999;
        }

        td {
            padding: 6px 8px;
            border: 1px solid #999;
        }

        .amount {
            text-align: right;
            font-weight: 500;
        }

        tr {
            page-break-inside: avoid;
        }

        /* Info Fields Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 12px 0 15px 0;
        }

        .info-field {
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
        }

        .info-label {
            font-weight: bold;
            font-size: 9px;
            color: #000;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 10px;
            min-height: 16px;
            padding-top: 2px;
        }

        /* Remarks */
        .remarks-box {
            border: 1px solid #000;
            padding: 8px;
            min-height: 35px;
            font-size: 10px;
            margin-bottom: 15px;
        }

        /* Signatures Section */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
            font-size: 10px;
        }

        .sig-block {
            text-align: center;
        }

        .sig-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 25px;
        }

        .sig-line {
            border-top: 1px solid #000;
            min-height: 35px;
            margin-bottom: 3px;
        }

        .sig-name {
            font-size: 8px;
            font-weight: 500;
        }

        .e-signature {
            font-size: 8px;
            color: #333;
            margin-top: 4px;
            font-style: normal;
            font-weight: 500;
        }

        .e-signature-detail {
            font-size: 7px;
            color: #666;
            margin-top: 2px;
            font-style: italic;
        }

        /* Finance Section */
        .finance-section {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 12px;
        }

        .finance-title {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            color: #d00;
            margin-bottom: 12px;
        }

        .approvals {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            text-align: center;
            font-size: 10px;
        }

        .approval-block {
            display: flex;
            flex-direction: column;
        }

        .approval-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 25px;
        }

        .approval-line {
            border-top: 1px solid #000;
            min-height: 35px;
            margin-bottom: 3px;
        }

        .approval-name {
            font-size: 8px;
            font-weight: 500;
        }

        .approval-e-signature {
            font-size: 8px;
            color: #333;
            margin-top: 4px;
            font-style: normal;
            font-weight: 500;
        }

        .approval-e-signature-detail {
            font-size: 7px;
            color: #666;
            margin-top: 2px;
            font-style: italic;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

        .footer p {
            margin: 2px 0;
        }

        /* Print Controls */
        .print-controls {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
        }

        .print-btn, .back-btn {
            padding: 10px 25px;
            font-size: 13px;
            cursor: pointer;
            border-radius: 4px;
            margin: 0 5px;
            border: none;
            font-weight: 500;
        }

        .print-btn {
            background: #6b21a8;
            color: white;
        }

        .print-btn:hover {
            background: #581c87;
        }

        .back-btn {
            background: #4b5563;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .back-btn:hover {
            background: #364152;
        }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <a href="{{ route('request_for_payments.show', $rfp->id) }}" class="back-btn">Back to RFP</a>
        <button type="button" onclick="setTimeout(() => { window.print(); }, 100);" class="print-btn">Print Request for Payment</button>
    </div>

    <div class="page">
        <!-- Header -->
        @php $methods = is_array($rfp->payment_methods) ? $rfp->payment_methods : json_decode($rfp->payment_methods ?? '[]', true); @endphp
        <div class="header-top" style="display:grid;grid-template-columns:1fr auto;column-gap:30px;row-gap:0;align-items:flex-start;">
            <div class="header-title-section">
                <div class="main-title">REQUEST FOR PAYMENT</div>
                <div class="company-name">Meatplus Trading Corp.</div>
            </div>
            <div class="logo-section">
                <img src="{{ asset('images/sopod-logo.PNG') }}" class="logo" alt="Logo">
                <div class="header-info-right">
                    <div><strong>Date:</strong> {{ $rfp->date ? $rfp->date->format('F j, Y') : '' }}</div>
                    <div><strong>Due Date:</strong> {{ $rfp->due_date ? $rfp->due_date->format('F j, Y') : '' }}</div>
                </div>
            </div>
            <div style="grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;flex-wrap:wrap;gap:3px 10px;">
                    @foreach(['managers_check'=>'MANAGER CHECK','fund_transfer'=>'FUND TRANSFER','regular_check'=>'REGULAR CHECK','pdc'=>'PDC','wire_transfer'=>'WIRE TRANSFER','auto_debit'=>'AUTO DEBIT','cash'=>'CASH','other'=>'OTHER'] as $key=>$label)
                    <div style="display:flex;align-items:center;gap:2px;font-size:8px;">
                        <div style="width:9px;height:9px;border:1px solid #000;flex-shrink:0;{{ in_array($key,$methods)?'background:#000;':'' }}"></div>
                        <span>{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
                <span style="font-size:10px;white-space:nowrap;"><strong>RFP #:</strong> {{ $rfp->rfp_no ?? '' }}</span>
            </div>
        </div>

        <!-- Payee -->
        <div class="section-title">Payee (Vendor/Supplier)</div>
        <div class="payee-box">
            <div style="font-weight:bold;">{{ $rfp->payee ?? '' }}</div>
            @if($rfp->payment_terms)
            <div style="margin-top:3px;font-size:9px;color:#555;">Terms: <strong>{{ $rfp->payment_terms }}</strong></div>
            @endif
        </div>

        <!-- PR / PO Reference -->
        @if($po)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;font-size:10px;">
            <div class="info-field">
                <div class="info-label">PR NO.</div>
                <div class="info-value">{{ $po->pr_no ?? '—' }}</div>
            </div>
            <div class="info-field">
                <div class="info-label">PO NO.</div>
                <div class="info-value">{{ $po->po_no ?? '—' }}</div>
            </div>
        </div>
        @endif

        <!-- Particulars Table -->
        @php $currSym = ($rfp->currency ?? 'PHP') === 'USD' ? '$ ' : '₱'; @endphp
        <table>
            <thead>
                <tr>
                    <th style="width: 75%;">Particulars</th>
                    <th style="width: 25%; text-align: right;">Amount ({{ $rfp->currency ?? 'PHP' }})</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $rfp->particulars ?? '' }}</td>
                    <td class="amount">{{ $rfp->amount ? $currSym . number_format($rfp->amount, 2) : '' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- GRPO Items (preferred) or PO Items fallback -->
        @if($po || $grpos->isNotEmpty())
        @php
            $useGrpo = $grpos->isNotEmpty();
            $poNo    = $po->po_no ?? '';
            $items = $useGrpo
                ? $grpos->map(fn($g,$i) => (object)[
                    'no'         => $i+1,
                    'item_code'  => $g->grpo_no ?? '',
                    'description'=> $g->items ?? '',
                    'brand'      => $g->brand ?? '',
                    'qty'        => $g->actual_qty,
                    'uom'        => $g->uom ?? 'KG',
                    'unit_price' => $g->price ?? 0,
                    'total'      => ($g->actual_qty ?? 0) * ($g->price ?? 0),
                  ])
                : ($po?->items->map(fn($item,$i) => (object)[
                    'no'         => $item->item_no ?? $i+1,
                    'item_code'  => $item->item_code ?? '',
                    'description'=> $item->description ?? '',
                    'brand'      => $item->brand ?? '',
                    'qty'        => $item->qty,
                    'uom'        => $item->uom ?? '',
                    'unit_price' => $item->unit_price,
                    'total'      => $item->total,
                  ]) ?? collect());
        @endphp
        <div class="section-title">
            {{ $useGrpo ? 'GRPO Items' . ($poNo ? ' — '.$poNo : '') : 'Purchase Order Items — '.$poNo }}
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:4%;">#</th>
                    <th style="width:13%;">Item Code</th>
                    <th style="width:28%;">Description</th>
                    <th style="width:13%;">Brand</th>
                    <th style="width:9%;text-align:center;">Actual Received</th>
                    <th style="width:7%;text-align:center;">UOM</th>
                    <th style="width:13%;text-align:right;">Unit Price</th>
                    <th style="width:13%;text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->no }}</td>
                    <td>{{ $item->item_code }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->brand }}</td>
                    <td style="text-align:center;">{{ number_format($item->qty, 2) }}</td>
                    <td style="text-align:center;">{{ $item->uom }}</td>
                    <td class="amount">{{ $currSym }}{{ number_format($item->unit_price, 2) }}</td>
                    <td class="amount">{{ $currSym }}{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
                @if(($rfp->currency ?? 'PHP') === 'USD' && optional($po)->exchange_rate)
                <tr style="background:#fffbe6;">
                    <td colspan="7" style="text-align:right;font-weight:bold;">PHP Equivalent (Rate: {{ $po->exchange_rate }}):</td>
                    <td class="amount" style="font-weight:bold;">₱{{ number_format($items->sum('total') * $po->exchange_rate, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        @endif

        @if($poTotal > 0)
        <div style="margin:8px 0;padding:8px 10px;border:1px solid #ccc;border-radius:4px;font-size:9pt;">
            <div style="font-weight:bold;margin-bottom:4px;">Payment Progress (vs PO Total: {{ $currSym }}{{ number_format($poTotal,2) }})</div>
            <div style="display:flex;gap:16px;">
                @if($paidBefore > 0)
                <span>Previously Paid: <strong>{{ $currSym }}{{ number_format($paidBefore,2) }}</strong></span>
                @endif
                <span>This RFP: <strong>{{ $currSym }}{{ number_format($thisAmount,2) }}</strong></span>
                <span>Total Paid: <strong>{{ $currSym }}{{ number_format($paidBefore + $thisAmount,2) }}</strong></span>
                <span>Remaining: <strong>{{ $currSym }}{{ number_format($remaining,2) }}</strong></span>
            </div>
        </div>
        @endif

        <!-- Info Fields -->
        <div class="info-grid">
            <div class="info-field">
                <div class="info-label">APV No.</div>
                <div class="info-value">{{ $rfp->apv_no ?? '' }}</div>
            </div>
            <div class="info-field">
                <div class="info-label">CV No.</div>
                <div class="info-value">{{ $rfp->cv_no ?? '' }}</div>
            </div>
            <div class="info-field">
                <div class="info-label">Bank</div>
                <div class="info-value">{{ $rfp->bank ?? '' }}</div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-label">Prepared By:</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $rfp->creator->name ?? '' }}</div>
                @if($rfp->creator && $rfp->created_at)
                    <div class="e-signature">@include('partials.esignature', ['signer' => $rfp->creator])</div>
                    <div class="e-signature-detail">Date/Time: {{ $rfp->created_at->format('d F Y | H:i') }} PHT (UTC+8)</div>
                @endif
            </div>
            <div class="sig-block">
                <div class="sig-label">Checked By:</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $rfp->departmentHeadApprover->name ?? '' }}</div>
                @if($rfp->departmentHeadApprover && $rfp->department_head_approved_at)
                    <div class="e-signature">@include('partials.esignature', ['signer' => $rfp->departmentHeadApprover])</div>
                    <div class="e-signature-detail">Date/Time: {{ $rfp->department_head_approved_at->format('d F Y | H:i') }} PHT (UTC+8)</div>
                    @if($rfp->department_head_approved_latitude && $rfp->department_head_approved_longitude)
                        <div class="e-signature-detail">Coords: {{ $rfp->department_head_approved_latitude }}, {{ $rfp->department_head_approved_longitude }}@if($rfp->department_head_approved_location) ({{ $rfp->department_head_approved_location }})@endif</div>
                    @endif
                @endif
            </div>
            <div class="sig-block">
                <div class="sig-label">Checked By:</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $rfp->accountingApprover->name ?? '' }}</div>
                @if($rfp->accountingApprover && $rfp->accounting_approved_at)
                    <div class="e-signature">@include('partials.esignature', ['signer' => $rfp->accountingApprover])</div>
                    <div class="e-signature-detail">Date/Time: {{ $rfp->accounting_approved_at->format('d F Y | H:i') }} PHT (UTC+8)</div>
                    @if($rfp->accounting_approved_latitude && $rfp->accounting_approved_longitude)
                        <div class="e-signature-detail">Coords: {{ $rfp->accounting_approved_latitude }}, {{ $rfp->accounting_approved_longitude }}@if($rfp->accounting_approved_location) ({{ $rfp->accounting_approved_location }})@endif</div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Finance Section -->
        <div class="finance-section">
            <div class="finance-title">-FOR FINANCE USE ONLY-</div>
            <div class="approvals">
                <div class="approval-block">
                    <div class="approval-label">Approved By:</div>
                    <div class="approval-line"></div>
                    <div class="approval-name">{{ $rfp->approver->name ?? '' }}</div>
                    @if($rfp->approver && $rfp->approved_at)
                        <div class="approval-e-signature">@include('partials.esignature', ['signer' => $rfp->approver])</div>
                        <div class="approval-e-signature-detail">Date/Time: {{ $rfp->approved_at->format('d F Y | H:i') }} PHT (UTC+8)</div>
                        @if($rfp->approved_latitude && $rfp->approved_longitude)
                            <div class="approval-e-signature-detail">Coords: {{ $rfp->approved_latitude }}, {{ $rfp->approved_longitude }}@if($rfp->approved_location) ({{ $rfp->approved_location }})@endif</div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This document is not valid for claiming Input Tax</p>
            <p>Print Date/Time: {{ now()->format('m/d/Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
