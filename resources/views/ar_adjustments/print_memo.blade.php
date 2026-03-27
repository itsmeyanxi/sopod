<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $memoType === 'debit' ? 'Debit' : 'Credit' }} Memo No. {{ $memoNumber }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
        }

        .memo-container {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 0;
        }

        /* Header */
        .memo-header {
            text-align: center;
            padding: 12px 15px 8px;
            border-bottom: 1px solid #000;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 2px;
        }

        .company-address {
            font-size: 8px;
            line-height: 1.4;
            color: #333;
        }

        .company-vat {
            font-size: 9px;
            margin-top: 3px;
            font-weight: bold;
        }

        /* Memo Type & Number */
        .memo-title-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 8px 15px;
            border-bottom: 1px solid #000;
        }

        .memo-type-label {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .memo-number-box {
            margin-left: 15px;
            border: 1px solid #c00;
            padding: 3px 12px;
            color: #c00;
            font-weight: bold;
            font-size: 14px;
            letter-spacing: 1px;
        }

        /* Info Section */
        .memo-info {
            padding: 10px 15px;
            border-bottom: 1px solid #000;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .info-left {
            flex: 1;
        }

        .info-right {
            flex: 1;
            text-align: right;
        }

        .info-label {
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            min-width: 40px;
        }

        .info-value {
            font-size: 11px;
            border-bottom: 1px solid #999;
            display: inline-block;
            min-width: 150px;
            padding-bottom: 1px;
        }

        .info-value-right {
            font-size: 11px;
            border-bottom: 1px solid #999;
            display: inline-block;
            min-width: 120px;
            padding-bottom: 1px;
        }

        /* Adjustment Note */
        .adjustment-note {
            padding: 6px 15px;
            font-size: 10px;
            font-style: italic;
            border-bottom: 1px solid #000;
        }

        /* Table */
        .memo-table {
            width: 100%;
            border-collapse: collapse;
        }

        .memo-table th {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
            padding: 6px 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .memo-table th.amount-col {
            text-align: right;
            width: 150px;
        }

        .memo-table td {
            padding: 8px 10px;
            vertical-align: top;
            font-size: 11px;
            min-height: 80px;
        }

        .memo-table td.amount-col {
            text-align: right;
            font-weight: bold;
            font-size: 12px;
            border-left: 1px solid #000;
        }

        .description-cell {
            min-height: 100px;
            border-right: 1px solid #000;
        }

        /* Total Row */
        .total-row {
            border-top: 2px solid #000;
        }

        .total-row td {
            padding: 6px 10px;
            font-weight: bold;
            font-size: 12px;
        }

        /* Signatures */
        .memo-signatures {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px 10px;
            border-top: 1px solid #000;
        }

        .signature-block {
            text-align: center;
            width: 45%;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 35px;
            padding-top: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .signature-name {
            font-size: 10px;
            margin-top: 2px;
        }

        /* Footer */
        .memo-footer {
            border-top: 1px solid #000;
            padding: 5px 15px;
            font-size: 7px;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        .memo-footer-right {
            font-weight: bold;
            font-size: 7px;
            color: #000;
        }

        /* Print Controls */
        .print-controls {
            text-align: center;
            padding: 20px;
            background: #f8f8f8;
            margin-bottom: 20px;
        }

        .print-controls button {
            padding: 10px 25px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            margin: 0 5px;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-back {
            background: #6b7280;
            color: #fff;
        }

        .btn-back:hover {
            background: #4b5563;
        }

        @media print {
            .print-controls {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .memo-container {
                border: 2px solid #000;
            }
        }
    </style>
</head>
<body>

    <!-- Print Controls (hidden when printing) -->
    <div class="print-controls">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print {{ $memoType === 'debit' ? 'Debit' : 'Credit' }} Memo
        </button>
        <button class="btn-back" onclick="window.history.back()">
            Back
        </button>
    </div>

    <div class="memo-container">
        <!-- Company Header -->
        <div class="memo-header">
            <div class="company-name">MEATPLUS TRADING CORP.</div>
            <div class="company-address">
                Unit 1207, 12/F Victoria Bldg. 429 U.N. Ave. Zone 72 Barangay 666, 1000<br>
                Ermita, NCR City of Manila, First District, Philippines<br>
                Telephone No. 8244-4619 Telefax No. 8244-4620
            </div>
            <div class="company-vat">VAT Reg TIN 006-873-989-00000</div>
        </div>

        <!-- Memo Type & Number Row -->
        <div class="memo-title-row">
            <span class="memo-type-label">{{ $memoType === 'debit' ? 'Debit' : 'Credit' }} Memo No.:</span>
            <span class="memo-number-box">{{ $memoNumber }}</span>
        </div>

        <!-- Customer Information -->
        <div class="memo-info">
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">PHP</span>
                    <span class="info-value">{{ number_format(abs($adjustment->amount), 2) }}</span>
                </div>
                <div class="info-right">
                    <span class="info-label">TIN:</span>
                    <span class="info-value-right">{{ $customer->tin_no ?? '' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $adjustment->customer_name }}</span>
                </div>
                <div class="info-right">
                    <span class="info-label">Business Style:</span>
                    <span class="info-value-right">{{ $customer->business_style ?? '' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Code:</span>
                    <span class="info-value">{{ $formattedCustomerCode }}</span>
                </div>
            </div>
        </div>

        <!-- Adjustment Note -->
        <div class="adjustment-note">
            We made the following adjustment (s) to your account:
        </div>

        <!-- Description & Amount Table -->
        <table class="memo-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="description-cell">
                        <div style="margin-bottom: 5px;">
                            <strong>Ref #:</strong> {{ $adjustment->reference_number }}
                        </div>
                        <div style="margin-bottom: 5px;">
                            <strong>Type:</strong> {{ $adjustment->formatted_type }}
                        </div>
                        @if($adjustment->dr_no)
                        <div style="margin-bottom: 5px;">
                            <strong>DR No.:</strong> {{ $adjustment->dr_no }}
                        </div>
                        @endif
                        @if($adjustment->invoice_number)
                        <div style="margin-bottom: 5px;">
                            <strong>Invoice No.:</strong> {{ $adjustment->invoice_number }}
                        </div>
                        @endif
                        @if($adjustment->gl_account)
                        <div style="margin-bottom: 5px;">
                            <strong>GL Account:</strong> {{ $adjustment->gl_account }}
                        </div>
                        @endif
                        @if($adjustment->remarks)
                        <div style="margin-top: 10px;">
                            <strong>Remarks:</strong> {{ $adjustment->remarks }}
                        </div>
                        @endif
                    </td>
                    <td class="amount-col">
                        ₱{{ number_format(abs($adjustment->amount), 2) }}
                    </td>
                </tr>
                <!-- Empty rows for form look -->
                <tr>
                    <td class="description-cell" style="min-height: 30px;">&nbsp;</td>
                    <td class="amount-col">&nbsp;</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td style="text-align: right; padding-right: 15px;">TOTAL</td>
                    <td class="amount-col" style="border-left: 1px solid #000;">₱{{ number_format(abs($adjustment->amount), 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="memo-signatures">
            <div class="signature-block">
                <div class="signature-line">Accounting Team Lead</div>
            </div>
            <div class="signature-block">
                <div>Approved by:</div>
                <div class="signature-line">Accounting Manager</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="memo-footer">
            <div>
                Date Printed: {{ now()->format('m/d/Y h:i A') }} |
                Prepared by: {{ $adjustment->created_by ?? Auth::user()->name ?? '' }} |
                Transaction Date: {{ $adjustment->transaction_date->format('m/d/Y') }}
            </div>
            <div class="memo-footer-right">
                *THIS DOCUMENT IS NOT VALID FOR CLAIMING INPUT TAX*
            </div>
        </div>
    </div>

</body>
</html>
