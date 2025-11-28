<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\Deliveries;

class RecordsController extends Controller
{
    public function index(Request $request)
    {
        $report = $request->query('report');
        $type = $request->query('type', 'sales_orders');
        $sort = $request->query('sort');
        $direction = $request->query('dir', 'asc');
        $showCancelled = $request->query('cancelled');
        
        // NEW: Get from/to filter parameters
        $from = $request->query('from');
        $to = $request->query('to');

        if ($type === 'sales_orders') {
            $query = SalesOrder::latest();
            $isSales = true;
            
            // NEW: Apply sales order range filter
            if ($from && $to) {
                $query->whereBetween('sales_order_number', [$from, $to]);
            } elseif ($from) {
                $query->where('sales_order_number', '>=', $from);
            } elseif ($to) {
                $query->where('sales_order_number', '<=', $to);
            }
        } 
        elseif ($type === 'deliveries') {
            $query = Deliveries::query();
            $isSales = false;

            // ----------------------------
            // FILTER : Show only cancelled
            // ----------------------------
            if ($showCancelled == 1) {
                $query->whereIn('status', ['Cancelled', 'Declined']);
            }

            if ($request->delivered_only) {
                $query->where('status', 'Delivered');
            }

            // ----------------------------
            // SORTING LOGIC FOR DELIVERIES
            // ----------------------------
            if ($sort) {
                // Validate direction
                $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
                
                switch ($sort) {
                    case 'amount':
                        // Use UNSIGNED to properly sort numeric values
                        $query->orderByRaw('CAST(total_amount AS UNSIGNED) ' . $direction);
                        break;

                    case 'customer':
                        $query->orderBy('customer_name', $direction);
                        break;

                    case 'item':
                        $query->orderBy('item_description', $direction);
                        break;
                }
            } else {
                $query->latest();
            }

        } 
        else {
            // category filter (Metromart, Lean Beef Topside etc.)
            $query = SalesOrder::where(function($q) use ($type) {
                $q->where('branch', 'LIKE', "%$type%")
                  ->orWhere('item_description', 'LIKE', "%$type%");
            })->latest();

            $isSales = true; 
        }

        // Default paginate
        $records = $query->paginate(10);

        return view('records.index', [
            'records' => $records,
            'report' => $report,
            'type' => $type,
            'sort' => $sort,
            'direction' => $direction,
            'showCancelled' => $showCancelled,
            'from' => $from,
            'to' => $to,
        ]);
    }




    //  SALES ORDER REPORT HANDLER
    private function salesOrderReports($query, $report)
    {
        return match($report) {
            'cancelled_so' => $query->whereIn('status', ['Declined','Cancelled'])
                ->get(['sales_order_number','customer_name','branch','item_description','total_amount']),

            'top_customers' => $query->selectRaw("customer_name, SUM(total_amount) as total_amount")
                ->groupBy('customer_name')
                ->orderByDesc('total_amount')
                ->limit(10)
                ->get(),

            'monthly_sales' => $query->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total_amount")
                ->groupBy('month')
                ->orderBy('month')
                ->get(),

            'sales_by_customer' => $query->selectRaw("customer_name, SUM(total_amount) as total_amount")
                ->groupBy('customer_name')
                ->get(),

            'sales_by_item' => $query->selectRaw("item_description, SUM(total_amount) as total_amount")
                ->groupBy('item_description')
                ->get(),

            default => $query->paginate(10),
        };
    }


    //  DELIVERY REPORT HANDLER
    private function deliveryReports($query, $report)
    {
        return match($report) {
            'monthly_sales' => $query->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total_amount")
                ->groupBy('month')
                ->orderBy('month')
                ->get(),

            'sales_by_customer' => $query->selectRaw("customer_name, SUM(total_amount) as total_amount")
                ->groupBy('customer_name')
                ->get(),

            'sales_by_item' => $query->selectRaw("item_description, SUM(total_amount) as total_amount")
                ->groupBy('item_description')
                ->get(),

            default => $query->paginate(10),
        };
    }

    // ==========================================================
    //                      MULTI BATCH
    // ==========================================================

    public function batchPrintSO(Request $request)
    {
        // Validate input
        $request->validate([
            'sales_orders' => 'required|array|min:1',
            'sales_orders.*' => 'exists:sales_orders,id'
        ]);

        $orders = SalesOrder::whereIn('id', $request->sales_orders)->get();

        if ($orders->count() == 0) {
            return back()->with('error', 'No sales orders found.');
        }

        // Use DomPDF
        $pdf = Pdf::loadView('records.sales-orders.batch-print', compact('orders'))
            ->setPaper('letter', 'portrait');

        $filename = "SalesOrders_BatchPrint_" . now()->format('Y-m-d_H-i') . ".pdf";
        return $pdf->download($filename);
    }

    public function batchPrintSalesOrders(Request $request)
    {
        // Validate input
        $request->validate([
            'sales_orders' => 'required|array|min:1',
            'sales_orders.*' => 'exists:sales_orders,id'
        ]);

        $orders = SalesOrder::with(['customer', 'items', 'preparer', 'approver'])
            ->whereIn('id', $request->sales_orders)
            ->get();

        if ($orders->count() == 0) {
            return back()->with('error', 'No sales orders found.');
        }

        // Use DomPDF
        $pdf = Pdf::loadView('records.sales-orders.batch-print', compact('orders'))
            ->setPaper('legal', 'portrait');

        return $pdf->download('SalesOrders_BatchPrint_' . now()->format('Ymd_His') . '.pdf');
    }


    // ==========================================================
    //                  EXPORT EXCEL (CSV)
    // ==========================================================

    public function exportExcel(Request $request)
    {
        $type   = $request->type;
        $report = $request->report;

        // =============================
        // EXPORT FOR DELIVERIES
        // =============================
        if ($type === 'deliveries') {

            $query = Deliveries::latest();

            // Only cancelled deliveries (your requirement)
            if ($report === 'cancelled_so') {
                $query->where('status', 'Cancelled');
            }

            $records = $query->get();

            $filename = 'deliveries_' . $report . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Content-Type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=\"$filename\"",
                "Expires"             => "0",
                "Pragma"              => "public",
            ];

            $callback = function() use ($records) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

                // CSV HEADER
                fputcsv($file, [
                    'SO Number',
                    'DR Number',
                    'Customer Code',
                    'Customer Name',
                    'Branch',
                    'Sales Representative',
                    'Sales Executive',
                    'Plate No.',
                    'Sales Invoice No.',
                    'PO Number',
                    'Request Delivery Date',
                    'Status',
                    'Approved By',
                    'Additional Instructions',
                ]);

                // CSV DATA
                foreach ($records as $r) {
                    fputcsv($file, [
                        $r->sales_order_number,
                        $r->dr_no,
                        $r->customer_code,
                        $r->customer_name,
                        $r->branch,
                        $r->sales_representative,
                        $r->sales_executive,
                        $r->plate_no,
                        $r->sales_invoice_no,
                        $r->po_number,
                        $r->request_delivery_date,
                        $r->status,
                        optional($r->approver)->name,
                        $r->additional_instructions,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // =============================
        // EXPORT FOR SALES ORDERS
        // =============================
        if ($type !== 'sales_orders') abort(404);

        $query = SalesOrder::latest();
        $records = $this->salesOrderReports($query, $report);

        $filename = $report . '_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        $callback = function() use ($records, $report) {

            $file = fopen('php://output','w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            switch($report) {
                case 'cancelled_so':
                    fputcsv($file, ['SO Number', 'Customer Name', 'Branch', 'Item Description', 'Total Amount']);
                    foreach ($records as $r) {
                        fputcsv($file, [
                            $r->sales_order_number,
                            $r->customer_name,
                            $r->branch,
                            $r->item_description,
                            $r->total_amount
                        ]);
                    }
                    break;

                case 'top_customers':
                case 'sales_by_customer':
                    fputcsv($file, ['Customer Name', 'Total Amount']);
                    foreach ($records as $r) {
                        fputcsv($file, [$r->customer_name, $r->total_amount]);
                    }
                    break;

                case 'monthly_sales':
                    fputcsv($file, ['Month', 'Total Amount']);
                    foreach ($records as $r) {
                        fputcsv($file, [$r->month, $r->total_amount]);
                    }
                    break;

                case 'sales_by_item':
                    fputcsv($file, ['Item Description', 'Total Amount']);
                    foreach ($records as $r) {
                        fputcsv($file, [$r->item_description, $r->total_amount]);
                    }
                    break;

                default:
                    // For paginated default view
                    fputcsv($file, ['SO Number', 'Customer Name', 'Branch', 'Item Description', 'Total Amount', 'Status']);
                    foreach ($records as $r) {
                        fputcsv($file, [
                            $r->sales_order_number,
                            $r->customer_name,
                            $r->branch,
                            $r->item_description,
                            $r->total_amount,
                            $r->status
                        ]);
                    }
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }




    // ==========================================================
    //                     INDIVIDUAL RECORDS
    // ==========================================================

    public function so_show($id)
    {
        $salesOrder = SalesOrder::findOrFail($id);
        return view('records.so_show', compact('salesOrder'));
    }

    public function dshow($id)
    {
        $delivery = Deliveries::findOrFail($id);
        return view('records.dshow', compact('delivery'));
    }
}