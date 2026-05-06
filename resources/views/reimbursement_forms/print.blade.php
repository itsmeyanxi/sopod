<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RI {{ $reimbursement->ri_no ?? '' }}</title>
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
            margin-bottom: 10px;
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

        /* Department + Date row */
        .top-fields {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
        }
        .top-fields .field {
            font-size: 10px;
        }
        .top-fields .field .flabel {
            font-weight: bold;
            text-transform: uppercase;
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
        table.items .col-date { width: 100px; }
        table.items .col-cost { text-align: right; width: 130px; }
        .total-cell {
            font-weight: bold;
            font-size: 11px;
            background: #f0f0f0;
        }
        .reimburse-cell {
            font-weight: bold;
            font-size: 11px;
            background: #f0f0f0;
        }

        /* Signature section */
        .sig-section { margin-top: 12px; }
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

        /* Approved By */
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
    <a href="{{ route('reimbursement_forms.show', $reimbursement->id) }}" class="back-btn">Back</a>
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
            <h1>REIMBURSEMENT FORM</h1>
        </div>
        <div class="header-right">
            <span class="no-label">RI No:</span>
            <span class="no-value">{{ $reimbursement->ri_no }}</span>
        </div>
    </div>

    <!-- Department + Date Applied -->
    <div class="top-fields">
        <div class="field">
            <span class="flabel">DEPARTMENT:</span> {{ $reimbursement->department }}
        </div>
        <div class="field">
            <span class="flabel">DATE APPLIED:</span> {{ $reimbursement->date_applied ? $reimbursement->date_applied->format('F d, Y') : '' }}
        </div>
    </div>

    <!-- Items Table -->
    <table class="items">
        <thead>
            <tr>
                <th class="col-date">Date</th>
                <th>Particulars</th>
                <th class="col-cost">Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reimbursement->items as $item)
            <tr>
                <td class="col-date">{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '' }}</td>
                <td>{{ $item->particulars }}</td>
                <td class="col-cost">&#8369;{{ number_format($item->cost, 2) }}</td>
            </tr>
            @endforeach
            @for($i = $reimbursement->items->count(); $i < 5; $i++)
            <tr>
                <td class="col-date">&nbsp;</td>
                <td>&nbsp;</td>
                <td class="col-cost">&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    <!-- Totals -->
    <table class="items" style="margin-top: -1px;">
        <tr>
            <td class="total-cell" style="text-align: right; border: 1px solid #000; padding: 5px 8px;">TOTAL AMOUNT SPENT:</td>
            <td class="total-cell col-cost" style="border: 1px solid #000; padding: 5px 8px;">&#8369;{{ number_format($reimbursement->total_amount_spent, 2) }}</td>
        </tr>
        <tr>
            <td class="reimburse-cell" style="text-align: right; border: 1px solid #000; padding: 5px 8px;">AMOUNT TO BE REIMBURSED:</td>
            <td class="reimburse-cell col-cost" style="border: 1px solid #000; padding: 5px 8px;">&#8369;{{ number_format($reimbursement->amount_to_be_reimbursed, 2) }}</td>
        </tr>
    </table>

    <!-- Signatures: Submitted By + Checked By -->
    <div class="sig-section">
        <div class="sig-row">
            <div class="sig-block">
                <div class="sig-label">Submitted By:</div>
                <div class="sig-line">
                    <span class="sig-name">{{ $reimbursement->submitted_by ?? $reimbursement->creator->name ?? '' }}</span>
                </div>
                <div class="sig-subtitle">Signature Over Printed Name</div>
                @if($reimbursement->creator && $reimbursement->created_at)
                <div class="sig-detail">@include('partials.esignature', ['signer' => $reimbursement->creator]) &middot; {{ $reimbursement->created_at->format('d M Y | H:i') }}</div>
                @endif
            </div>
            <div class="sig-block">
                <div class="sig-label">Checked By:</div>
                <div class="sig-line">
                    <span class="sig-name">{{ $reimbursement->dhApprover->name ?? '' }}</span>
                </div>
                <div class="sig-subtitle">Department Head</div>
                @if($reimbursement->dhApprover && $reimbursement->dh_approved_at)
                <div class="sig-detail">
                    @include('partials.esignature', ['signer' => $reimbursement->dhApprover]) &middot; {{ $reimbursement->dh_approved_at->format('d M Y | H:i') }}
                    @if($reimbursement->dh_approved_location) &middot; {{ $reimbursement->dh_approved_location }} @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Approved By -->
        <div class="sig-approved">
            <div class="sig-label">Approved By:</div>
            <div class="sig-names">
                <span class="sig-name">{{ $reimbursement->executiveApprover->name ?? '' }}</span>
            </div>
            @if($reimbursement->executiveApprover && $reimbursement->executive_approved_at)
            <div style="font-size: 7px; color: #888; margin-top: 2px;">
                @include('partials.esignature', ['signer' => $reimbursement->executiveApprover]) &middot; {{ $reimbursement->executive_approved_at->format('d M Y | H:i') }}
                @if($reimbursement->executive_approved_location) &middot; {{ $reimbursement->executive_approved_location }} @endif
            </div>
            @else
            <div class="sig-titles">
                <span>President</span>
                <span>Vice President</span>
            </div>
            @endif
        </div>
    </div>

    <div class="footer-note">
        *Note: Attach invoices, Official receipts (OR) and other supporting documents.
    </div>
</div>

<script>
    window.onload = function() { setTimeout(function() { window.print(); }, 500); };
</script>
</body>
</html>
