<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GRPO {{ $record->grpo_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .page { width: 210mm; min-height: 297mm; padding: 12mm 14mm; margin: 0 auto; }

        /* Header */
        .header { display: flex; align-items: center; margin-bottom: 8px; }
        .header-logo { width: 70px; margin-right: 16px; }
        .header-logo img { width: 100%; }
        .header-company { flex: 1; text-align: center; }
        .header-company .company-name { font-size: 14px; font-weight: bold; color: #8B0000; }
        .header-company .company-address { font-size: 10px; color: #333; line-height: 1.5; }

        /* Title */
        .title-row { text-align: center; margin: 8px 0 10px; }
        .title-row h1 { font-size: 20px; font-weight: bold; color: #8B0000; letter-spacing: 1px; }

        /* GRPO info block */
        .info-block { display: flex; margin-bottom: 10px; }
        .vendor-block { flex: 1; }
        .vendor-block .label { font-size: 10px; color: #555; }
        .vendor-block .vendor-name { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
        .vendor-block .vendor-email { font-size: 10px; color: #333; }
        .grpo-details { width: 220px; border: 1px solid #8B0000; padding: 6px 10px; }
        .grpo-details table { width: 100%; border-collapse: collapse; }
        .grpo-details td { font-size: 10px; padding: 1px 0; vertical-align: top; }
        .grpo-details td:first-child { color: #555; white-space: nowrap; padding-right: 6px; }
        .grpo-details td:last-child { font-weight: 500; }
        .grpo-no { font-size: 14px; font-weight: bold; color: #8B0000; }
        .grpo-date { font-size: 14px; font-weight: bold; color: #8B0000; }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .items-table thead tr { background-color: #8B0000; color: #fff; }
        .items-table thead th { padding: 6px 8px; text-align: left; font-size: 11px; border: 1px solid #ccc; }
        .items-table thead th.right { text-align: right; }
        .items-table tbody td { padding: 6px 8px; border: 1px solid #ccc; font-size: 11px; vertical-align: top; }
        .items-table tfoot td { padding: 6px 8px; border: 1px solid #ccc; font-weight: bold; font-size: 11px; }
        .items-table .item-rows td { min-height: 20px; }

        /* Remarks */
        .remarks-box { border: 1px solid #ccc; margin: 8px 0; }
        .remarks-box .remarks-label { background: #8B0000; color: #fff; padding: 4px 8px; font-weight: bold; font-size: 11px; }
        .remarks-box .remarks-content { padding: 8px; min-height: 50px; font-size: 11px; }

        /* Signature block */
        .sig-block { display: flex; margin: 12px 0 8px; gap: 20px; }
        .sig-col { flex: 1; text-align: center; }
        .sig-col .sig-label { font-size: 10px; color: #555; margin-bottom: 4px; }
        .sig-col .sig-image-wrap { height: 55px; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #000; margin-bottom: 4px; }
        .sig-col .sig-image-wrap img { max-height: 50px; max-width: 160px; object-fit: contain; }
        .sig-col .sig-name { font-weight: bold; font-size: 11px; }
        .sig-col .sig-role { font-size: 10px; color: #555; font-style: italic; }
        .sig-col .sig-date { font-size: 9px; color: #777; margin-top: 2px; }

        /* Copies footer */
        .copies-row { display: flex; margin-top: 10px; border-top: 1px solid #ccc; padding-top: 6px; }
        .copies-row .copy { flex: 1; text-align: center; font-weight: bold; font-size: 10px; }

        /* Footer note */
        .footer-notes { text-align: center; margin-top: 10px; font-size: 9px; color: #555; }
        .print-date { text-align: right; font-size: 9px; color: #777; margin-top: 6px; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Print button (hidden on print) --}}
    <div class="no-print" style="text-align:right; margin-bottom:10px;">
        <button onclick="window.print()" style="background:#8B0000;color:#fff;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;font-size:13px;">
            🖨 Print
        </button>
        <button onclick="window.close()" style="background:#555;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-size:13px;margin-left:8px;">
            Close
        </button>
    </div>

    @php
        $po = $record->purchaseOrder;
        $poItems = $po ? $po->items : collect();
        $vendorEmail = $po?->supplierModel?->email ?? '';
        $paymentTerms = $po?->payment_terms ?? '—';
    @endphp

    {{-- Company Header --}}
    <div class="header">
        <div class="header-logo">
            <img src="{{ asset('images/meatplus-logo.png') }}" alt="Meatplus" onerror="this.style.display='none'">
        </div>
        <div class="header-company">
            <div class="company-name">Meatplus Trading Corp</div>
            <div class="company-address">
                12F Victoria Building,<br>
                United Nations Avenue, Ermita, Manila, Philippines, 1004<br>
                VAT Reg. TIN 006-873-989-000
            </div>
        </div>
    </div>

    {{-- Title --}}
    <div class="title-row">
        <h1>GOODS RECEIPT PO</h1>
    </div>

    {{-- Vendor + GRPO details --}}
    <div class="info-block">
        <div class="vendor-block">
            <div class="label">Vendor:</div>
            <div class="vendor-name">{{ strtoupper($record->supplier) }}</div>
            @if($vendorEmail)
                <div class="vendor-email">{{ $vendorEmail }}</div>
            @endif
        </div>
        <div class="grpo-details">
            <table>
                <tr>
                    <td style="font-size:12px;font-weight:bold;color:#8B0000;">GRPO</td>
                    <td style="font-size:12px;font-weight:bold;color:#8B0000;">{{ $record->grpo_no }}</td>
                </tr>
                <tr>
                    <td>GRPO Date :</td>
                    <td>{{ $record->date->format('m/d/Y') }}</td>
                </tr>
                <tr>
                    <td>Purchase Order No. :</td>
                    <td>{{ $record->po_no ?? '—' }}</td>
                </tr>
                @if($record->reference_number)
                <tr>
                    <td>Reference No. :</td>
                    <td>{{ $record->reference_number }}</td>
                </tr>
                @endif
                <tr>
                    <td>Payment Terms :</td>
                    <td>{{ $paymentTerms }}</td>
                </tr>
                <tr>
                    <td>Shipping Type :</td>
                    <td>{{ $record->shipping_type ?? '' }}</td>
                </tr>
                <tr>
                    <td>Storage Name :</td>
                    <td>{{ $record->storage_name ?? '' }}</td>
                </tr>
                <tr>
                    <td>Storage Reference No :</td>
                    <td>{{ $record->storage_reference_no ?? '' }}</td>
                </tr>
                <tr>
                    <td>Container No :</td>
                    <td>{{ $record->container_no ?? '' }}</td>
                </tr>
                <tr>
                    <td>Pallet No :</td>
                    <td>{{ $record->pallet_no ?? '' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Item Category</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th class="right">Qty</th>
                <th>UOM</th>
                <th>Pallet No</th>
            </tr>
        </thead>
        <tbody>
            @if($poItems->isNotEmpty())
                @foreach($poItems as $item)
                <tr class="item-rows">
                    <td>{{ $item->item_category ?? 'Trade Inventory' }}</td>
                    <td>{{ $item->item_code }}</td>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:right">{{ number_format($record->actual_qty, 2) }}</td>
                    <td>{{ $item->uom }}</td>
                    <td>{{ $record->pallet_no ?? '' }}</td>
                </tr>
                @endforeach
            @else
                <tr class="item-rows">
                    <td>Trade Inventory</td>
                    <td>{{ $record->brand ?? '' }}</td>
                    <td>{{ $record->items }}</td>
                    <td style="text-align:right">{{ number_format($record->actual_qty, 2) }}</td>
                    <td>kg</td>
                    <td>{{ $record->pallet_no ?? '' }}</td>
                </tr>
            @endif
            {{-- Blank filler rows --}}
            @for($i = ($poItems->isNotEmpty() ? $poItems->count() : 1); $i < 7; $i++)
            <tr><td style="height:22px;">&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
        <tfoot> 
            <tr>
                <td colspan="3" style="text-align:left; font-weight:bold;">Subtotal</td>
                <td style="text-align:right">{{ number_format($record->actual_qty, 2) }}</td>
                <td></td>
                <td></td> 
            </tr>
        </tfoot>
    </table>

    {{-- Remarks --}}
    <div class="remarks-box">
        <div class="remarks-label">Remarks</div>
        <div class="remarks-content">{{ $record->items }}</div>
    </div>

    {{-- Signature Block --}}
    <div class="sig-block">
        <div class="sig-col">
            <div class="sig-label">Received by:</div>
            <div class="sig-image-wrap">
                @if($record->receivedBy?->esignature)
                    <img src="{{ asset('storage/' . $record->receivedBy->esignature) }}" alt="Signature">
                @elseif($record->receivedBy)
                    <span style="font-size:10px;color:#333;">Digitally Signed</span>
                @endif
            </div>
            <div class="sig-name">{{ strtoupper($record->receivedBy?->name ?? '') }}</div>
            <div class="sig-role">Warehouse Receiving Staff</div>
            @if($record->received_at)
                <div class="sig-date">{{ $record->received_at->format('m/d/Y h:i A') }}</div>
            @endif
        </div>
        <div class="sig-col">
            <div class="sig-label">Approved by:</div>
            <div class="sig-image-wrap">
                @if($record->grpoApprovedBy?->esignature)
                    <img src="{{ asset('storage/' . $record->grpoApprovedBy->esignature) }}" alt="Signature">
                @elseif($record->grpoApprovedBy)
                    <span style="font-size:10px;color:#333;">Digitally Signed</span>
                @endif
            </div>
            <div class="sig-name">{{ strtoupper($record->grpoApprovedBy?->name ?? '') }}</div>
            <div class="sig-role">Warehouse Supervisor</div>
            @if($record->grpo_approved_at)
                <div class="sig-date">{{ $record->grpo_approved_at->format('m/d/Y h:i A') }}</div>
            @endif
        </div>
        <div class="sig-col">
            <div class="sig-label">Time Received:</div>
            <div class="sig-image-wrap"></div>
            <div class="sig-name">&nbsp;</div>
            <div class="sig-role">&nbsp;</div>
        </div>
    </div>

    {{-- Copies --}}
    <div class="copies-row">
        <div class="copy">COPY 1 : ACCOUNTING</div>
        <div class="copy">COPY 2 : WAREHOUSE</div>
        <div class="copy">COPY 3 : PURCHASING</div>
    </div>

    {{-- Footer Notes --}}
    <div class="footer-notes">
        <div>This document is not valid for claiming Input Tax</div>
        <div>This is a system generated document. No signature is required.</div>
    </div>
    <div class="print-date">
        Print Date/Time: {{ now()->format('n/j/Y g:i:sA') }} &nbsp;&nbsp; Page 1 of 1
    </div>

</div>
</body>
</html>

