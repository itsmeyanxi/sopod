<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAR {{ $car->car_no ?? '' }}</title>
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
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
        }
        .header-logo {
            width: 70px;
            margin-right: 15px;
        }
        .header-logo img { width: 65px; height: auto; }
        .header-center {
            flex: 1;
            text-align: center;
            padding-top: 5px;
        }
        .header-center h1 {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-right {
            text-align: right;
            padding-top: 5px;
        }
        .header-right .no-label {
            font-size: 14px;
            font-weight: bold;
        }
        .header-right .no-value {
            font-size: 18px;
            font-weight: bold;
            color: #c00;
            border: 2px solid #c00;
            padding: 2px 12px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        /* Fields grid */
        .fields-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            margin-bottom: 8px;
        }
        .field-row {
            display: flex;
            align-items: baseline;
            padding: 3px 0;
            border-bottom: 1px solid #999;
            margin-right: 15px;
        }
        .field-row .flabel {
            font-weight: bold;
            font-size: 10px;
            min-width: 110px;
            white-space: nowrap;
        }
        .field-row .fvalue {
            flex: 1;
            font-size: 11px;
            padding-left: 5px;
        }

        /* Purpose */
        .purpose-row {
            display: flex;
            align-items: baseline;
            padding: 3px 0;
            border-bottom: 1px solid #999;
            margin-bottom: 12px;
        }
        .purpose-row .flabel {
            font-weight: bold;
            font-size: 10px;
            min-width: 110px;
        }
        .purpose-row .fvalue {
            flex: 1;
            font-size: 11px;
            padding-left: 5px;
            min-height: 16px;
        }

        /* Signatures - top row: Checked By + Approved By */
        .sig-top {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 10px;
            margin-top: 15px;
        }
        .sig-block {
            text-align: center;
        }
        .sig-block .sig-label {
            font-weight: bold;
            font-size: 10px;
            text-align: left;
            margin-bottom: 3px;
        }
        .sig-block .sig-line {
            border-bottom: 1px solid #000;
            min-height: 30px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 3px;
        }
        .sig-block .sig-name {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .sig-block .sig-subtitle {
            font-size: 8px;
            color: #555;
            margin-top: 2px;
            text-align: center;
        }
        .sig-block .sig-detail {
            font-size: 7px;
            color: #888;
            margin-top: 2px;
        }

        /* Fine print */
        .fine-print {
            font-size: 8px;
            color: #333;
            line-height: 1.4;
            margin: 10px 0;
            padding: 6px 0;
            border-top: 1px solid #999;
        }

        /* Requested By at bottom right */
        .sig-bottom {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .sig-bottom .sig-block {
            width: 250px;
        }

        .no-print {
            text-align: center;
            padding: 15px;
        }
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
    <a href="{{ route('cash_advance_requests.show', $car->id) }}" class="back-btn">Back</a>
    <button onclick="window.print()" class="print-btn">Print</button>
</div>

<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-logo">
            <img src="{{ asset('images/sopod-logo.PNG') }}" alt="MeatPlus">
        </div>
        <div class="header-center">
            <h1>CASH ADVANCE REQUEST</h1>
        </div>
        <div class="header-right">
            <span class="no-label">N<sup>o</sup></span>
            <span class="no-value">{{ $car->car_no }}</span>
        </div>
    </div>

    <!-- Fields -->
    <div class="fields-section">
        <div class="field-row">
            <span class="flabel">Payee</span>
            <span class="fvalue">: {{ $car->payee }}</span>
        </div>
        <div class="field-row">
            <span class="flabel">Date Requested</span>
            <span class="fvalue">: {{ $car->date_requested ? $car->date_requested->format('F d, Y') : '' }}</span>
        </div>
        <div class="field-row">
            <span class="flabel">Department</span>
            <span class="fvalue">: {{ $car->department }}</span>
        </div>
        <div class="field-row">
            <span class="flabel">Date Needed</span>
            <span class="fvalue">: {{ $car->date_needed ? $car->date_needed->format('F d, Y') : '' }}</span>
        </div>
        <div class="field-row">
            <span class="flabel">Purpose</span>
            <span class="fvalue">: {{ $car->purpose }}</span>
        </div>
        <div class="field-row">
            <span class="flabel">Amount Advanced</span>
            <span class="fvalue">: &#8369;{{ number_format($car->amount_advanced, 2) }}</span>
        </div>
    </div>

    <!-- Checked By + Approved By -->
    <div class="sig-top">
        <div class="sig-block">
            <div class="sig-label">Checked by :</div>
            <div class="sig-line">
                <span class="sig-name">{{ $car->dhApprover->name ?? '' }}</span>
            </div>
            <div class="sig-subtitle">Department Head Signature over Printed Name</div>
            @if($car->dhApprover && $car->dh_approved_at)
            <div class="sig-detail">
                @include('partials.esignature', ['signer' => $car->dhApprover]) &middot; {{ $car->dh_approved_at->format('d M Y | H:i') }}
                @if($car->dh_approved_location) &middot; {{ $car->dh_approved_location }} @endif
            </div>
            @endif
        </div>
        <div class="sig-block">
            <div class="sig-label">Approved by:</div>
            <div class="sig-line">
                <span class="sig-name">{{ $car->executiveApprover->name ?? '' }}</span>
            </div>
            <div class="sig-subtitle">Signature over Printed Name</div>
            @if($car->executiveApprover && $car->executive_approved_at)
            <div class="sig-detail">
                @include('partials.esignature', ['signer' => $car->executiveApprover]) &middot; {{ $car->executive_approved_at->format('d M Y | H:i') }}
                @if($car->executive_approved_location) &middot; {{ $car->executive_approved_location }} @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Fine Print -->
    <div class="fine-print">
        I hereby acknowledge receipt of the above sum of money and hereby agree to liquidate in 5 calendar days after the cash advance serve its purpose and provide receipts to document the expenditures. I also hereby grant Meatplus Trading Corporation, authority to deduct any unliquidated and undocumented expenditure from my salary or final pay without the necessity of a reminder or prior notice and/or without prejudice to other.
    </div>

    <!-- Requested By at bottom right -->
    <div class="sig-bottom">
        <div class="sig-block">
            <div class="sig-label">Requested by:</div>
            <div class="sig-line">
                <span class="sig-name">{{ $car->requested_by ?? $car->creator->name ?? '' }}</span>
            </div>
            <div class="sig-subtitle">Signature over Printed Name</div>
            @if($car->creator && $car->created_at)
            <div class="sig-detail">
                @include('partials.esignature', ['signer' => $car->creator]) &middot; {{ $car->created_at->format('d M Y | H:i') }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    window.onload = function() { setTimeout(function() { window.print(); }, 500); };
</script>
</body>
</html>
