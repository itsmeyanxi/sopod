<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APV {{ $apv->apv_no ?? '' }} - Accounts Payable Voucher</title>
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

        .header-top {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header-title-section { display: flex; flex-direction: column; justify-content: flex-start; }
        .main-title { font-size: 18px; font-weight: bold; color: #000; margin-bottom: 5px; }
        .company-name { font-size: 14px; font-weight: bold; color: #000; line-height: 1.3; }
        .logo-section { text-align: right; }
        .logo { width: 60px; height: 60px; object-fit: contain; margin-bottom: 8px; }
        .header-info-right { font-size: 10px; text-align: right; line-height: 1.6; }
        .header-info-right strong { font-weight: bold; }

        .section-title { font-weight: bold; font-size: 11px; margin-top: 12px; margin-bottom: 6px; color: #000; border-bottom: 1px solid #999; padding-bottom: 4px; }

        table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; page-break-inside: avoid; }
        th { background: #f0f0f0; color: #000; font-weight: bold; padding: 6px 8px; text-align: left; border: 1px solid #999; }
        td { padding: 6px 8px; border: 1px solid #999; }
        .amount { text-align: right; font-weight: 500; }
        tr { page-break-inside: avoid; }

        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px 20px; margin: 8px 0 15px 0; font-size: 10px; }
        .info-item strong { font-weight: bold; }

        /* Signatures Section */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 30px; font-size: 10px; }
        .sig-table th { background: #f0f0f0; border: 1px solid #999; padding: 6px 8px; text-align: center; font-weight: bold; }
        .sig-table td { border: 1px solid #999; padding: 8px; text-align: center; vertical-align: bottom; height: 70px; }
        .sig-table .sig-role { background: #f0f0f0; border: 1px solid #999; padding: 4px 8px; text-align: center; font-size: 9px; font-style: italic; }
        .sig-name { font-size: 10px; font-weight: 600; }
        .e-signature { font-size: 8px; color: #333; margin-top: 4px; font-weight: 500; }
        .e-signature-detail { font-size: 7px; color: #666; margin-top: 2px; font-style: italic; }

        .footer { text-align: center; margin-top: 15px; font-size: 8px; color: #666; border-top: 1px solid #ccc; padding-top: 8px; }
        .footer p { margin: 2px 0; }

        .print-controls { text-align: center; margin-bottom: 15px; padding: 10px; }
        .print-btn, .back-btn { padding: 10px 25px; font-size: 13px; cursor: pointer; border-radius: 4px; margin: 0 5px; border: none; font-weight: 500; }
        .print-btn { background: #6b21a8; color: white; }
        .print-btn:hover { background: #581c87; }
        .back-btn { background: #4b5563; color: white; text-decoration: none; display: inline-block; }
        .back-btn:hover { background: #364152; }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <a href="{{ route('accounts_payable_invoices.show', $apv->id) }}" class="back-btn">Back to APV</a>
        <button type="button" onclick="setTimeout(() => { window.print(); }, 100);" class="print-btn">Print APV</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header-top">
            <div class="header-title-section">
                <div class="main-title">ACCOUNTS PAYABLE VOUCHER</div>
                <div class="company-name">Meatplus Trading Corp</div>
            </div>
            <div class="logo-section">
                <img src="{{ asset('images/sopod-logo.PNG') }}" class="logo" alt="Logo">
                <div class="header-info-right">
                    <div><strong>APV Date:</strong> {{ $apv->apv_date ? $apv->apv_date->format('F j, Y') : '' }}</div>
                    <div><strong>Doc Date:</strong> {{ $apv->document_date ? $apv->document_date->format('F j, Y') : '' }}</div>
                    <div><strong>APV #:</strong> {{ $apv->apv_no ?? '' }}</div>
                    @if($apv->reference_no)
                        <div><strong>Ref #:</strong> {{ $apv->reference_no }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Vendor Information -->
        <div class="section-title">VENDOR INFORMATION</div>
        <div class="info-grid">
            @if($apv->vendor_code)
                <div class="info-item"><strong>Vendor Code:</strong> {{ $apv->vendor_code }}</div>
            @endif
            <div class="info-item"><strong>Vendor Name:</strong> {{ $apv->vendor_name }}</div>
            @if($apv->vendor_address)
                <div class="info-item"><strong>Address:</strong> {{ $apv->vendor_address }}</div>
            @endif
            @if($apv->vendor_tin)
                <div class="info-item"><strong>TIN:</strong> {{ $apv->vendor_tin }}</div>
            @endif
            <div class="info-item"><strong>Currency:</strong> {{ $apv->currency }}
                @if($apv->forex_rate && $apv->currency !== 'PHP')
                    (Rate: {{ number_format($apv->forex_rate, 4) }})
                @endif
            </div>
            @if($apv->payment_terms)
                <div class="info-item"><strong>Payment Terms:</strong> {{ $apv->payment_terms }}</div>
            @endif
        </div>

        <!-- Particulars -->
        <div class="section-title">PARTICULARS</div>
        <div style="border: 1px solid #999; padding: 8px; min-height: 40px; font-size: 10px; margin-bottom: 12px;">
            {{ $apv->particulars ?? '' }}
        </div>

        <!-- Amount Breakdown -->
        <div class="section-title">AMOUNT BREAKDOWN</div>
        <table>
            <tbody>
                <tr>
                    <td style="text-align: right; font-weight: bold;">Total Amount:</td>
                    <td class="amount" style="width: 30%;">{{ $apv->currency }} {{ number_format($apv->total, 2) }}</td>
                </tr>
                @if($apv->payment_type === 'downpayment' && $apv->downpayment_amount)
                <tr>
                    <td style="text-align: right; font-weight: bold;">Downpayment Amount:</td>
                    <td class="amount">{{ $apv->currency }} {{ number_format($apv->downpayment_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td style="text-align: right; font-weight: bold;">Total Before VAT:</td>
                    <td class="amount">{{ $apv->currency }} {{ number_format($apv->total_before_vat, 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-weight: bold;">VAT Amount:</td>
                    <td class="amount">{{ $apv->currency }} {{ number_format($apv->vat_amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-weight: bold;">Total After VAT:</td>
                    <td class="amount">{{ $apv->currency }} {{ number_format($apv->total_after_vat, 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-weight: bold;">Withholding Tax:</td>
                    <td class="amount">({{ $apv->currency }} {{ number_format($apv->w_tax_amount, 2) }})</td>
                </tr>
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <td style="text-align: right; font-weight: bold; font-size: 12px;">GRAND TOTAL:</td>
                    <td class="amount" style="font-size: 12px;">{{ $apv->currency }} {{ number_format($apv->grand_total, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($apv->remarks)
        <div class="section-title">REMARKS</div>
        <div style="border: 1px solid #999; padding: 8px; min-height: 25px; font-size: 10px; margin-bottom: 12px;">
            {{ $apv->remarks }}
        </div>
        @endif

        <!-- Signature Table -->
        <table class="sig-table">
            <thead>
                <tr>
                    <th style="width: 33.33%;">Prepared By:</th>
                    <th style="width: 33.33%;">Reviewed By:</th>
                    <th style="width: 33.33%;">Approved By:</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="sig-name">{{ $apv->creator->name ?? '' }}</div>
                        @if($apv->creator && $apv->created_at)
                            <div class="e-signature">@include('partials.esignature', ['signer' => $apv->creator])</div>
                            <div class="e-signature-detail">Date/Time: {{ $apv->created_at->format('d F Y | H:i') }} PHT (UTC+8)</div>
                        @endif
                    </td>
                    <td>
                        <div class="sig-name">{{ $apv->departmentHeadApprover->name ?? '' }}</div>
                        @if($apv->departmentHeadApprover && $apv->department_head_approved_at)
                            <div class="e-signature">@include('partials.esignature', ['signer' => $apv->departmentHeadApprover])</div>
                            <div class="e-signature-detail">Date/Time: {{ $apv->department_head_approved_at->format('d F Y | H:i') }} PHT (UTC+8)</div>
                            @if($apv->department_head_approved_latitude && $apv->department_head_approved_longitude)
                                <div class="e-signature-detail">Coords: {{ $apv->department_head_approved_latitude }}, {{ $apv->department_head_approved_longitude }}@if($apv->department_head_approved_location) ({{ $apv->department_head_approved_location }})@endif</div>
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="sig-name">{{ $apv->approver->name ?? '' }}</div>
                        @if($apv->approver && $apv->approved_at)
                            <div class="e-signature">@include('partials.esignature', ['signer' => $apv->approver])</div>
                            <div class="e-signature-detail">Date/Time: {{ $apv->approved_at->format('d F Y | H:i') }} PHT (UTC+8)</div>
                            @if($apv->approved_latitude && $apv->approved_longitude)
                                <div class="e-signature-detail">Coords: {{ $apv->approved_latitude }}, {{ $apv->approved_longitude }}@if($apv->approved_location) ({{ $apv->approved_location }})@endif</div>
                            @endif
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="sig-role">Creator</td>
                    <td class="sig-role">Department Head</td>
                    <td class="sig-role">Accounting Manager</td>
                </tr>
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Status: {{ strtoupper($apv->status) }}</p>
            <p>Print Date/Time: {{ now()->format('m/d/Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>