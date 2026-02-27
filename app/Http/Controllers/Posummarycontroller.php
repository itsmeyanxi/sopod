<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POSummaryController extends Controller
{
    public function index()
    {
        return view('records.purchase_orders.po_summary');
    }

    public function apiData(Request $request)
    {
        $company = $request->query('company');

        $query = DB::table('purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            // ── Subquery: sum of item totals per PO (the real PO amount) ──
            ->leftJoin(
                DB::raw('(
                    SELECT purchase_order_id,
                           SUM(COALESCE(total, 0)) as items_total
                    FROM purchase_order_items
                    GROUP BY purchase_order_id
                ) as poi'),
                'poi.purchase_order_id', '=', 'po.id'
            )
            // ── Subquery: one row per PO from APV (no duplicates) ──
            ->leftJoin(
                DB::raw('(
                    SELECT purchase_order_no,
                           SUM(grand_total) as grand_total,
                           MAX(id)          as id
                    FROM accounts_payable_invoices
                    GROUP BY purchase_order_no
                ) as apv'),
                'apv.purchase_order_no', '=', 'po.po_no'
            )
            // ── Subquery: one row per PO from RFP ──
            ->leftJoin(
                DB::raw('(
                    SELECT purchase_order_id,
                           MAX(id) as id
                    FROM request_for_payments
                    GROUP BY purchase_order_id
                ) as rfp'),
                'rfp.purchase_order_id', '=', 'po.id'
            )
            // ── Subquery: sum paid amounts per PO across ALL APVs and their CVs ──
            ->leftJoin(
                DB::raw('(
                    SELECT apv_inner.purchase_order_no,
                           SUM(CASE WHEN cv_inner.status = "approved" THEN cv_inner.paid_amount ELSE 0 END) as paid_amount,
                           MAX(cv_inner.id)     as id,
                           MAX(cv_inner.status) as status
                    FROM accounts_payable_invoices as apv_inner
                    LEFT JOIN check_vouchers as cv_inner ON cv_inner.accounts_payable_invoice_id = apv_inner.id
                    WHERE cv_inner.id IS NOT NULL
                    GROUP BY apv_inner.purchase_order_no
                ) as cv'),
                'cv.purchase_order_no', '=', 'po.po_no'
            )
            ->select([
                'po.id',
                'po.po_no',
                DB::raw("DATE_FORMAT(po.order_date, '%d-%b-%y') as date"),
                DB::raw('COALESCE(s.supplier_name, po.supplier) as supplier'),
                'po.location',
                'po.company',
                'po.status as approval_status',
                // Use sum of item totals; fall back to lc_price for old POs without items
                DB::raw('COALESCE(poi.items_total, po.lc_price, 0) as po_amount'),
                DB::raw('COALESCE(apv.grand_total, 0)  as invoice_amount'),
                DB::raw('COALESCE(cv.paid_amount, 0)   as paid_amount'),
                'cv.status  as cv_status',
                'rfp.id     as rfp_id',
                'apv.id     as apv_id',
                'cv.id      as cv_id',
            ])
            ->where('po.status', '!=', 'rejected');

        if ($company) {
            $query->where(function ($q) use ($company) {
                $q->where('po.company', $company);

                if ($company === 'NBC') {
                    $q->orWhere('po.company', 'like', '%North Breeders%');
                } elseif ($company === 'PMAI') {
                    $q->orWhere('po.company', 'like', '%Magalang%')
                      ->orWhere('po.company', 'like', '%Pacific Magalang%');
                } elseif ($company === 'PARI') {
                    $q->orWhere('po.company', 'like', '%Agro Resources%')
                      ->orWhere('po.company', 'like', '%Pacific Agro%');
                }

                $prefixMap = ['NBC' => 'NBC', 'PMAI' => 'PMA', 'PARI' => 'PAR'];
                if (isset($prefixMap[$company])) {
                    $q->orWhere('po.po_no', 'like', $prefixMap[$company] . '%');
                }
            });
        }

        $rows = $query
            ->orderByDesc('po.created_at')
            ->get()
            ->map(function ($r) {
                $paymentStatus = $this->derivePaymentStatus(
                    $r->approval_status,
                    $r->rfp_id,
                    $r->apv_id,
                    $r->cv_id,
                    $r->cv_status,
                    (float) $r->po_amount,
                    (float) $r->paid_amount,
                    (float) $r->invoice_amount
                );

                return [
                    'po_number'      => $r->po_no,
                    'date'           => $r->date,
                    'supplier'       => $r->supplier ?? 'N/A',
                    'category'       => $r->location ?? '',
                    'gl_type'        => '',
                    'company'        => self::normalizeCompany($r->company, $r->po_no),
                    'po_amount'      => (float) $r->po_amount,
                    'invoice_amount' => (float) $r->invoice_amount,
                    'paid_amount'    => (float) $r->paid_amount,
                    'status'         => $paymentStatus,
                ];
            });

        return response()->json(['pos' => $rows]);
    }

    private static function normalizeCompany(?string $company, ?string $poNumber): string
    {
        if (in_array($company, ['NBC', 'PMAI', 'PARI'])) return $company;

        $lower = strtolower($company ?? '');
        if (str_contains($lower, 'north breeders')) return 'NBC';
        if (str_contains($lower, 'magalang'))        return 'PMAI';
        if (str_contains($lower, 'agro resources'))  return 'PARI';

        $po = $poNumber ?? '';
        if (str_starts_with($po, 'NBC')) return 'NBC';
        if (str_starts_with($po, 'PMA')) return 'PMAI';
        if (str_starts_with($po, 'PAR')) return 'PARI';

        return $company ?? 'UNKNOWN';
    }

    private function derivePaymentStatus(
        ?string $approvalStatus,
        $rfpId,
        $apvId,
        $cvId,
        ?string $cvStatus,
        float $poAmount,
        float $paidAmount,
        float $invoiceAmount
    ): string {
        // PO not yet approved — put on hold
        if ($approvalStatus === 'pending') {
            return 'HOLD';
        }

        // CV exists with approved paid amount
        if ($cvId && $paidAmount > 0) {
            return $paidAmount >= $invoiceAmount ? 'PAID' : 'FOR COLLECTION';
        }

        // APV exists but no CV payment yet
        if ($apvId) {
            return 'FOR DEPOSIT';
        }

        // RFP filed but no APV yet
        if ($rfpId) {
            return 'FOR COLLECTION';
        }

        // Nothing filed yet
        return 'UNPAID';
    }
}