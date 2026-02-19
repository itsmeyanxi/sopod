<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APV {{ $apv->apv_no ?? '' }} - Accounts Payable Invoice</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; background: #fff; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 10mm; }
        @media print {
            body { background: #fff; }
            .page { padding: 8mm; margin: 0; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 8mm; }
        }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 6px; }
        .logo-section { display: flex; align-items: center; gap: 8px; }
        .logo { width: 45px; height: 45px; object-fit: contain; }
        .company-info { font-size: 9px; }
        .company-name { font-weight: bold; font-size: 11px; }
        .company-address { font-size: 8px; color: #333; line-height: 1.2; }
        .doc-title { font-size: 13px; font-weight: bold; text-align: center; flex: 1; }
        .apv-header { text-align: right; font-size: 9px; line-height: 1.4; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 3px 4px; }
        th { background: #b91c1c; color: #fff; font-weight: bold; font-size: 8px; text-align: center; }
        .label { font-weight: bold; font-size: 8px; background: #f5f5f5; width: 100px; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-section { margin: 8px 0; }
        .totals-table { width: 55%; margin-left: auto; border-collapse: collapse; font-size: 9px; }
        .totals-table td { border: 1px solid #000; padding: 3px 6px; }
        .totals-table .tlabel { text-align: right; }
        .totals-table .tvalue { text-align: right; font-weight: bold; }
        .totals-table .grand { background: #f0f0f0; font-weight: bold; }

        .section-title { font-weight: bold; font-size: 9px; margin: 8px 0 4px 0; }
        .print-controls { text-align: center; margin-bottom: 15px; padding: 10px; }
        .print-btn, .back-btn { padding: 10px 25px; font-size: 13px; cursor: pointer; border-radius: 4px; margin: 0 5px; border: none; }
        .print-btn { background: #6b21a8; color: white; }
        .back-btn { background: #4b5563; color: white; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <a href="{{ route('accounts_payable_invoices.show', $apv->id ?? '#') }}" class="back-btn">Back to APV</a>
        <button onclick="window.print()" class="print-btn">Print APV</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <img src="{{ asset('images/sopod-logo.png') }}" class="logo" alt="Logo">
                <div class="company-info">
                    <div class="company-name">{{ $apv->company ?? 'MEATPLUS' }}</div>
                    <div class="company-address">
                        12F Victoria Building, United Nations Avenue, Ermita, Manila, Philippines, 1004<br>
                        VAT Reg. TIN 006-873-989-000
                    </div>
                </div>
            </div>
            <div class="doc-title">ACCOUNTS PAYABLE INVOICE</div>
            <div class="apv-header">
                <div><strong>Date:</strong> {{ $apv->apv_date ? $apv->apv_date->format('F j, Y') : '' }}</div>
                <div><strong>APV NO.:</strong> {{ $apv->apv_no ?? '' }}</div>
                <div><strong>Reference:</strong> {{ $apv->reference_no ?? '' }}</div>
            </div>
        </div>

        <!-- Top Details -->
        <table style="margin-bottom: 8px;">
            <tr>
                <td class="label">VENDOR NAME</td>
                <td style="width: 40%;">{{ $apv->vendor_name ?? '' }}</td>
                <td class="label">TERMS</td>
                <td style="width: 20%;">{{ $apv->terms ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">VENDOR ADDRESS</td>
                <td colspan="3">{{ $apv->vendor_address ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">DUE DATE</td>
                <td>{{ $apv->due_date ? $apv->due_date->format('m/d/Y') : '' }}</td>
                <td class="label">PO NO</td>
                <td>{{ $apv->po_no ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">CURRENCY</td>
                <td>{{ $apv->currency ?? 'PHP' }}</td>
                <td class="label">FOREX RATE</td>
                <td>{{ $apv->forex_rate ?? '1' }}</td>
            </tr>
        </table>

        <!-- Items Table -->
        <table style="margin-bottom: 8px;">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 20%;">Item Code</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;">Account Code</th>
                    <th style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($apv->items ?? [] as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-left">{{ $item->item_code ?? '' }}</td>
                        <td class="text-left">{{ $item->description ?? '' }}</td>
                        <td class="text-left">{{ $item->account_code ?? '' }}</td>
                        <td class="text-right">{{ $item->amount ? number_format($item->amount, 2) : '' }}</td>
                    </tr>
                @empty
                    @for($i = 0; $i < 5; $i++)
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    @endfor
                @endforelse
            </tbody>
        </table>

        <!-- Totals -->
        @php
            $subtotal = isset($apv->items) ? $apv->items->sum('amount') : 0;
            $withholding_tax = $apv->withholding_tax ?? 0;
            $total = $subtotal - $withholding_tax;
        @endphp
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="tlabel">Total PHP:</td>
                    <td class="tvalue">{{ number_format($subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="tlabel">Withholding Tax:</td>
                    <td class="tvalue">{{ number_format($withholding_tax, 2) }}</td>
                </tr>
                <tr class="grand">
                    <td class="tlabel">Grand Total:</td>
                    <td class="tvalue">{{ number_format($total, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Signatures -->
        <div style="margin-top: 20px;">
            <div class="section-title">APPROVAL SIGNATURES:</div>
            <table>
                <tr>
                    <th style="width: 33.33%;">Prepared by:</th>
                    <th style="width: 33.33%;">Reviewed by:</th>
                    <th style="width: 33.33%;">Approved by:</th>
                </tr>
                <tr>
                    <td style="height: 50px; padding-bottom: 4px; vertical-align: bottom;">
                        <div style="border-top: 1px solid #000; font-size: 8px;">{{ $apv->prepared_by ?? '' }}</div>
                    </td>
                    <td style="height: 50px; padding-bottom: 4px; vertical-align: bottom;">
                        <div style="border-top: 1px solid #000; font-size: 8px;">{{ $apv->reviewed_by ?? '' }}</div>
                    </td>
                    <td style="height: 50px; padding-bottom: 4px; vertical-align: bottom;">
                        <div style="border-top: 1px solid #000; font-size: 8px;">{{ $apv->approved_by ?? '' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 10px; font-size: 8px; color: #666; border-top: 1px solid #000; padding-top: 4px;">
            <p>This document is not valid for claiming Input Tax</p>
            <p>Print Date/Time: {{ now()->format('m/d/Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
