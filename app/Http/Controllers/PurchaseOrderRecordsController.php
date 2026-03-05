<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\PurchaseOrder;
use App\Models\RequestForPayment;
use App\Models\AccountsPayableInvoice;
use App\Models\CheckVoucher;

class PurchaseOrderRecordsController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'purchase_requests');
        $status = $request->query('status', 'all');
        $from = $request->query('from');
        $to = $request->query('to');
        $user = auth()->user();

        // Determine which tabs the user can see
        $allowedTabs = $this->getAllowedTabs($user);

        // If current type is not allowed, default to first allowed tab
        if (!in_array($type, $allowedTabs)) {
            $type = $allowedTabs[0] ?? 'purchase_requests';
        }

        $records = $this->getRecords($type, $status, $from, $to);

        return view('po_records.index', [
            'records' => $records,
            'type' => $type,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'allowedTabs' => $allowedTabs,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $type = $request->query('type', 'purchase_requests');
        $status = $request->query('status', 'all');
        $from = $request->query('from');
        $to = $request->query('to');

        $records = $this->getRecords($type, $status, $from, $to, false);

        $filename = $type . '_records_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        $callback = function () use ($records, $type) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            switch ($type) {
                case 'purchase_requests':
                    fputcsv($file, ['PR No', 'Company', 'Requisitioner', 'Supplier', 'Date of Request', 'Status', 'Created By']);
                    foreach ($records as $r) {
                        fputcsv($file, [
                            $r->pr_no,
                            $r->company,
                            $r->requisitioner,
                            $r->supplier->supplier_name ?? 'N/A',
                            $r->date_of_request,
                            ucfirst($r->status),
                            $r->creator->name ?? 'N/A',
                        ]);
                    }
                    break;

                case 'purchase_orders':
                    fputcsv($file, ['PO No', 'PR No', 'Company', 'Supplier', 'Order Date', 'Status', 'Created By']);
                    foreach ($records as $r) {
                        fputcsv($file, [
                            $r->po_no,
                            $r->pr_no ?? 'N/A',
                            $r->company,
                            $r->supplier ?? 'N/A',
                            $r->order_date,
                            ucfirst($r->status),
                            $r->creator->name ?? 'N/A',
                        ]);
                    }
                    break;

                case 'rfp':
                    fputcsv($file, ['RFP No', 'PO No', 'Company', 'Payee', 'Amount', 'Date', 'Status', 'Created By']);
                    foreach ($records as $r) {
                        fputcsv($file, [
                            $r->rfp_no,
                            $r->purchaseOrder->po_no ?? 'N/A',
                            $r->company,
                            $r->payee,
                            $r->amount,
                            $r->date,
                            ucfirst($r->status),
                            $r->creator->name ?? 'N/A',
                        ]);
                    }
                    break;

                case 'apv':
                    fputcsv($file, ['APV No', 'RFP No', 'Vendor', 'Payment Type', 'Grand Total', 'Status', 'Created By']);
                    foreach ($records as $r) {
                        fputcsv($file, [
                            $r->apv_no,
                            $r->requestForPayment->rfp_no ?? 'N/A',
                            $r->vendor_name,
                            ucfirst($r->payment_type ?? 'N/A'),
                            $r->grand_total,
                            ucfirst($r->status),
                            $r->creator->name ?? 'N/A',
                        ]);
                    }
                    break;

                case 'check_vouchers':
                    fputcsv($file, ['CV No', 'APV No', 'Supplier', 'Bank', 'Check Amount', 'Status', 'Created By']);
                    foreach ($records as $r) {
                        fputcsv($file, [
                            $r->cv_no,
                            $r->apv_no ?? 'N/A',
                            $r->supplier_name,
                            $r->bank ?? 'N/A',
                            $r->check_amount,
                            ucfirst($r->status),
                            $r->creator->name ?? 'N/A',
                        ]);
                    }
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getAllowedTabs($user): array
    {
        $tabs = [];

        if ($user->canAccessModule('purchase_requests')) {
            $tabs[] = 'purchase_requests';
        }
        if ($user->canAccessModule('purchase_orders')) {
            $tabs[] = 'purchase_orders';
        }
        if ($user->canAccessModule('rfp')) {
            $tabs[] = 'rfp';
        }
        if ($user->canAccessModule('apv')) {
            $tabs[] = 'apv';
            $tabs[] = 'check_vouchers';
        }

        return $tabs;
    }

    private function getRecords(string $type, string $status, ?string $from, ?string $to, bool $paginate = true)
    {
        switch ($type) {
            case 'purchase_requests':
                $query = PurchaseRequest::with(['creator', 'supplier'])->orderByDesc('created_at');
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
                if ($from) {
                    $query->whereDate('date_of_request', '>=', $from);
                }
                if ($to) {
                    $query->whereDate('date_of_request', '<=', $to);
                }
                break;

            case 'purchase_orders':
                $query = PurchaseOrder::with('creator')->orderByDesc('created_at');
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
                if ($from) {
                    $query->whereDate('order_date', '>=', $from);
                }
                if ($to) {
                    $query->whereDate('order_date', '<=', $to);
                }
                break;

            case 'rfp':
                $query = RequestForPayment::with(['creator', 'purchaseOrder'])->orderByDesc('created_at');
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
                if ($from) {
                    $query->whereDate('date', '>=', $from);
                }
                if ($to) {
                    $query->whereDate('date', '<=', $to);
                }
                break;

            case 'apv':
                $query = AccountsPayableInvoice::with(['creator', 'requestForPayment'])->orderByDesc('created_at');
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
                if ($from) {
                    $query->whereDate('apv_date', '>=', $from);
                }
                if ($to) {
                    $query->whereDate('apv_date', '<=', $to);
                }
                break;

            case 'check_vouchers':
                $query = CheckVoucher::with('creator')->orderByDesc('created_at');
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
                if ($from) {
                    $query->whereDate('cv_date', '>=', $from);
                }
                if ($to) {
                    $query->whereDate('cv_date', '<=', $to);
                }
                break;

            default:
                $query = PurchaseRequest::with(['creator', 'supplier'])->orderByDesc('created_at');
                break;
        }

        return $paginate ? $query->paginate(20) : $query->get();
    }
}
