<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ strtoupper(str_replace('_', ' ', $adjustment->transaction_type)) }} - {{ $adjustment->reference_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm 18mm;
            background: #fff;
        }

        /* ── Header ── */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #1a1a2e;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left img {
            height: 55px;
            width: auto;
        }

        .company-info .company-name {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #1a1a2e;
            text-transform: uppercase;
        }

        .company-info .company-sub {
            font-size: 9px;
            color: #555;
            margin-top: 1px;
        }

        .header-right {
            text-align: right;
        }

        .doc-type-badge {
            display: inline-block;
            background: #1a1a2e;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 6px 16px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .doc-type-badge.decrease {
            background: #7f1d1d;
        }

        .doc-type-badge.increase {
            background: #14532d;
        }

        .ref-number {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }

        .ref-number strong {
            font-size: 13px;
            color: #000;
        }

        /* ── Info Table ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border: 1px solid #ccc;
            margin-bottom: 12px;
        }

        .info-grid .info-row {
            display: contents;
        }

        .info-row > .info-cell {
            display: grid;
            grid-template-columns: 120px 1fr;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-row > .info-cell:nth-child(odd) {
            border-right: 1px solid #ccc;
        }

        .info-cell .label {
            background: #f3f4f6;
            font-weight: 700;
            padding: 5px 8px;
            border-right: 1px solid #e0e0e0;
            color: #333;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .info-cell .value {
            padding: 5px 8px;
            font-size: 11px;
            color: #111;
        }

        .info-cell.full-width {
            grid-column: span 2;
        }

        /* ── Amount Section ── */
        .amount-section {
            border: 2px solid #1a1a2e;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .amount-section.decrease {
            border-color: #7f1d1d;
            background: #fff5f5;
        }

        .amount-section.increase {
            border-color: #14532d;
            background: #f0fdf4;
        }

        .amount-label {
            font-size: 11px;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
        }

        .amount-direction {
            font-size: 9px;
            color: #777;
            margin-top: 2px;
        }

        .amount-value {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .amount-value.decrease { color: #991b1b; }
        .amount-value.increase { color: #166534; }

        /* ── Remarks Box ── */
        .remarks-box {
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 8px 10px;
            margin-bottom: 14px;
            min-height: 40px;
        }

        .remarks-box .section-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
        }

        .remarks-box .remarks-text {
            font-size: 11px;
            color: #111;
            line-height: 1.5;
        }

        /* ── Signatures ── */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }

        .sig-block {
            text-align: center;
        }

        .sig-block .sig-name {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: 700;
            font-size: 10px;
        }

        .sig-block .sig-title {
            font-size: 9px;
            color: #555;
            margin-top: 1px;
        }

        .sig-space {
            height: 35px;
        }

        /* ── Footer ── */
        .print-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            font-size: 8.5px;
            color: #888;
        }

        /* ── Watermark for non-decrease (e.g., debit memo) ── */
        .doc-type-label {
            font-size: 10px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 6px;
        }

        /* ── Print ── */
        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 12mm 15mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="background:#1a1a2e;color:#fff;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-size:13px;font-weight:600;">Print Preview — {{ $adjustment->reference_number }}</span>
    <div style="display:flex;gap:10px;">
        <button onclick="window.print()" style="background:#3b82f6;color:#fff;border:none;padding:7px 18px;border-radius:5px;font-size:12px;cursor:pointer;">&#128438; Print</button>
        <button onclick="window.close()" style="background:#6b7280;color:#fff;border:none;padding:7px 18px;border-radius:5px;font-size:12px;cursor:pointer;">&#10005; Close</button>
    </div>
</div>

<div class="page">

    {{-- ═══ HEADER ═══ --}}
    <div class="header">
        <div class="header-left">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            @endif
            <div class="company-info">
                <div class="company-name">SOPOD Enterprises</div>
                <div class="company-sub">Credits & Collection Department</div>
            </div>
        </div>
        <div class="header-right">
            <div class="doc-type-badge {{ $adjustment->is_decrease ? 'decrease' : 'increase' }}">
                {{ $adjustment->formatted_type }}
            </div>
            <div class="ref-number">
                Reference No: <strong>{{ $adjustment->reference_number }}</strong>
            </div>
        </div>
    </div>

    {{-- ═══ INFO GRID ═══ --}}
    <div class="info-grid">

        {{-- Row 1: Customer Name | Transaction Date --}}
        <div class="info-row">
            <div class="info-cell">
                <span class="label">Customer Name</span>
                <span class="value">{{ $adjustment->customer_name }}</span>
            </div>
            <div class="info-cell">
                <span class="label">Transaction Date</span>
                <span class="value">{{ $adjustment->transaction_date->format('F d, Y') }}</span>
            </div>
        </div>

        {{-- Row 2: Customer Code | Branch --}}
        <div class="info-row">
            <div class="info-cell">
                <span class="label">Customer Code</span>
                <span class="value">{{ $adjustment->customer_code ?? '—' }}</span>
            </div>
            <div class="info-cell">
                <span class="label">Branch</span>
                <span class="value">{{ $adjustment->branch ?? '—' }}</span>
            </div>
        </div>

        {{-- Row 3: DR Number | Invoice Number --}}
        <div class="info-row">
            <div class="info-cell">
                <span class="label">DR Number</span>
                <span class="value">{{ $adjustment->dr_no ?? '—' }}</span>
            </div>
            <div class="info-cell">
                <span class="label">Invoice Number</span>
                <span class="value">{{ $adjustment->invoice_number ?? '—' }}</span>
            </div>
        </div>

        {{-- Row 4: GL Account | Transaction Type --}}
        <div class="info-row">
            <div class="info-cell">
                <span class="label">GL Account</span>
                <span class="value">
                    {{ $adjustment->gl_account ?? '—' }}
                    @if($adjustment->glAccount)
                        &nbsp;—&nbsp;{{ $adjustment->glAccount->account_name }}
                    @endif
                </span>
            </div>
            <div class="info-cell">
                <span class="label">Type</span>
                <span class="value">{{ $adjustment->formatted_type }}</span>
            </div>
        </div>

    </div>

    {{-- ═══ AMOUNT ═══ --}}
    <div class="amount-section {{ $adjustment->is_decrease ? 'decrease' : 'increase' }}">
        <div>
            <div class="amount-label">Adjustment Amount</div>
            <div class="amount-direction">
                {{ $adjustment->is_decrease ? '▼ Decreases Accounts Receivable' : '▲ Increases Accounts Receivable' }}
            </div>
        </div>
        <div class="amount-value {{ $adjustment->is_decrease ? 'decrease' : 'increase' }}">
            {{ $adjustment->is_decrease ? '(' : '' }}₱{{ number_format(abs($adjustment->amount), 2) }}{{ $adjustment->is_decrease ? ')' : '' }}
        </div>
    </div>

    {{-- ═══ REMARKS ═══ --}}
    <div class="remarks-box">
        <div class="section-label">Remarks / Justification</div>
        <div class="remarks-text">{{ $adjustment->remarks ?: 'No remarks provided.' }}</div>
    </div>

    {{-- ═══ SIGNATURES ═══ --}}
    <div class="signatures">
        <div class="sig-block">
            <div class="sig-space"></div>
            <div class="sig-name">{{ $adjustment->created_by }}</div>
            <div class="sig-title">Prepared By</div>
        </div>
        <div class="sig-block">
            <div class="sig-space"></div>
            <div class="sig-name">{{ $adjustment->signed_by }}</div>
            <div class="sig-title">Approved / Signed By</div>
        </div>
        <div class="sig-block">
            <div class="sig-space"></div>
            <div class="sig-name">&nbsp;</div>
            <div class="sig-title">Received By (Customer)</div>
        </div>
    </div>

    {{-- ═══ FOOTER ═══ --}}
    <div class="print-footer">
        <span>Printed: {{ now()->format('F d, Y h:i A') }}</span>
        <span>Created: {{ $adjustment->created_at->format('F d, Y h:i A') }}</span>
        <span>Ref: {{ $adjustment->reference_number }}</span>
    </div>

</div>

</body>
</html>
