<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Journal Voucher — {{ $voucher->jv_number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }

        .jv-container { max-width: 750px; margin: 0 auto; border: 2px solid #000; }

        .jv-header { text-align: center; padding: 15px; border-bottom: 2px solid #000; }
        .company-name { font-size: 16px; font-weight: bold; letter-spacing: 2px; }
        .company-address { font-size: 8px; color: #333; line-height: 1.4; }
        .jv-title { font-size: 14px; font-weight: bold; margin-top: 8px; letter-spacing: 1px; text-decoration: underline; }

        .jv-info { padding: 10px 15px; border-bottom: 1px solid #000; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .info-label { font-weight: bold; font-size: 10px; min-width: 80px; }
        .info-value { font-size: 11px; border-bottom: 1px solid #ccc; flex: 1; margin-left: 5px; }

        .jv-table { width: 100%; border-collapse: collapse; }
        .jv-table th { background: #f0f0f0; border: 1px solid #000; padding: 5px 8px; font-size: 10px; text-transform: uppercase; }
        .jv-table td { border: 1px solid #000; padding: 4px 8px; font-size: 10px; }
        .jv-table .text-right { text-align: right; }
        .jv-table .total-row { background: #e8e8e8; font-weight: bold; }

        .jv-signatures { display: flex; justify-content: space-between; padding: 20px 30px 15px; border-top: 1px solid #000; }
        .sig-block { text-align: center; width: 30%; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 4px; font-size: 10px; font-weight: bold; }
        .sig-name { font-size: 9px; margin-top: 2px; }

        .jv-footer { border-top: 1px solid #000; padding: 5px 15px; font-size: 7px; color: #666; text-align: center; }

        .print-controls { text-align: center; padding: 20px; background: #f8f8f8; margin-bottom: 20px; }
        .print-controls button { padding: 10px 25px; font-size: 14px; cursor: pointer; border: none; border-radius: 5px; margin: 0 5px; }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #6b7280; color: #fff; }

        @media print {
            .print-controls { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        <button class="btn-back" onclick="window.history.back()">Back</button>
    </div>

    <div class="jv-container">
        <div class="jv-header">
            <div class="company-name">MEATPLUS TRADING CORP.</div>
            <div class="company-address">
                Unit 1207, 12/F Victoria Bldg. 429 U.N. Ave. Zone 72 Barangay 666, 1000<br>
                Ermita, NCR City of Manila, First District, Philippines
            </div>
            <div class="jv-title">JOURNAL VOUCHER</div>
        </div>

        <div class="jv-info">
            <div class="info-row">
                <div style="display:flex;flex:1"><span class="info-label">JV No.:</span><span class="info-value">{{ $voucher->jv_number }}</span></div>
                <div style="display:flex;flex:1"><span class="info-label">Date:</span><span class="info-value">{{ $voucher->jv_date->format('F d, Y') }}</span></div>
            </div>
            <div class="info-row">
                <div style="display:flex;flex:1"><span class="info-label">Type:</span><span class="info-value">{{ $voucher->formatted_type }}</span></div>
                <div style="display:flex;flex:1"><span class="info-label">Reference:</span><span class="info-value">{{ $voucher->reference_no ?? '' }}</span></div>
            </div>
            <div class="info-row">
                <div style="display:flex;flex:1"><span class="info-label">Description:</span><span class="info-value">{{ $voucher->description }}</span></div>
            </div>
        </div>

        <table class="jv-table">
            <thead>
                <tr>
                    <th style="text-align:left">Account Code</th>
                    <th style="text-align:left">Account Name</th>
                    <th style="text-align:left">Particulars</th>
                    <th style="text-align:right; width:110px">Debit</th>
                    <th style="text-align:right; width:110px">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucher->lines as $line)
                <tr>
                    <td>{{ $line->account_code }}</td>
                    <td>{{ $line->account_name }}</td>
                    <td>{{ $line->line_description }}</td>
                    <td class="text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                    <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                </tr>
                @endforeach
                @for($i = $voucher->lines->count(); $i < 8; $i++)
                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
                @endfor
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right; padding-right:10px;">TOTAL</td>
                    <td class="text-right">{{ number_format($voucher->total_debit, 2) }}</td>
                    <td class="text-right">{{ number_format($voucher->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($voucher->remarks)
        <div style="padding: 6px 15px; border-top: 1px solid #000; font-size: 9px;">
            <strong>Remarks:</strong> {{ $voucher->remarks }}
        </div>
        @endif

        <div class="jv-signatures">
            <div class="sig-block">
                <div class="sig-line">Prepared By</div>
                <div class="sig-name">{{ $voucher->prepared_by ?? '' }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">Checked By</div>
                <div class="sig-name">{{ $voucher->checked_by ?? '' }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">Approved By</div>
                <div class="sig-name">{{ $voucher->approved_by ?? '' }}</div>
            </div>
        </div>

        <div class="jv-footer">
            Printed: {{ now()->format('m/d/Y h:i A') }} | Status: {{ $voucher->status }}
        </div>
    </div>
</body>
</html>
