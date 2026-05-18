<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableInvoice;
use App\Models\RequestForPayment;
use App\Models\CashAdvanceRequest;
use App\Models\ReimbursementForm;
use App\Models\ApvItem;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountsPayableInvoiceController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->canAccessAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $query = AccountsPayableInvoice::with(['creator', 'requestForPayment']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('apv_no', 'like', "%{$s}%")
                  ->orWhere('vendor_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('apv_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('apv_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20);

        return view('accounts_payable_invoices.index', compact('invoices'));
    }

    public function exportExcel(Request $request)
    {
        if (!Auth::user()->canAccessAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $query = AccountsPayableInvoice::with(['creator', 'requestForPayment']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('apv_no', 'like', "%{$s}%")
                  ->orWhere('vendor_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('apv_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('apv_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('created_at')->get();

        $filename = 'AP_Invoices_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($file, [
                'APV No', 'APV Date', 'RFP No', 'Vendor Name', 'Payment Type',
                'Currency', 'Grand Total', 'Status', 'Due Date', 'Created By', 'Created At',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->apv_no,
                    $inv->apv_date ? $inv->apv_date->format('Y-m-d') : '',
                    $inv->requestForPayment->rfp_no ?? 'N/A',
                    $inv->vendor_name,
                    $inv->payment_type === 'downpayment' ? 'Downpayment' : 'Full Payment',
                    $inv->currency,
                    number_format($inv->grand_total, 2),
                    ucfirst($inv->status),
                    $inv->due_date ? $inv->due_date->format('Y-m-d') : '',
                    $inv->creator->name ?? 'N/A',
                    $inv->created_at ? $inv->created_at->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create(Request $request)
    {
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $apvNo = 'APV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $selectedRFP  = null;
        $supplierInfo = null;

        if ($request->has('rfp_id')) {
            $selectedRFP = RequestForPayment::with(['purchaseOrder.supplierModel'])->find($request->rfp_id);
            if ($selectedRFP && $selectedRFP->purchaseOrder) {
                $supplier     = $selectedRFP->purchaseOrder->supplierModel;
                $supplierInfo = [
                    'address' => $supplier->address ?? $selectedRFP->purchaseOrder->supplier_address ?? '',
                    'tin'     => $supplier->tin ?? '',
                    'code'    => $supplier->supplier_code ?? '',
                ];
            }
        }

        $glAccounts = $this->getGlAccounts();

        return view('accounts_payable_invoices.create', compact('apvNo', 'selectedRFP', 'supplierInfo', 'glAccounts'));
    }

    /**
     * Search approved RFPs (AJAX)
     */
    public function searchRFPs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $rfps = RequestForPayment::with(['purchaseOrder.supplierModel', 'purchaseOrder.items'])
            ->where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('rfp_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('payee', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('company', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('purchaseOrder', fn($q) => $q->where('po_no', 'LIKE', "%{$searchTerm}%"));
            })
            ->limit(10)
            ->get()
            ->map(function ($rfp) {
                $supplier = $rfp->purchaseOrder->supplierModel ?? null;
                $po = $rfp->purchaseOrder;
                $vendorAddress = $po->supplier_address ?? $supplier->address ?? '';
                $vendorTin     = $po->supplier_tin ?? $supplier->tin ?? '';

                // Resolve vendor_code: try supplier first, then vendor table by payee name
                $vendorCode = $supplier->supplier_code ?? '';
                if (!$vendorCode) {
                    $vendorMatch = \App\Models\Vendor::where('vendor_name', $rfp->payee)->first()
                        ?? \App\Models\Supplier::where('supplier_name', $rfp->payee)->first();
                    $vendorCode = $vendorMatch->vendor_code ?? $vendorMatch->supplier_code ?? '';
                }

                return [
                    'id'               => $rfp->id,
                    'rfp_no'           => $rfp->rfp_no,
                    'payee'            => $rfp->payee,
                    'company'          => $rfp->company,
                    'date'             => $rfp->date,
                    'amount'           => (float) $rfp->amount,
                    'particulars'      => $rfp->particulars,
                    'purchase_order_no'   => $po->po_no ?? '',
                    'po_reference_number' => $po->reference_number ?? '',
                    'vendor_address'      => $vendorAddress,
                    'vendor_tin'          => $vendorTin,
                    'vendor_code'         => $vendorCode,
                    'payment_terms'       => $po->payment_terms ?? '',
                    'currency'            => $po->currency ?? 'PHP',
                    'forex_rate'          => $po->forex_rate ?? '',
                    'po_type'             => $po->po_type ?? 'items',
                    'service_description' => $po->service_description ?? null,
                    'po_items'            => $po && $po->po_type !== 'service'
                        ? $po->items->map(fn($i) => ['description' => $i->description, 'item_code' => $i->item_code])->values()
                        : [],
                ];
            });

        return response()->json($rfps);
    }

    /**
     * Search approved Cash Advance Requests (AJAX)
     */
    public function searchCARs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $cars = CashAdvanceRequest::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('car_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('payee', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('department', 'LIKE', "%{$searchTerm}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($car) {
                return [
                    'id'             => $car->id,
                    'car_no'         => $car->car_no,
                    'payee'          => $car->payee,
                    'department'     => $car->department,
                    'amount'         => (float) $car->amount_advanced,
                    'purpose'        => $car->purpose,
                    'date_requested' => $car->date_requested?->format('Y-m-d'),
                ];
            });

        return response()->json($cars);
    }

    /**
     * Search approved Reimbursement Forms (AJAX)
     */
    public function searchReimbursements(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $reimbursements = ReimbursementForm::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('ri_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('department', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('submitted_by', 'LIKE', "%{$searchTerm}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($ri) {
                return [
                    'id'           => $ri->id,
                    'ri_no'        => $ri->ri_no,
                    'department'   => $ri->department,
                    'submitted_by' => $ri->submitted_by,
                    'amount'       => (float) $ri->amount_to_be_reimbursed,
                    'total_spent'  => (float) $ri->total_amount_spent,
                    'date_applied' => $ri->date_applied?->format('Y-m-d'),
                ];
            });

        return response()->json($reimbursements);
    }

    /**
     * Store a newly created invoice.
     * VAT and EWT are only computed when currency is PHP.
     * For all other currencies (USD, EUR, JPY, etc.) vat_amount and w_tax_amount are forced to 0.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'request_for_payment_id' => 'nullable|exists:request_for_payments,id',
            'apv_date'               => 'required|date',
            'payment_type'           => 'required|in:full_payment,downpayment',
            'vendor_name'            => 'required|string',
            'document_date'          => 'required|date',
            'currency'               => 'required|string',
            'items'                  => 'required|array|min:1',
            'items.*.gross_amount'   => 'required|numeric',
        ]);

        $items    = $request->items ?? [];
        $currency = $request->currency;
        $isPhp    = $currency === 'PHP';

        // ── Only use positive (DR) items for totals; skip CR entries ─────────
        $drItems = array_filter($items, fn($i) => ((float)($i['gross_amount'] ?? 0)) > 0);

        // ── Compute totals ────────────────────────────────────────────────────
        $total = array_sum(array_column($drItems, 'gross_amount'));

        $ewtRates = ['C158'=>0.01,'158'=>0.01,'C160'=>0.02,'160'=>0.02,'C100'=>0.05,'I010'=>0.05,'I011'=>0.10];

        if ($isPhp) {
            $vatAmount = array_sum(array_map(
                fn($i) => !empty($i['vat']) ? ($i['gross_amount'] * 12 / 112) : 0,
                $drItems
            ));
            $wTaxAmount = array_sum(array_map(function ($i) use ($ewtRates) {
                $net  = !empty($i['vat']) ? ($i['gross_amount'] * 100 / 112) : $i['gross_amount'];
                $rate = $ewtRates[$i['tax_code'] ?? ''] ?? 0;
                return $net * $rate;
            }, $drItems));

            // Fallback: if no tax_code computed EWT, check if any item explicitly uses the EWT account
            if ($wTaxAmount == 0) {
                foreach ($items as $i) {
                    $code = $i['account_code'] ?? '';
                    $name = strtolower($i['account_name'] ?? '');
                    if ($code === '211300015' || str_contains($name, 'withholding') || str_contains($name, 'ewt')) {
                        $wTaxAmount += abs((float)($i['gross_amount'] ?? 0));
                    }
                }
            }
        } else {
            // No VAT or withholding tax for foreign currencies
            $vatAmount  = 0;
            $wTaxAmount = 0;
        }

        $netOfVat          = $total - $vatAmount;
        $grandTotal        = $isPhp ? ($total - $wTaxAmount) : $total;
        $downpaymentAmount = $request->downpayment_amount ?? 0;

        // ── RFP amount cap check ──────────────────────────────────────────────
        if ($request->request_for_payment_id) {
            $rfp = RequestForPayment::find($request->request_for_payment_id);
            if ($rfp && (float) $total > (float) $rfp->amount) {
                return back()->withInput()->with(
                    'error',
                    'Total amount (₱' . number_format($total, 2) . ') exceeds RFP amount (₱' . number_format($rfp->amount, 2) . ').'
                );
            }
        }

        DB::beginTransaction();
        try {
            $apvNo = 'APV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $invoice = AccountsPayableInvoice::create([
                'apv_no'                  => $apvNo,
                'request_for_payment_id'  => $request->request_for_payment_id,
                'cash_advance_request_id' => $request->cash_advance_request_id,
                'reimbursement_form_id'   => $request->reimbursement_form_id,
                'reference_type'          => $request->reference_type,
                'apv_date'                => $request->apv_date,
                'payment_type'            => $request->payment_type,
                'vendor_code'             => $request->vendor_code,
                'vendor_name'             => $request->vendor_name,
                'vendor_address'          => $request->vendor_address,
                'vendor_tin'              => $request->vendor_tin,
                'document_date'           => $request->document_date,
                'payment_terms'           => $request->payment_terms,
                'due_date'                => $request->due_date,
                'reference_no'            => $request->reference_no,
                'purchase_order_no'       => $request->purchase_order_no,
                'currency'                => $currency,
                'forex_rate'              => $request->forex_rate,
                'particulars'             => collect($items)->pluck('particulars')->filter()->implode('; '),
                'item_code'               => collect($items)->pluck('item_code')->filter()->first(),
                'account_code'            => collect($items)->pluck('account_code')->filter()->first(),
                'account_name'            => collect($items)->pluck('account_name')->filter()->first(),
                'total'                   => $total,
                'downpayment_amount'      => $downpaymentAmount,
                'total_before_vat'        => $total,
                'vat_amount'              => $vatAmount,
                'total_after_vat'         => $netOfVat,
                'w_tax_amount'            => $wTaxAmount,
                'grand_total'             => $grandTotal,
                'prepared_by'             => $request->prepared_by,
                'reviewed_by'             => $request->reviewed_by,
                'remarks'                 => $request->remarks,
                'status'                  => 'pending',
                'created_by'              => Auth::id(),
            ]);

            foreach ($drItems as $row) {
                ApvItem::create([
                    'apv_id'       => $invoice->id,
                    'particulars'  => $row['particulars'] ?? null,
                    'item_code'    => $row['item_code'] ?? null,
                    'department'   => $row['department'] ?? null,
                    'division'     => $row['division'] ?? null,
                    'vat'          => ($isPhp && !empty($row['vat'])) ? 1 : 0,
                    'tax_code'     => $isPhp ? ($row['tax_code'] ?? null) : null,
                    'account_code' => $row['account_code'] ?? null,
                    'account_name' => $row['account_name'] ?? null,
                    'gross_amount' => $row['gross_amount'] ?? 0,
                ]);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action'    => 'Created',
                'item'      => $invoice->apv_no,
                'target'    => $invoice->vendor_name,
                'type'      => 'Accounts Payable Invoice',
                'message'   => 'Created Accounts Payable Invoice ' . $invoice->apv_no . ' for ' . $invoice->vendor_name,
            ]);

            return redirect()
                ->route('accounts_payable_invoices.show', $invoice->id)
                ->with('success', 'Accounts Payable Invoice created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice.
     */
    public function show($id)
    {
        $invoice = AccountsPayableInvoice::with(['creator', 'requestForPayment.purchaseOrder.items', 'approver', 'departmentHeadApprover', 'items'])
            ->findOrFail($id);

        $po    = $invoice->requestForPayment?->purchaseOrder ?? null;
        $grpos = $po ? \App\Models\LiveChicken::where('po_no', $po->po_no)->orderBy('date')->get() : collect();

        return view('accounts_payable_invoices.show', compact('invoice', 'grpos'));
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit($id)
    {
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $invoice    = AccountsPayableInvoice::findOrFail($id);


        $glAccounts = $this->getGlAccounts();
        return view('accounts_payable_invoices.edit', compact('invoice', 'glAccounts'));
    }

    /**
     * Update the specified invoice.
     * VAT and EWT are only computed when currency is PHP.
     * For all other currencies (USD, EUR, JPY, etc.) vat_amount and w_tax_amount are forced to 0.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'apv_date'             => 'required|date',
            'payment_type'         => 'required|in:full_payment,downpayment',
            'vendor_name'          => 'required|string',
            'document_date'        => 'required|date',
            'currency'             => 'required|string',
            'items'                => 'required|array|min:1',
            'items.*.gross_amount' => 'required|numeric',
        ]);

        $invoice  = AccountsPayableInvoice::findOrFail($id);
        $wasApproved = $invoice->status === 'approved';
        $items    = $request->input('items', []);
        $currency = $request->currency;
        $isPhp    = $currency === 'PHP';

        // ── Only use positive (DR) items for totals; skip CR entries ─────────
        $drItems = array_filter($items, fn($i) => ((float)($i['gross_amount'] ?? 0)) > 0);

        // ── Compute totals ────────────────────────────────────────────────────
        $total = array_sum(array_column($drItems, 'gross_amount'));

        $ewtRates = ['C158'=>0.01,'158'=>0.01,'C160'=>0.02,'160'=>0.02,'C100'=>0.05,'I010'=>0.05,'I011'=>0.10];

        if ($isPhp) {
            $vatAmount = array_sum(array_map(
                fn($i) => !empty($i['vat']) ? ($i['gross_amount'] * 12 / 112) : 0,
                $drItems
            ));
            $wTaxAmount = array_sum(array_map(function ($i) use ($ewtRates) {
                $net  = !empty($i['vat']) ? ($i['gross_amount'] * 100 / 112) : $i['gross_amount'];
                $rate = $ewtRates[$i['tax_code'] ?? ''] ?? 0;
                return $net * $rate;
            }, $drItems));

            // Fallback: if no tax_code computed EWT, check if any item explicitly uses the EWT account
            if ($wTaxAmount == 0) {
                foreach ($items as $i) {
                    $code = $i['account_code'] ?? '';
                    $name = strtolower($i['account_name'] ?? '');
                    if ($code === '211300015' || str_contains($name, 'withholding') || str_contains($name, 'ewt')) {
                        $wTaxAmount += abs((float)($i['gross_amount'] ?? 0));
                    }
                }
            }
        } else {
            // No VAT or withholding tax for foreign currencies
            $vatAmount  = 0;
            $wTaxAmount = 0;
        }

        $netOfVat          = $total - $vatAmount;
        $grandTotal        = $isPhp ? ($total - $wTaxAmount) : $total;
        $downpaymentAmount = $request->downpayment_amount ?? 0;

        // ── RFP amount cap check ──────────────────────────────────────────────
        $rfpId = $request->request_for_payment_id ?: $invoice->request_for_payment_id;
        if ($rfpId) {
            $rfp = RequestForPayment::find($rfpId);
            if ($rfp && (float) $total > (float) $rfp->amount) {
                return back()->withInput()->with(
                    'error',
                    'Total amount (₱' . number_format($total, 2) . ') exceeds RFP amount (₱' . number_format($rfp->amount, 2) . ').'
                );
            }
        }

        DB::beginTransaction();
        try {
            $invoice->update([
                'apv_date'          => $request->apv_date,
                'payment_type'      => $request->payment_type,
                'vendor_code'       => $request->vendor_code,
                'vendor_name'       => $request->vendor_name,
                'vendor_address'    => $request->vendor_address,
                'vendor_tin'        => $request->vendor_tin,
                'document_date'     => $request->document_date,
                'payment_terms'     => $request->payment_terms,
                'due_date'          => $request->due_date,
                'reference_no'      => $request->reference_no,
                'purchase_order_no' => $request->purchase_order_no,
                'currency'          => $currency,
                'forex_rate'        => $request->forex_rate,
                'particulars'       => collect($drItems)->pluck('particulars')->filter()->implode('; '),
                'total'             => $total,
                'downpayment_amount'=> $downpaymentAmount,
                'total_before_vat'  => $total,
                'vat_amount'        => $vatAmount,
                'total_after_vat'   => $netOfVat,
                'w_tax_amount'      => $wTaxAmount,
                'grand_total'       => $grandTotal,
                'prepared_by'       => $request->prepared_by,
                'reviewed_by'       => $request->reviewed_by,
                'remarks'           => $request->remarks,
            ]);

            // Replace all items (only save positive DR entries)
            $invoice->items()->delete();
            foreach ($drItems as $row) {
                ApvItem::create([
                    'apv_id'       => $invoice->id,
                    'particulars'  => $row['particulars'] ?? null,
                    'item_code'    => $row['item_code'] ?? null,
                    'department'   => $row['department'] ?? null,
                    'division'     => $row['division'] ?? null,
                    // Force VAT and tax to zero/null for non-PHP currencies
                    'vat'          => ($isPhp && !empty($row['vat'])) ? 1 : 0,
                    'tax_code'     => $isPhp ? ($row['tax_code'] ?? null) : null,
                    'account_code' => $row['account_code'] ?? null,
                    'account_name' => $row['account_name'] ?? null,
                    'gross_amount' => $row['gross_amount'],
                ]);
            }

            if ($wasApproved) {
                DB::table('accounts_payable_invoices')->where('id', $invoice->id)->update([
                    'status'                            => 'pending',
                    'approval_stage'                    => 'pending',
                    'approved_by'                       => null,
                    'approved_at'                       => null,
                    'department_head_approved_by'       => null,
                    'department_head_approved_at'       => null,
                    'department_head_approved_latitude' => null,
                    'department_head_approved_longitude'=> null,
                    'department_head_approved_location' => null,
                ]);
                // Invalidate downstream CVs
                \App\Models\CheckVoucher::where('accounts_payable_invoice_id', $invoice->id)
                    ->update(['status' => 'invalidated']);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action'    => 'Updated',
                'item'      => $invoice->apv_no,
                'target'    => $invoice->vendor_name,
                'type'      => 'Accounts Payable Invoice',
                'message'   => 'Updated Accounts Payable Invoice ' . $invoice->apv_no . ($wasApproved ? ' (reset to pending for re-approval)' : ''),
            ]);

            $msg = $wasApproved
                ? 'Invoice updated and reset to pending for re-approval.'
                : 'Invoice updated successfully!';

            return redirect()
                ->route('accounts_payable_invoices.show', $invoice->id)
                ->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy($id)
    {
        try {
            $invoice = AccountsPayableInvoice::findOrFail($id);

            if (\App\Models\CheckVoucher::where('accounts_payable_invoice_id', $invoice->id)->exists()) {
                return back()->with('error', 'Cannot delete: this APV has linked Check Vouchers.');
            }

            $apvNo   = $invoice->apv_no;
            $invoice->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action'    => 'Deleted',
                'item'      => $apvNo,
                'target'    => 'N/A',
                'type'      => 'Accounts Payable Invoice',
                'message'   => 'Deleted Accounts Payable Invoice ' . $apvNo,
            ]);

            return redirect()
                ->route('accounts_payable_invoices.index')
                ->with('success', 'Invoice deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting invoice: ' . $e->getMessage());
        }
    }

    /**
     * Approve as Department Head (Level 1 - Review)
     */
    public function approveDH(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveAPVInvoices()) {
            return redirect()->route('accounts_payable_invoices.index')->with('error', 'Unauthorized.');
        }

        $invoice = AccountsPayableInvoice::where('approval_stage', 'pending_dh')->findOrFail($id);
        $invoice->update([
            'approval_stage'                    => 'pending_accounting',
            'department_head_approved_by'       => Auth::id(),
            'department_head_approved_at'       => now(),
            'department_head_approved_latitude' => $request->input('latitude'),
            'department_head_approved_longitude'=> $request->input('longitude'),
            'department_head_approved_location' => $request->input('location'),
            'rejection_reason'                  => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Reviewed (DH)',
            'item'      => $invoice->apv_no,
            'target'    => $invoice->vendor_name,
            'type'      => 'Accounts Payable Invoice',
            'message'   => 'Department Head reviewed Accounts Payable Invoice ' . $invoice->apv_no,
        ]);

        return redirect()->back()->with('success', 'APV reviewed by Department Head. Forwarded to Accounting Manager.');
    }

    /**
     * Approve as Accounting Manager (Level 2 - Final)
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveAPV()) {
            return redirect()->route('accounts_payable_invoices.index')->with('error', 'Unauthorized.');
        }

        $invoice = AccountsPayableInvoice::where('approval_stage', 'pending_accounting')->findOrFail($id);
        $invoice->update([
            'status'            => 'approved',
            'approval_stage'    => 'approved',
            'approved_by'       => Auth::id(),
            'approved_at'       => now(),
            'approved_latitude' => $request->input('latitude'),
            'approved_longitude'=> $request->input('longitude'),
            'approved_location' => $request->input('location'),
            'rejection_reason'  => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Approved',
            'item'      => $invoice->apv_no,
            'target'    => $invoice->vendor_name,
            'type'      => 'Accounts Payable Invoice',
            'message'   => 'Approved Accounts Payable Invoice ' . $invoice->apv_no,
        ]);

        return redirect()->back()->with('success', 'Invoice approved!');
    }

    /**
     * Reject an invoice (any stage)
     */
    public function reject(Request $request, $id)
    {
        if (!Auth::user()->canApproveAPVInvoices()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $invoice = AccountsPayableInvoice::findOrFail($id);

        $invoice->update([
            'status'           => 'rejected',
            'approval_stage'   => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Rejected',
            'item'      => $invoice->apv_no,
            'target'    => $invoice->vendor_name,
            'type'      => 'Accounts Payable Invoice',
            'message'   => 'Rejected Accounts Payable Invoice ' . $invoice->apv_no,
        ]);

        return redirect()->back()->with('success', 'Invoice rejected.');
    }

    /**
     * Print accounts payable invoice
     */
    public function print($id)
    {
        $apv = AccountsPayableInvoice::with(['creator', 'approver', 'departmentHeadApprover', 'items', 'requestForPayment.purchaseOrder.items'])->findOrFail($id);
        $liveRate = \App\Models\Currency::phpRate($apv->currency ?? 'PHP');
        $po    = $apv->requestForPayment->purchaseOrder ?? null;
        $grpos = $po ? \App\Models\LiveChicken::where('po_no', $po->po_no)->orderBy('date')->get() : collect();
        return view('accounts_payable_invoices.print', ['apv' => $apv, 'liveRate' => $liveRate, 'grpos' => $grpos]);
    }

    private function getGlAccounts(): array
    {
        return \App\Models\GlAccount::orderBy('account_code')
            ->get(['account_code', 'account_name'])
            ->map(fn($a) => [
                'code'    => $a->account_code,
                'name'    => $a->account_name,
                'display' => $a->account_code . ' — ' . $a->account_name,
                'search'  => strtolower($a->account_code . ' ' . $a->account_name),
            ])->toArray();
    }

    public function ewtRegister(Request $request)
    {
        if (!Auth::user()->canAccessAPV()) abort(403);

        $dateFrom = $request->date_from ?? now()->startOfYear()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');
        $status   = $request->status;

        $ewtRates = ['C158'=>0.01,'158'=>0.01,'C160'=>0.02,'160'=>0.02,'C100'=>0.05,'I010'=>0.05,'I011'=>0.10];

        $items = \App\Models\ApvItem::with('apv')
            ->whereNotNull('tax_code')->where('tax_code', '!=', '')
            ->whereHas('apv', function($q) use ($dateFrom, $dateTo, $status) {
                $q->whereDate('apv_date', '>=', $dateFrom)->whereDate('apv_date', '<=', $dateTo);
                if ($status) $q->where('status', $status);
            })->get();

        $vendors = [];
        foreach ($items as $item) {
            $name = $item->apv->vendor_name ?? 'Unknown';
            if (!isset($vendors[$name])) $vendors[$name] = ['gross'=>0,'vat'=>0,'net'=>0,'ewt'=>0,'amount_due'=>0,'count'=>0];
            $gross = abs((float)$item->gross_amount);
            $vatAmt = $item->vat ? $gross * 12/112 : 0;
            $net    = $gross - $vatAmt;
            $ewt    = $net * ($ewtRates[$item->tax_code] ?? 0);
            $vendors[$name]['gross']      += $gross;
            $vendors[$name]['vat']        += $vatAmt;
            $vendors[$name]['net']        += $net;
            $vendors[$name]['ewt']        += $ewt;
            $vendors[$name]['amount_due'] += ($net - $ewt);
            $vendors[$name]['count']++;
        }
        ksort($vendors);

        return view('ewt_register.index', compact('vendors','dateFrom','dateTo','status'));
    }

    public function ewtDetail(Request $request, $vendorName)
    {
        if (!Auth::user()->canAccessAPV()) abort(403);

        $vendorName = urldecode($vendorName);

        $dateFrom = $request->date_from ?? now()->startOfYear()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');
        $status   = $request->status;

        $ewtRates = ['C158'=>0.01,'158'=>0.01,'C160'=>0.02,'160'=>0.02,'C100'=>0.05,'I010'=>0.05,'I011'=>0.10];

        $items = \App\Models\ApvItem::with('apv')
            ->whereNotNull('tax_code')->where('tax_code', '!=', '')
            ->whereHas('apv', function($q) use ($vendorName, $dateFrom, $dateTo, $status) {
                $q->where('vendor_name', $vendorName)
                  ->whereDate('apv_date', '>=', $dateFrom)
                  ->whereDate('apv_date', '<=', $dateTo);
                if ($status) $q->where('status', $status);
            })->get();

        $rows = $items->map(function($item) use ($ewtRates) {
            $gross  = abs((float)$item->gross_amount);
            $vatAmt = $item->vat ? $gross * 12/112 : 0;
            $net    = $gross - $vatAmt;
            $rate   = $ewtRates[$item->tax_code] ?? 0;
            $ewt    = $net * $rate;
            return [
                'apv_no'      => $item->apv->apv_no,
                'apv_date'    => $item->apv->apv_date,
                'status'      => $item->apv->status,
                'apv_id'      => $item->apv->id,
                'particulars' => $item->particulars,
                'tax_code'    => $item->tax_code,
                'rate_pct'    => $rate * 100,
                'gross'       => $gross,
                'vat'         => $vatAmt,
                'net'         => $net,
                'ewt'         => $ewt,
                'amount_due'  => $net - $ewt,
            ];
        });

        $totals = [
            'gross'      => $rows->sum('gross'),
            'vat'        => $rows->sum('vat'),
            'net'        => $rows->sum('net'),
            'ewt'        => $rows->sum('ewt'),
            'amount_due' => $rows->sum('amount_due'),
        ];

        return view('ewt_register.detail', compact('vendorName','rows','totals','dateFrom','dateTo','status'));
    }
}