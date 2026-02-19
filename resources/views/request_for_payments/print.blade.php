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
            color: #000;
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
        <div class="header-top">
            <div class="header-title-section">
                <div class="main-title">REQUEST FOR PAYMENT</div>
                <div class="company-name">Pacific Magalang Agriventures Inc.</div>
            </div>
            <div class="logo-section">
                <img src="{{ asset('images/sopod-logo.png') }}" class="logo" alt="Logo">
                <div class="header-info-right">
                    <div><strong>Date:</strong> {{ $rfp->date ? $rfp->date->format('F j, Y') : '' }}</div>
                    <div><strong>Due Date:</strong> {{ $rfp->due_date ? $rfp->due_date->format('F j, Y') : '' }}</div>
                    <div><strong>RFP #:</strong> {{ $rfp->rfp_no ?? '' }}</div>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="payment-methods">
            @php
                $methods = is_array($rfp->payment_methods) ? $rfp->payment_methods : json_decode($rfp->payment_methods ?? '[]', true);
            @endphp
            <div class="payment-grid">
                <div class="payment-item">
                    <div class="checkbox {{ in_array('managers_check', $methods) ? 'checked' : '' }}"></div>
                    <span>MANAGER CHECK</span>
                </div>
                <div class="payment-item">
                    <div class="checkbox {{ in_array('fund_transfer', $methods) ? 'checked' : '' }}"></div>
                    <span>FUND TRANSFER</span>
                </div>
                <div class="payment-item">
                    <div class="checkbox {{ in_array('regular_check', $methods) ? 'checked' : '' }}"></div>
                    <span>REGULAR CHECK</span>
                </div>
                <div class="payment-item">
                    <div class="checkbox {{ in_array('pdc', $methods) ? 'checked' : '' }}"></div>
                    <span>PDC</span>
                </div>
                <div class="payment-item">
                    <div class="checkbox {{ in_array('wire_transfer', $methods) ? 'checked' : '' }}"></div>
                    <span>WIRE TRANSFER</span>
                </div>
                <div class="payment-item">
                    <div class="checkbox {{ in_array('auto_debit', $methods) ? 'checked' : '' }}"></div>
                    <span>AUTO DEBIT</span>
                </div>
                <div class="payment-item">
                    <div class="checkbox {{ in_array('cash', $methods) ? 'checked' : '' }}"></div>
                    <span>CASH</span>
                </div>
                <div class="payment-item">
                    <div class="checkbox"></div>
                    <span>OTHER</span>
                </div>
            </div>
        </div>

        <!-- Payee -->
        <div class="section-title">Payee</div>
        <div class="payee-box">{{ $rfp->payee ?? '' }}</div>

        <!-- Particulars Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 75%;">Particulars</th>
                    <th style="width: 25%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $rfp->particulars ?? '' }}</td>
                    <td class="amount">{{ $rfp->amount ? number_format($rfp->amount, 2) : '' }}</td>
                </tr>
            </tbody>
        </table>

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

        <!-- Remarks -->
        <div class="section-title">Remarks:</div>
        <div class="remarks-box">&nbsp;</div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-label">Prepared By:</div>
                <div class="sig-line"></div>
                <div class="sig-name"></div>
            </div>
            <div class="sig-block">
                <div class="sig-label">Checked By:</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $rfp->checked_by ?? '' }}</div>
                <div class="e-signature">{{ $rfp->checked_by ? $rfp->checked_by . ' is acknowledged as e-signature' : '' }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-label">Checked By:</div>
                <div class="sig-line"></div>
                <div class="sig-name"></div>
            </div>
        </div>

        <!-- Finance Section -->
        <div class="finance-section">
            <div class="finance-title">-FOR FINANCE USE ONLY-</div>
            <div class="approvals">
                <div class="approval-block">
                    <div class="approval-label">Approved By:</div>
                    <div class="approval-line"></div>
                    <div class="approval-name"></div>
                    <div class="approval-e-signature">&nbsp;</div>
                </div>
                <div class="approval-block">
                    <div class="approval-label">Approved By:</div>
                    <div class="approval-line"></div>
                    <div class="approval-name"></div>
                    <div class="approval-e-signature">&nbsp;</div>
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
