<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIQ {{ $liquidation->liq_no ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .page { width: 100%; padding: 25px 30px; }
        @media screen {
            .page { width: 210mm; min-height: 148mm; margin: 20px auto; box-shadow: 0 0 10px rgba(0,0,0,0.15); }
        }
        @media print {
            .page { padding: 15px 20px; margin: 0; width: 100%; page-break-after: avoid; }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 8mm; }
        }

        /* Header */
        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #000;
        }
        .header-left {
            width: 280px;
        }
        .header-left img { width: 130px; height: auto; margin-bottom: 2px; }
        .header-left .addr {
            font-size: 8px;
            color: #555;
            line-height: 1.3;
        }
        .header-center {
            flex: 1;
            text-align: center;
            padding-top: 8px;
        }
        .header-center h1 {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-right {
            text-align: right;
            padding-top: 8px;
        }
        .header-right .no-label {
            font-size: 11px;
            font-weight: bold;
        }
        .header-right .no-value {
            font-size: 16px;
            font-weight: bold;
            color: #c00;
            border: 2px solid #c00;
            padding: 2px 10px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        /* Info fields */
        .info-fields {
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            border-bottom: 1px solid #999;
            padding: 3px 0;
        }
        .info-row .flabel {
            font-weight: bold;
            font-size: 10px;
            min-width: 130px;
            text-transform: uppercase;
        }
        .info-row .fvalue {
            flex: 1;
            font-size: 11px;
        }

        /* Items table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        table.items th, table.items td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 10px;
        }
        table.items th {
            background: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        table.items .col-amount {
            text-align: right;
            width: 150px;
        }
        .total-cell {
            font-weight: bold;
            font-size: 11px;
            background: #f0f0f0;
        }

        /* Signature section */
        .sig-section {
            margin-top: 12px;
        }
        .sig-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 8px;
        }
        .sig-block { }
        .sig-block .sig-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 2px;
        }
        .sig-block .sig-line {
            border-bottom: 1px solid #000;
            min-height: 28px;
            display: flex;
            align-items: flex-end;
            padding-bottom: 2px;
        }
        .sig-block .sig-name {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .sig-block .sig-subtitle {
            font-size: 8px;
            color: #555;
            margin-top: 1px;
        }
        .sig-block .sig-detail {
            font-size: 7px;
            color: #888;
            margin-top: 1px;
        }

        /* Approved By - centered full width */
        .sig-approved {
            margin-top: 8px;
            text-align: center;
        }
        .sig-approved .sig-label {
            font-weight: bold;
            font-size: 10px;
            text-align: left;
            margin-bottom: 2px;
        }
        .sig-approved .sig-names {
            border-bottom: 1px solid #000;
            min-height: 28px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 2px;
            gap: 30px;
        }
        .sig-approved .sig-name {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .sig-approved .sig-titles {
            display: flex;
            justify-content: center;
            gap: 80px;
            font-size: 8px;
            color: #555;
            margin-top: 2px;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 8px;
            color: #555;
            font-style: italic;
            text-align: center;
        }

        .car-ref {
            margin-bottom: 8px;
            padding: 5px 8px;
            border: 1px solid #999;
            background: #f9f9f9;
            font-size: 9px;
        }

        .no-print { text-align: center; padding: 15px; }
        .no-print button, .no-print a {
            padding: 8px 20px; margin: 0 5px; cursor: pointer; font-size: 12px;
            border: none; border-radius: 4px; text-decoration: none; display: inline-block;
        }
        .no-print .print-btn { background: #6b21a8; color: white; }
        .no-print .back-btn { background: #4b5563; color: white; }
    </style>
</head>
<body>

<div class="no-print">
    <a href="{{ route('liquidation_forms.show', $liquidation->id) }}" class="back-btn">Back</a>
    <button onclick="window.print()" class="print-btn">Print</button>
</div>

<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="{{ asset('images/sopod-logo.PNG') }}" alt="Meat Plus"><br>
            <span class="addr">12th Floor, 429 Victoria Bldg, UN Ave., Ermita, Manila<br>Tel. No.: 2444618 - 19 &nbsp; Fax No: 2444620</span>
        </div>
        <div class="header-center">
            <h1>LIQUIDATION FORM</h1>
        </div>
        <div class="header-right">
            <span class="no-label">LIQ No:</span>
            <span class="no-value">{{ $liquidation->liq_no }}</span>
        </div>
    </div>

    @if($liquidation->cashAdvanceRequest)
    <div class="car-ref">
        <strong>Linked Cash Advance:</strong> {{ $liquidation->cashAdvanceRequest->car_no }}
        &nbsp;|&nbsp; Payee: {{ $liquidation->cashAdvanceRequest->payee }}
        &nbsp;|&nbsp; Amount Advanced: &#8369;{{ number_format($liquidation->cashAdvanceRequest->amount_advanced, 2) }}
    </div>
    @endif

    <!-- Info Fields -->
    <div class="info-fields">
        <div class="info-row">
            <span class="flabel">Name:</span>
            <span class="fvalue">{{ $liquidation->name }}</span>
        </div>
        <div class="info-row">
            <span class="flabel">Department:</span>
            <span class="fvalue">{{ $liquidation->department }}</span>
        </div>
        <div class="info-row">
            <span class="flabel">Date Applied:</span>
            <span class="fvalue">{{ $liquidation->date_applied ? $liquidation->date_applied->format('F d, Y') : '' }}</span>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items">
        <thead>
            <tr>
                <th>Particulars</th>
                <th class="col-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($liquidation->items as $item)
            <tr>
                <td>{{ $item->particulars }}</td>
                <td class="col-amount">&#8369;{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
            @for($i = $liquidation->items->count(); $i < 5; $i++)
            <tr>
                <td>&nbsp;</td>
                <td class="col-amount">&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    <!-- Total -->
    <table class="items" style="margin-top: -1px;">
        <tr>
            <td class="total-cell" style="text-align: right; border: 1px solid #000; padding: 5px 8px;">TOTAL AMOUNT SPENT:</td>
            <td class="total-cell col-amount" style="border: 1px solid #000; padding: 5px 8px;">&#8369;{{ number_format($liquidation->total_amount_spent, 2) }}</td>
        </tr>
    </table>

    <!-- Signatures: Submitted By + Checked By -->
    <div class="sig-section">
        <div class="sig-row">
            <div class="sig-block">
                <div class="sig-label">Submitted By:</div>
                <div class="sig-line">
                    <span class="sig-name">{{ $liquidation->submitted_by ?? $liquidation->creator->name ?? '' }}</span>
                </div>
                <div class="sig-subtitle">Signature Over Printed Name</div>
                @if($liquidation->creator && $liquidation->created_at)
                <div class="sig-detail">@include('partials.esignature', ['signer' => $liquidation->creator]) &middot; {{ $liquidation->created_at->format('d M Y | H:i') }}</div>
                @endif
            </div>
            <div class="sig-block">
                <div class="sig-label">Checked By:</div>
                <div class="sig-line">
                    <span class="sig-name">{{ $liquidation->dhApprover->name ?? '' }}</span>
                </div>
                <div class="sig-subtitle">Immediate Superior</div>
                @if($liquidation->dhApprover && $liquidation->dh_approved_at)
                <div class="sig-detail">
                    @include('partials.esignature', ['signer' => $liquidation->dhApprover]) &middot; {{ $liquidation->dh_approved_at->format('d M Y | H:i') }}
                    @if($liquidation->dh_approved_location) &middot; {{ $liquidation->dh_approved_location }} @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Approved By - full width centered -->
        <div class="sig-approved">
            <div class="sig-label">Approved By:</div>
            <div class="sig-names">
                <span class="sig-name">{{ $liquidation->executiveApprover->name ?? '' }}</span>
            </div>
            @if($liquidation->executiveApprover && $liquidation->executive_approved_at)
            <div style="font-size: 7px; color: #888; margin-top: 2px;">
                @include('partials.esignature', ['signer' => $liquidation->executiveApprover]) &middot; {{ $liquidation->executive_approved_at->format('d M Y | H:i') }}
                @if($liquidation->executive_approved_location) &middot; {{ $liquidation->executive_approved_location }} @endif
            </div>
            @endif
        </div>
    </div>

    <div class="footer-note">
        Please attach invoices, Official receipts (OR) and other supporting documents.
    </div>
</div>

<script>
    window.onload = function() { setTimeout(function() { window.print(); }, 500); };
</script>
</body>
</html>
