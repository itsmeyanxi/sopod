<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReceivingReport;
use App\Models\ReceivingReportItem;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;

class ReceivingReportsController extends Controller
{
    /**
     * Display list of receiving reports
     */
    public function index(Request $request)
    {
        $query = ReceivingReport::with(['salesOrder.customer', 'items'])
            ->withSum('items as quantity', 'quantity')
            ->withSum('items as total_amount', 'total_amount')
            ->orderBy('received_date', 'desc');

        // Date filters
        if ($request->filled('date_from')) {
            $query->whereDate('received_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('received_date', '<=', $request->date_to);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('rr_number', 'like', '%' . $request->search . '%')
                  ->orWhere('sales_order_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('plate_no', 'like', '%' . $request->search . '%');
            });
        }

        $receivingReports = $query->get();

        return view('receiving_reports.index', compact('receivingReports'));
    }

    /**
     * Show single receiving report
     */
    public function show($id)
    {
        $receivingReport = ReceivingReport::with(['items', 'salesOrder'])->findOrFail($id);
        return view('receiving_reports.show', compact('receivingReport'));
    }

    /**
     * Print single receiving report
     */
    public function print($id)
    {
        $receivingReport = ReceivingReport::with([
            'salesOrder.items.item',
            'items.item'
        ])->findOrFail($id);
        
        return view('receiving_reports.print', compact('receivingReport'));
    }

    /**
     * Export receiving reports to Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $search = $request->input('search');
            $status = $request->input('status');

            $query = ReceivingReport::with(['items', 'salesOrder.customer']);

            if ($dateFrom) {
                $query->whereDate('received_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('received_date', '<=', $dateTo);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('rr_number', 'like', '%' . $search . '%')
                      ->orWhere('customer_name', 'like', '%' . $search . '%')
                      ->orWhere('sales_order_number', 'like', '%' . $search . '%');
                });
            }

            $receivingReports = $query->orderBy('received_date', 'desc')->get();

            $filename = 'receiving_reports_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($receivingReports) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'RR Number',
                    'Sales Order',
                    'Customer',
                    'Branch',
                    'Item Code',
                    'Item Description',
                    'Brand',
                    'Category',
                    'Quantity',
                    'UOM',
                    'Price',
                    'Amount',
                    'Received Date',
                    'Plate No',
                    'Status',
                    'Received By'
                ]);

                $grandTotal = 0;

                foreach ($receivingReports as $rr) {
                    $receivedDate = $rr->received_date ? \Carbon\Carbon::parse($rr->received_date)->format('m/d/Y') : '—';
                    
                    if ($rr->items && $rr->items->count() > 0) {
                        $rrTotal = 0;
                        
                        foreach ($rr->items as $item) {
                            fputcsv($file, [
                                $rr->rr_number,
                                $rr->sales_order_number,
                                $rr->customer_name ?? '—',
                                $rr->branch ?? '—',
                                $item->item_code ?? '—',
                                $item->item_description ?? '—',
                                $item->brand ?? '—',
                                $item->item_category ?? '—',
                                number_format($item->quantity, 2),
                                $item->uom ?? '—',
                                number_format($item->unit_price, 2),
                                number_format($item->total_amount, 2),
                                $receivedDate,
                                $rr->plate_no ?? '—',
                                $rr->status,
                                $rr->received_by ?? '—'
                            ]);
                            
                            $rrTotal += $item->total_amount;
                        }
                        
                        $grandTotal += $rrTotal;
                        
                        // Subtotal
                        $subtotalRow = array_fill(0, 11, '');
                        $subtotalRow[10] = 'SUBTOTAL:';
                        $subtotalRow[11] = number_format($rrTotal, 2);
                        fputcsv($file, $subtotalRow);
                        fputcsv($file, []); // Blank line
                    }
                }

                // Grand total
                fputcsv($file, []);
                $grandTotalRow = array_fill(0, 11, '');
                $grandTotalRow[10] = '>>> GRAND TOTAL <<<';
                $grandTotalRow[11] = number_format($grandTotal, 2);
                fputcsv($file, $grandTotalRow);

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export receiving reports failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(500, 'Failed to export: ' . $e->getMessage());
        }
    }
}