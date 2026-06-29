<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV {{ $checkVoucher->cv_no ?? '' }} - Check Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 10mm; }
        @media print {
            body { background: #fff; }
            .page { padding: 8mm; margin: 0; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 8mm; }
        }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .logo-section { display: flex; align-items: center; gap: 10px; }
        .logo { width: 50px; height: 50px; object-fit: contain; }
        .company-info { font-size: 10px; }
        .company-name { font-weight: bold; font-size: 12px; }
        .company-address { font-size: 8px; color: #333; line-height: 1.3; }
        .doc-title { font-size: 16px; font-weight: bold; text-align: center; flex: 1; }
        .cv-info { text-align: right; font-size: 10px; }
        .cv-label { font-weight: bold; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #e5e5e5; font-weight: bold; font-size: 8px; text-align: center; }
        .label { font-weight: bold; width: 120px; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .section-header { font-weight: bold; margin-top: 10px; margin-bottom: 4px; font-size: 10px; }
        .detail-box { border: 1px solid #000; padding: 8px; margin-bottom: 10px; font-size: 10px; }
        .detail-row { display: flex; margin-bottom: 4px; }
        .detail-label { font-weight: bold; width: 120px; }
        .detail-value { flex: 1; }

        .payment-details-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 8px; }
        .payment-field { display: flex; flex-direction: column; }
        .payment-field-label { font-weight: bold; font-size: 9px; margin-bottom: 2px; }
        .payment-field-value { border: 1px solid #000; padding: 4px; font-size: 10px; min-height: 20px; }

        .e-signature { font-size: 8px; color: #333; margin-top: 4px; font-style: normal; font-weight: 500; }
        .e-signature-detail { font-size: 7px; color: #666; margin-top: 2px; font-style: italic; }

        .print-controls { text-align: center; margin-bottom: 15px; padding: 10px; }
        .print-btn, .back-btn { padding: 10px 25px; font-size: 13px; cursor: pointer; border-radius: 4px; margin: 0 5px; border: none; }
        .print-btn { background: #6b21a8; color: white; }
        .back-btn { background: #4b5563; color: white; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <a href="{{ route('check_vouchers.show', $checkVoucher->id) }}" class="back-btn">Back to CV</a>
        <button onclick="window.print()" class="print-btn">Print Check Voucher</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <img src="{{ asset('images/sopod-logo.PNG') }}" class="logo" alt="Logo">
                <div class="company-info">
                    <div class="company-name">{{ $checkVoucher->company ?? 'Meatplus Trading Corp' }}</div>
                    <div class="company-address">
                        12F Victoria Building, United Nations Avenue, Ermita, Manila, Philippines, 1004<br>
                        VAT Reg. TIN 006-873-989-000
                    </div>
                </div>
            </div>
            <div class="doc-title">CHECK VOUCHER</div>
            <div class="cv-info">
                <div><span class="cv-label">CV No.:</span> {{ $checkVoucher->cv_no ?? '' }}</div>
                <div><span class="cv-label">Date:</span> {{ $checkVoucher->cv_date ? $checkVoucher->cv_date->format('m/d/Y') : '' }}</div>
            </div>
        </div>

        <!-- Supplier Details -->
        <div class="detail-box">
            <div class="detail-row">
                <div class="detail-label">Supplier Code:</div>
                <div class="detail-value">{{ $checkVoucher->vendor_code ?? '' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Supplier Name:</div>
                <div class="detail-value">{{ $checkVoucher->supplier_name ?? '' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Supplier Address:</div>
                <div class="detail-value">{{ $checkVoucher->supplier_address ?? '' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Supplier TIN:</div>
                <div class="detail-value">{{ $checkVoucher->vendor_tin ?? '' }}</div>
            </div>
        </div>

        <!-- Payment Details -->
        <!-- Additional Check Details -->
        <div class="detail-box">
            <div class="detail-row">
                <div class="detail-label">Check #:</div>
                <div class="detail-value">{{ $checkVoucher->check_no ?? '' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Bank:</div>
                <div class="detail-value">{{ $checkVoucher->bank ?? '' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Branch:</div>
                <div class="detail-value">{{ $checkVoucher->branch ?? '' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Check Amount:</div>
                <div class="detail-value">{{ $checkVoucher->check_amount ? number_format($checkVoucher->check_amount, 2) : '' }}</div>
            </div>
        </div>
        <div class="section-header">PAYMENT DETAILS:</div>
        <div class="payment-details-row">
            <div class="payment-field">
                <div class="payment-field-label">Date</div>
                <div class="payment-field-value">{{ $checkVoucher->check_date ? $checkVoucher->check_date->format('m/d/Y') : '' }}</div>
            </div>
            <div class="payment-field">
                <div class="payment-field-label">Type</div>
                <div class="payment-field-value">{{ $checkVoucher->payment_type ?? '' }}</div>
            </div>
            <div class="payment-field">
                <div class="payment-field-label">Reference No.</div>
                <div class="payment-field-value">{{ $checkVoucher->reference_no ?? '' }}</div>
            </div>
            <div class="payment-field">
                <div class="payment-field-label">APV No.</div>
                <div class="payment-field-value">{{ $checkVoucher->apv_no ?? '' }}</div>
            </div>
            <div class="payment-field">
                <div class="payment-field-label">Paid Amount</div>
                <div class="payment-field-value">{{ $checkVoucher->paid_amount ? number_format($checkVoucher->paid_amount, 2) : '' }}</div>
            </div>
        </div>

        <!-- Particulars Section -->
        <div class="section-header">PARTICULARS:</div>
        <div style="border: 1px solid #000; padding: 8px; margin-bottom: 10px; min-height: 60px; font-size: 10px;">
            {{ $checkVoucher->particulars ?? '' }}
        </div>
<!-- Journal Entry -->
@if(is_array($checkVoucher->journal_entries) && count($checkVoucher->journal_entries) > 0)
<div class="section-header" style="margin-top: 10px;">JOURNAL ENTRY:</div>
<table style="margin-bottom: 10px;">
    <thead>
        <tr>
            <th style="width: 5%;">No.</th>
            <th style="width: 30%;">Account Code</th>
            <th style="width: 40%;">Account Name</th>
            <th style="width: 12.5%;">Debit (PHP)</th>
            <th style="width: 12.5%;">Credit (PHP)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($checkVoucher->journal_entries as $index => $entry)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td class="text-left">{{ $entry['account_code'] ?? '' }}</td>
            <td class="text-left">{{ $entry['account_name'] ?? '' }}</td>
            <td class="text-right">{{ isset($entry['debit']) && $entry['debit'] ? number_format($entry['debit'], 2) : '' }}</td>
            <td class="text-right">{{ isset($entry['credit']) && $entry['credit'] ? number_format($entry['credit'], 2) : '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
        <!-- Remarks -->
        @if($checkVoucher->remarks)
            <div class="detail-box">
                <strong>Remarks:</strong><br>
                {{ $checkVoucher->remarks }}
            </div>
        @endif

        <!-- Signatures -->
        <div style="margin-top: 20px;">
            <div class="section-header">APPROVAL SIGNATURES:</div>
            <table>
                <tr>
                    <th style="width: 33.33%;">Prepared by:</th>
                    <th style="width: 33.33%;">Reviewed by:</th>
                    <th style="width: 33.33%;">Approved by:</th>
                </tr>
                <tr>
                    <td style="height: 50px; padding-bottom: 4px; vertical-align: bottom;">
                        <div style="border-top: 1px solid #000; font-size: 8px;">&nbsp;</div>
                    </td>
                    <td style="height: 50px; padding-bottom: 4px; vertical-align: bottom;">
                        <div style="border-top: 1px solid #000; font-size: 8px;">&nbsp;</div>
                    </td>
                    <td style="height: 50px; padding-bottom: 4px; vertical-align: bottom;">
                        <div style="border-top: 1px solid #000; font-size: 8px;">{{ $checkVoucher->approvalUser->name ?? ($checkVoucher->approved_by ?? '') }}</div>
                        @if($checkVoucher->approvalUser && $checkVoucher->approval_date)
                            <div class="e-signature">@include('partials.esignature', ['signer' => $checkVoucher->approvalUser])</div>
                            <div class="e-signature-detail">Date/Time: {{ $checkVoucher->approval_date->format('d F Y | H:i') }} PHT (UTC+8)</div>
                            @if($checkVoucher->approved_latitude && $checkVoucher->approved_longitude)
                                <div class="e-signature-detail">Coords: {{ $checkVoucher->approved_latitude }}, {{ $checkVoucher->approved_longitude }}@if($checkVoucher->approved_location) ({{ $checkVoucher->approved_location }})@endif</div>
                            @endif
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-center" style="font-size: 8px; font-style: italic; background: #f0f0f0;">Creator</td>
                    <td class="text-center" style="font-size: 8px; font-style: italic; background: #f0f0f0;">Accounting Manager</td>
                    <td class="text-center" style="font-size: 8px; font-style: italic; background: #f0f0f0;">ODM / FDM</td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 10px; font-size: 8px; color: #666; border-top: 1px solid #000; padding-top: 4px;">
            <p>This document is not valid for claiming Input Tax</p>
            <p>Print Date/Time: {{ now()->format('m/d/Y h:i:s A') }}</p>
        </div>
    </div>
</body>
</html>
