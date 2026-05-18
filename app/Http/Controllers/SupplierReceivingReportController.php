<?php

namespace App\Http\Controllers;

use App\Models\SupplierReceivingReport;
use App\Models\SupplierReceivingReportItem;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\LiveChicken;
use App\Models\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupplierReceivingReportController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierReceivingReport::with(['supplier', 'creator', 'items'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('srr_code', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_name', 'LIKE', "%{$search}%")
                  ->orWhere('po_no', 'LIKE', "%{$search}%")
                  ->orWhere('cv_no', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date_to);
        }

        $reports = $query->paginate(20)->appends($request->query());

        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();

        return view('supplier_receiving_reports.index', compact('reports', 'suppliers'));
    }

    public function create()
    {
        $srrCode = $this->generateSrrCode();
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();
        // ✅ NEW: Fetch storages from database instead of hardcoded list
        $storages = Storage::where('status', 'active')->with('warehouse')->orderBy('storage_name')->get();

        return view('supplier_receiving_reports.create', compact('srrCode', 'suppliers', 'storages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'report_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'report_type' => 'required|string|in:purchased,stock_transfer,backload,returned',
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $supplier = $request->supplier_id ? Supplier::find($request->supplier_id) : null;
            $srrCode = $this->generateSrrCode();

            $report = SupplierReceivingReport::create([
                'srr_code' => $srrCode,
                'report_date' => $request->report_date,
                'supplier_id' => $request->supplier_id,
                'supplier_name' => $supplier?->supplier_name,
                'cv_no' => $request->cv_no,
                'po_no' => $request->po_no,
                'reference_number' => $request->reference_number ?: null,
                'storage' => $request->storage,
                'report_type' => $request->report_type,
                'note' => $request->note,
                'prepared_by' => Auth::user()->name,
                'checked_by' => $request->checked_by,
                'received_by' => $request->received_by,
                'verified_by' => $request->verified_by,
                'status' => $request->has('save_final') ? 'saved' : 'draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $index => $item) {
                SupplierReceivingReportItem::create([
                    'supplier_receiving_report_id' => $report->id,
                    'item_no' => $index + 1,
                    'item_code' => $item['item_code'] ?? null,
                    'item_description' => $item['item_description'],
                    'brand' => $item['brand'] ?? null,
                    'no_of_boxes' => $item['no_of_boxes'] ?? 0,
                    'net_weight' => $item['net_weight'] ?? 0,
                    'pd' => $item['pd'] ?? 0,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'pallet_no' => $item['pallet_no'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('supplier_receiving_reports.show', $report->id)
                ->with('success', 'Supplier Receiving Report created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating report: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $report = SupplierReceivingReport::with(['items', 'supplier', 'creator', 'approver'])
            ->findOrFail($id);

        return view('supplier_receiving_reports.show', compact('report'));
    }

    public function edit($id)
    {
        $report = SupplierReceivingReport::with('items')->findOrFail($id);


        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();

        return view('supplier_receiving_reports.edit', compact('report', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'report_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'report_type' => 'required|string|in:purchased,stock_transfer,backload,returned',
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $report = SupplierReceivingReport::findOrFail($id);

            $wasApproved = $report->isApproved();
            $supplier = $request->supplier_id ? Supplier::find($request->supplier_id) : null;

            $report->update([
                'report_date' => $request->report_date,
                'supplier_id' => $request->supplier_id,
                'supplier_name' => $supplier?->supplier_name,
                'cv_no' => $request->cv_no,
                'po_no' => $request->po_no,
                'reference_number' => $request->reference_number ?: null,
                'storage' => $request->storage,
                'report_type' => $request->report_type,
                'note' => $request->note,
                'checked_by' => $request->checked_by,
                'received_by' => $request->received_by,
                'verified_by' => $request->verified_by,
                'status' => $request->has('save_final') ? 'saved' : 'draft',
            ]);

            $report->items()->delete();

            foreach ($request->items as $index => $item) {
                SupplierReceivingReportItem::create([
                    'supplier_receiving_report_id' => $report->id,
                    'item_no' => $index + 1,
                    'item_code' => $item['item_code'] ?? null,
                    'item_description' => $item['item_description'],
                    'brand' => $item['brand'] ?? null,
                    'no_of_boxes' => $item['no_of_boxes'] ?? 0,
                    'net_weight' => $item['net_weight'] ?? 0,
                    'pd' => $item['pd'] ?? 0,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'pallet_no' => $item['pallet_no'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            if ($wasApproved) {
                $rfpIds = DB::table('request_for_payments')
                    ->where('srr_id', $report->id)
                    ->pluck('id');
                DB::table('request_for_payments')->where('srr_id', $report->id)
                    ->update(['status' => 'invalidated']);
                if ($rfpIds->isNotEmpty()) {
                    $apvIds = DB::table('accounts_payable_invoices')
                        ->whereIn('request_for_payment_id', $rfpIds)->pluck('id');
                    if ($apvIds->isNotEmpty()) {
                        DB::table('accounts_payable_invoices')->whereIn('id', $apvIds)
                            ->update(['status' => 'invalidated']);
                        DB::table('check_vouchers')->whereIn('accounts_payable_invoice_id', $apvIds)
                            ->update(['status' => 'invalidated']);
                    }
                }
            }

            DB::commit();

            $msg = $wasApproved
                ? 'Report updated and reset to pending; linked RFPs invalidated.'
                : 'Supplier Receiving Report updated successfully!';

            return redirect()
                ->route('supplier_receiving_reports.show', $report->id)
                ->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating report: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $report = SupplierReceivingReport::findOrFail($id);

            if (\App\Models\RequestForPayment::where('srr_id', $report->id)->exists()) {
                return back()->with('error', 'Cannot delete: this SRR has linked RFPs.');
            }

            $report->delete();

            return redirect()
                ->route('supplier_receiving_reports.index')
                ->with('success', 'Supplier Receiving Report deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting report: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        $user = Auth::user();

        if (!$user->canApproveSupplierReceivingReports()) {
            return redirect()->route('supplier_receiving_reports.index')
                ->with('error', 'Unauthorized to approve receiving reports.');
        }

        $report = SupplierReceivingReport::findOrFail($id);

        $report->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Supplier Receiving Report approved successfully!');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApproveSupplierReceivingReports()) {
            return redirect()->route('supplier_receiving_reports.index')
                ->with('error', 'Unauthorized to reject receiving reports.');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $report = SupplierReceivingReport::findOrFail($id);

        $report->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Supplier Receiving Report rejected.');
    }

    public function print($id)
    {
        $report = SupplierReceivingReport::with(['items', 'supplier', 'creator', 'approver'])
            ->findOrFail($id);

        return view('supplier_receiving_reports.print', compact('report'));
    }

    public function searchPurchaseOrders(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $search = $request->input('search', '');

        $query = PurchaseOrder::where('status', 'approved');

        if ($supplierId) {
            $query->where(function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId)
                  ->orWhereNull('supplier_id');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_no', 'LIKE', "%{$search}%")
                  ->orWhere('supplier', 'LIKE', "%{$search}%");
            });
        }

        // Only show POs that have a live chicken record
        $lcPoNos = LiveChicken::whereNotNull('po_no')->pluck('po_no')->toArray();
        $query->whereIn('po_no', $lcPoNos);

        $pos = $query->select('id', 'po_no', 'supplier', 'brand', 'order_date')
            ->limit(10)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($pos);
    }

    public function exportExcel(Request $request)
    {
        $query = SupplierReceivingReport::with(['items', 'supplier', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date_to);
        }

        $reports = $query->get();

        $filename = 'supplier_receiving_reports_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['SRR Code', 'Date', 'Supplier', 'CV No', 'PO No', 'Storage', 'Type', 'Status', 'Total Boxes', 'Total Net Weight', 'Total PD', 'Created By', 'Created At']);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->srr_code,
                    $report->report_date->format('Y-m-d'),
                    $report->supplier_name,
                    $report->cv_no ?? '',
                    $report->po_no ?? '',
                    $report->storage ?? '',
                    ucfirst(str_replace('_', ' ', $report->report_type)),
                    ucfirst($report->status),
                    $report->items->sum('no_of_boxes'),
                    $report->items->sum('net_weight'),
                    $report->items->sum('pd'),
                    $report->creator->name ?? 'N/A',
                    $report->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function generateSrrCode()
    {
        $year = date('Y');
        $prefix = "SRR-{$year}-";

        $lastReport = SupplierReceivingReport::where('srr_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('srr_code')
            ->first();

        if ($lastReport) {
            $lastNumber = (int) substr($lastReport->srr_code, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function getPOItems(Request $request)
    {
        $poNo = $request->input('po_no');
        if (!$poNo) return response()->json(['po_qty' => 0, 'items' => [], 'blocked' => false]);

        $po = PurchaseOrder::with(['items', 'supplierModel'])->where('po_no', $poNo)->first();
        if (!$po) return response()->json(['po_qty' => 0, 'items' => [], 'blocked' => false]);

        $items = $po->items->map(fn($item) => [
            'item_code'   => $item->item_code,
            'description' => $item->description,
            'brand'       => $item->brand,
            'note'        => $item->note,
            'qty'         => (float) $item->qty,
        ]);

        $poTotalQty = $items->sum('qty');

        // Check if this PO is linked to a live chicken record
        $liveChicken = LiveChicken::where('po_no', $poNo)->first();
        $blocked = $liveChicken && (float) $liveChicken->actual_qty < (float) $poTotalQty;

        return response()->json([
            'po_qty'            => $poTotalQty,
            'lc_actual_qty'     => $liveChicken ? (float) $liveChicken->actual_qty : null,
            'supplier_id'       => $po->supplier_id,
            'supplier_name'     => $po->supplierModel->supplier_name ?? $po->supplier,
            'note'              => $po->remarks,
            'items'             => $items->values(),
            'reference_number'  => $po->reference_number,
            'blocked'           => $blocked,
            'blocked_message'   => $blocked
                ? "PO {$poNo} is blocked: Live Chicken actual qty ({$liveChicken->actual_qty}) is less than PO qty ({$poTotalQty}). Update the Live Chicken record or adjust the PO qty first."
                : null,
        ]);
    }
}
