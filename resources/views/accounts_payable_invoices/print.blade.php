<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>APV {{ $apv->apv_no ?? '' }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:Arial,sans-serif;font-size:10px;color:#000;background:#fff;}
        .page{width:210mm;margin:0 auto;padding:10mm 12mm;}
        @media print{
            body{margin:0;}
            .page{width:100%;padding:6mm 8mm;margin:0;}
            .no-print{display:none!important;}
            @page{size:A4 portrait;margin:6mm;}
        }

        /* Header */
        .top-header{display:flex;align-items:center;gap:12px;margin-bottom:6px;}
        .logo{width:60px;height:60px;object-fit:contain;}
        .company-block{text-align:center;flex:1;}
        .company-name{font-size:13px;font-weight:bold;}
        .company-addr{font-size:8px;color:#333;line-height:1.6;}
        .doc-title{font-size:15px;font-weight:bold;color:#c00;text-align:center;
            margin:6px 0;letter-spacing:1px;}

        /* Info section */
        .info-section{display:grid;grid-template-columns:1fr 1fr;margin-bottom:6px;font-size:9px;}
        .info-row{display:flex;margin-bottom:2px;line-height:1.4;}
        .info-row .lbl{font-weight:bold;min-width:90px;flex-shrink:0;}

        /* Section bar */
        .section-bar{background:#7b0000;color:#fff;font-weight:bold;font-size:10px;
            padding:3px 8px;letter-spacing:2px;text-align:center;}

        /* Entry table */
        table.entry{width:100%;border-collapse:collapse;font-size:8px;}
        table.entry th{background:#c8c8c8;border:1px solid #aaa;padding:3px 4px;text-align:center;font-weight:bold;}
        table.entry td{border:1px solid #ccc;padding:2px 4px;vertical-align:top;}
        table.entry td.amt{text-align:right;}
        table.entry .acct-name{color:#0070c0;}

        /* Remarks + Summary side by side */
        .bottom-section{display:grid;grid-template-columns:1fr 1fr;gap:0;margin-top:0;}
        .remarks-col{}
        .remarks-body{border:1px solid #aaa;border-top:none;padding:5px 8px;min-height:50px;font-size:9px;}
        .summary-col{padding-left:16px;font-size:8.5px;}
        .sum-row{display:flex;justify-content:space-between;gap:10px;margin-bottom:1px;padding:1px 0;}
        .sum-row .lbl{color:#333;}
        .sum-row .val{text-align:right;min-width:80px;}
        .sum-row.wtax .val{color:#c00;}
        .sum-row.grand{border-top:1px solid #000;margin-top:2px;padding-top:2px;}
        .sum-row.grand .lbl,.sum-row.grand .val{font-weight:bold;font-size:9.5px;
            border-bottom:3px double #000;padding-bottom:1px;}

        /* Signatures */
        .sig-section{margin-top:14px;font-size:10px;}
        .sig-labels{display:flex;justify-content:space-between;font-size:9px;font-style:italic;margin-bottom:2px;}
        .sig-2col{display:flex;justify-content:space-between;margin-bottom:10px;}
        .sig-block-l{text-align:left;}
        .sig-block-r{text-align:right;}
        .sig-space{height:38px;}
        .sig-name{font-weight:bold;font-size:10px;text-transform:uppercase;}
        .sig-role{font-size:8.5px;color:#333;}
        .sig-stamp{font-size:7px;color:#555;}
        .sig-center{text-align:center;}
        .sig-label-center{font-size:9px;font-style:italic;margin-bottom:2px;}

        .footer{text-align:center;margin-top:8px;font-size:8px;color:#c00;}
        .footer-sub{text-align:center;font-size:7.5px;color:#666;margin-top:2px;}
        .print-controls{text-align:center;margin-bottom:12px;padding:8px;}
        .btn{padding:8px 20px;font-size:12px;cursor:pointer;border-radius:4px;margin:0 5px;border:none;font-weight:500;text-decoration:none;display:inline-block;}
        .btn-print{background:#6b21a8;color:white;}
        .btn-back{background:#4b5563;color:white;}
    </style>
</head>
<body>
<div class="no-print print-controls">
    <a href="{{ route('accounts_payable_invoices.show', $apv->id) }}" class="btn btn-back">← Back</a>
    <button onclick="setTimeout(()=>window.print(),100)" class="btn btn-print">Print APV</button>
</div>

<div class="page">
    <!-- Header: logo left, company centered -->
    <div class="top-header">
        <img src="{{ asset('images/sopod-logo.PNG') }}" class="logo" alt="Logo">
        <div class="company-block">
            <div class="company-name">Meatplus Trading Corp</div>
            <div class="company-addr">
                12F Victoria Building,<br>
                United Nations Avenue, Ermita, Manila, Philippines, 1004<br>
                VAT Reg. TIN 006-873-989-000
            </div>
        </div>
    </div>

    <div class="doc-title">ACCOUNTS PAYABLE INVOICE</div>

    <!-- Info section -->
    <div class="info-section">
        <div>
            <div class="info-row"><span class="lbl">VENDOR CODE:</span><span>{{ $apv->vendor_code }}</span></div>
            <div class="info-row"><span class="lbl">VENDOR NAME:</span><span>{{ $apv->vendor_name }}</span></div>
            <div class="info-row"><span class="lbl">TERMS:</span><span>{{ $apv->payment_terms ?: '-' }}</span></div>
            <div class="info-row"><span class="lbl">REF NO:</span><span>{{ $apv->reference_no }}</span></div>
            <div class="info-row"><span class="lbl">PO NO:</span><span>{{ $apv->purchase_order_no }}</span></div>
        </div>
        <div>
            <div class="info-row"><span class="lbl">DATE:</span><span>{{ $apv->apv_date ? $apv->apv_date->format('F j, Y') : ($apv->document_date ? $apv->document_date->format('F j, Y') : '') }}</span></div>
            <div class="info-row"><span class="lbl">APV NO.:</span><span>{{ $apv->apv_no }}</span></div>
            <div class="info-row"><span class="lbl">DUE DATE:</span><span>{{ $apv->due_date ? $apv->due_date->format('F j, Y') : '' }}</span></div>
            <div class="info-row"><span class="lbl">CURRENCY:</span><span>{{ $apv->currency }}</span></div>
            <div class="info-row"><span class="lbl">FOREX RATE:</span><span>{{ $apv->forex_rate ? number_format($apv->forex_rate, 4) : '1' }}</span></div>
        </div>
    </div>

    <!-- PO / GRPO Reference Items -->
    @if($grpos->isNotEmpty() || ($apv->requestForPayment?->purchaseOrder?->items?->isNotEmpty()))
    @php
        $po      = $apv->requestForPayment?->purchaseOrder ?? null;
        $useGrpo = $grpos->isNotEmpty();
        $pItems  = $useGrpo
            ? $grpos->map(fn($g,$i) => (object)['no'=>$i+1,'item_code'=>$g->grpo_no??'','description'=>$g->items??'','brand'=>$g->brand??'','qty'=>$g->actual_qty,'uom'=>$g->uom??'KG','unit_price'=>$g->price??0,'total'=>($g->actual_qty??0)*($g->price??0)])
            : ($po?->items->map(fn($item,$i) => (object)['no'=>$item->item_no??$i+1,'item_code'=>$item->item_code??'','description'=>$item->description??'','brand'=>$item->brand??'','qty'=>$item->qty,'uom'=>$item->uom??'','unit_price'=>$item->unit_price,'total'=>$item->total]) ?? collect());
        $poNo    = $po?->po_no ?? '';
        $csym    = ($apv->currency ?? 'PHP') === 'USD' ? '$' : '₱';
    @endphp
    <div style="font-weight:bold;font-size:9px;margin:5px 0 3px;letter-spacing:1px;color:#555;">
        {{ $useGrpo ? 'GRPO ITEMS' : 'PURCHASE ORDER ITEMS' }}{{ $poNo ? ' — '.$poNo : '' }}
    </div>
    <table class="entry" style="margin-bottom:8px;">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:13%">Item Code</th>
                <th style="width:30%">Description</th>
                <th style="width:13%">Brand</th>
                <th style="width:10%;text-align:center">Qty Received</th>
                <th style="width:7%;text-align:center">UOM</th>
                <th style="width:12%;text-align:right">Unit Price</th>
                <th style="width:11%;text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pItems as $pi)
            <tr>
                <td>{{ $pi->no }}</td>
                <td>{{ $pi->item_code }}</td>
                <td>{{ $pi->description }}</td>
                <td>{{ $pi->brand }}</td>
                <td style="text-align:center">{{ number_format($pi->qty,2) }}</td>
                <td style="text-align:center">{{ $pi->uom }}</td>
                <td class="amt">{{ $csym }}{{ number_format($pi->unit_price,2) }}</td>
                <td class="amt">{{ $csym }}{{ number_format($pi->total,2) }}</td>
            </tr>
            @endforeach
            <tr style="background:#ddd;font-weight:bold;">
                <td colspan="7" style="text-align:right;padding:3px 4px;border:1px solid #aaa;">Grand Total:</td>
                <td class="amt" style="border:1px solid #aaa;font-weight:bold;">{{ $csym }}{{ number_format($pItems->sum('total'),2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Entry table -->
    <div class="section-bar">ENTRY &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; AMOUNT</div>
    <table class="entry">
        <thead>
            <tr>
                <th style="width:18%">Particuars</th>
                <th style="width:11%">Item Code</th>
                <th style="width:9%">Division</th>
                <th style="width:11%">Account Code</th>
                <th style="width:27%">Account Name</th>
                <th style="width:12%">DEBIT</th>
                <th style="width:12%">CREDIT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($apv->items as $item)
            @php $amt=(float)$item->gross_amount; @endphp
            <tr style="{{ $loop->even ? 'background:#f7f7f7;':'' }}">
                <td>{{ $item->particulars }}</td>
                <td>{{ $item->item_code }}</td>
                <td>{{ $item->division }}</td>
                <td>{{ $item->account_code }}</td>
                <td class="acct-name">{{ $item->account_name }}</td>
                <td class="amt">{{ $amt>0 ? number_format($amt,2) : '' }}</td>
                <td class="amt">{{ $amt<0 ? number_format(abs($amt),2) : '' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:#999;">No items</td></tr>
            @endforelse
            @if($apv->w_tax_amount > 0)
            <tr>
                <td></td><td></td><td></td>
                <td>211300015</td>
                <td class="acct-name">Withholding Tax Payable - EWT</td>
                <td class="amt"></td>
                <td class="amt">{{ number_format($apv->w_tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td></td><td></td><td></td>
                <td>{{ $apv->account_code ?? '211100004' }}</td>
                <td class="acct-name">{{ $apv->account_name ?? 'Accounts Payable' }}</td>
                <td class="amt"></td>
                <td class="amt">{{ number_format($apv->grand_total, 2) }}</td>
            </tr>
            @php $crRows = ($apv->w_tax_amount > 0 ? 1 : 0) + 1; @endphp
            @for($r=count($apv->items)+$crRows;$r<9;$r++)
            <tr style="height:15px;"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
        @php
            $dT=$apv->items->where('gross_amount','>=',0)->sum('gross_amount');
            $cT=$apv->items->filter(fn($i)=>$i->gross_amount<0)->sum(fn($i)=>abs($i->gross_amount)) + (float)$apv->w_tax_amount + (float)$apv->grand_total;
        @endphp
        <tfoot>
            <tr>
                <td colspan="5" style="background:#ddd;border:1px solid #aaa;"></td>
                <td style="text-align:right;border:1px solid #aaa;padding:3px 4px;background:#ddd;font-weight:bold;">{{ number_format($dT,2) }}</td>
                <td style="text-align:right;border:1px solid #aaa;padding:3px 4px;background:#ddd;font-weight:bold;">{{ number_format($cT,2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Remarks + Summary -->
    <div class="bottom-section">
        <div class="remarks-col">
            <div class="section-bar" style="font-size:9px;">REMARKS</div>
            <div class="remarks-body">{{ $apv->remarks }}</div>
        </div>
        <div class="summary-col">
            @if($apv->currency !== 'PHP')
            <div class="sum-row"><span class="lbl">Total {{ $apv->currency }}:</span><span class="val">{{ number_format($apv->total,2) }}</span></div>
            @endif
            <div class="sum-row"><span class="lbl">Total PHP:</span><span class="val">{{ number_format($apv->currency!=='PHP' ? $apv->total*($apv->forex_rate?:1) : $apv->total,2) }}</span></div>
            <div class="sum-row"><span class="lbl">Total Before VAT:</span><span class="val">{{ number_format($apv->total_before_vat,2) }}</span></div>
            <div class="sum-row"><span class="lbl">VAT Amount:</span><span class="val">{{ number_format($apv->vat_amount,2) }}</span></div>
            <div class="sum-row wtax"><span class="lbl">Wtax Amount:</span><span class="val">({{ number_format($apv->w_tax_amount,2) }})</span></div>
            <div class="sum-row grand"><span class="lbl">Grand Total {{ $apv->currency !== 'PHP' ? $apv->currency : '' }}:</span><span class="val">{{ number_format($apv->grand_total,2) }}</span></div>
            @if($apv->currency !== 'PHP')
            <div class="sum-row" style="margin-top:4px;padding-top:2px;border-top:1px dashed #aaa;">
                <span class="lbl" style="color:#0070c0;">Philippine Peso:</span>
                <span class="val" style="color:#0070c0;font-weight:bold;">₱{{ number_format($apv->grand_total * $liveRate, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Signatures -->
    <div class="sig-section">
        <div class="sig-labels">
            <span>Prepared by:</span>
            <span>Reviewed by:</span>
        </div>
        <div class="sig-2col">
            <div class="sig-block-l">
                <div class="sig-space">
                    @if($apv->creator)<div class="sig-stamp">@include('partials.esignature',['signer'=>$apv->creator])</div>@endif
                </div>
                <div class="sig-name">{{ $apv->creator->name ?? '' }}</div>
                <div class="sig-role">Accounting Analyst</div>
            </div>
            <div class="sig-block-r">
                <div class="sig-space">
                    @if($apv->departmentHeadApprover)
                        <div class="sig-stamp">@include('partials.esignature',['signer'=>$apv->departmentHeadApprover])</div>
                        @if($apv->department_head_approved_at)<div class="sig-stamp">{{ $apv->department_head_approved_at->format('d F Y | H:i') }}</div>@endif
                    @endif
                </div>
                <div class="sig-name">{{ $apv->departmentHeadApprover->name ?? '' }}</div>
                <div class="sig-role">Accounting Supervisor</div>
            </div>
        </div>
        <div class="sig-center">
            <div class="sig-label-center">Approved by:</div>
            <div class="sig-space">
                @if($apv->approver)
                    <div class="sig-stamp">@include('partials.esignature',['signer'=>$apv->approver])</div>
                    @if($apv->approved_at)<div class="sig-stamp">{{ $apv->approved_at->format('d F Y | H:i') }}</div>@endif
                @endif
            </div>
            <div class="sig-name">{{ $apv->approver->name ?? '' }}</div>
            <div class="sig-role">Accounting Manager</div>
        </div>
    </div>

    <div class="footer">This document is not valid for claiming Input Tax</div>
    <div class="footer-sub">Print Date/Time: &nbsp; {{ now()->format('m/d/y h:i A') }}</div>
</div>
</body>
</html>
