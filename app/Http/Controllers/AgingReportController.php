<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\ARLedger;
use App\Models\Deliveries;
use App\Models\ArAging;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AgingReportController extends Controller
{
    /**
     * Display the aging reports view
     * ✅ UPDATED: Filter by record_date and include_flag
     * ✅ NEW: Dynamic age calculation based on filter_date
     */
    public function view(Request $request)
    {
        // Check if we have imported AR Aging data
        $hasImportedData = ArAging::count() > 0;

        if ($hasImportedData) {
            // Use imported AR Aging data
            $agingReports = $this->getImportedAgingData($request);
        } else {
            // Get aging date for calculated data
            $agingDate = $request->filled('filter_date')
                ? Carbon::parse($request->filter_date)
                : now();

            // Fallback to calculated data from sales invoices
            $query = $this->buildAgingQuery($request);
            $agingReports = $query->get()->map(function($invoice) use ($agingDate) {
                return $this->calculateInvoiceAging($invoice, $agingDate);
            });
        }

        return view('aging_reports.view', compact('agingReports', 'hasImportedData'));
    }

    /**
     * ✅ UPDATED: Get imported AR Aging data with proper filtering
     * Shows ALL records but separates outstanding vs closed
     * ✅ NEW: Dynamically calculates age based on filter_date (aging_date)
     */
    private function getImportedAgingData(Request $request)
    {
        $query = ArAging::query();

        // Get the aging date to use for age calculation (separate from filter_date)
        // If no aging_date is provided, use today's date (ages auto-increment daily)
        $agingDate = $request->filled('aging_date')
            ? Carbon::parse($request->aging_date)
            : now();

        // Debug logging
        Log::info('getImportedAgingData called', [
            'aging_date_param' => $request->input('aging_date'),
            'aging_date_filled' => $request->filled('aging_date'),
            'aging_date_used' => $agingDate->format('Y-m-d'),
        ]);

        // Apply date filter on record_date if provided
        if ($request->filled('filter_date')) {
            $filterDate = Carbon::parse($request->filter_date);
            $query->whereDate('record_date', '<=', $filterDate);
        }

        // Apply include filter - ONLY show records matching the selection
        if ($request->filled('include') && $request->include !== 'all') {
            $query->where('include_flag', $request->include);
        }

        return $query->orderBy('record_date', 'desc')
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function($record, $index) use ($agingDate, $request) {
                // ✅ Calculate age based on counter_date (days passed since counter_date)
                // Use counter_date if available, otherwise fall back to due_date or invoice_date
                $baseDate = null;
                if ($record->counter_date) {
                    $baseDate = Carbon::parse($record->counter_date)->startOfDay();
                } elseif ($record->due_date) {
                    $baseDate = Carbon::parse($record->due_date)->startOfDay();
                } elseif ($record->invoice_date) {
                    $baseDate = Carbon::parse($record->invoice_date)->startOfDay();
                }

                if ($baseDate) {
                    // If aging_date is provided, use it; otherwise use the record's own record_date
                    if ($request->filled('aging_date')) {
                        $agingDateForCalc = $agingDate->copy()->startOfDay();
                    } else {
                        // Use record_date from the database record
                        $agingDateForCalc = $record->record_date
                            ? Carbon::parse($record->record_date)->startOfDay()
                            : now()->startOfDay();
                    }

                    // Age = days passed since base date (signed difference - can be negative if base date is in future)
                    $age = $baseDate->diffInDays($agingDateForCalc, false);

                    // Debug log for first 3 records
                    if ($index < 3) {
                        Log::info("Age calculation (record $index)", [
                            'invoice_no' => $record->invoice_no,
                            'base_date' => $baseDate->format('Y-m-d'),
                            'base_date_source' => $record->counter_date ? 'counter_date' : ($record->due_date ? 'due_date' : 'invoice_date'),
                            'aging_date_param' => $request->input('aging_date'),
                            'aging_date_for_calc' => $agingDateForCalc->format('Y-m-d'),
                            'calculated_age' => $age,
                        ]);
                    }
                } else {
                    // No valid date found, use fallback
                    $age = $record->age ?? 0;
                    if ($index < 3) {
                        Log::info("No valid date (record $index)", [
                            'invoice_no' => $record->invoice_no,
                            'fallback_age' => $age,
                        ]);
                    }
                }
                $ageCategory = $this->getAgeCategory($age);

                return [
                    'id' => $record->id,
                    'aging_date' => $record->aging_date ?? $agingDate->format('Y-m-d'),
                    'counter_date' => $record->counter_date ?? $record->due_date ?? 'N/A',
                    'invoice_date' => $record->invoice_date ? Carbon::parse($record->invoice_date)->format('Y-m-d') : 'N/A',
                    'record_date' => $record->record_date ? Carbon::parse($record->record_date)->format('Y-m-d') : ($record->created_at ? $record->created_at->format('Y-m-d') : 'N/A'),
                    'sales_executive' => $record->sales_executive ?? $record->se2 ?? 'N/A',
                    'customer_name' => $record->client_name ?? $record->customer_code,
                    'customer_code' => $record->customer_code,
                    'invoice_no' => $record->invoice_no ?? 'N/A',
                    'so_dr_po' => $this->formatImportedSoDrPo($record),
                    'invoice_amount' => $record->invoice_amount ?? 0,
                    'settled_amount' => $record->settled_invoice_amount ?? 0,
                    'net_ar' => $record->net_ar_balance ?? 0,
                    'age' => $age, // ✅ Dynamically calculated age
                    'age_category' => $ageCategory, // ✅ Dynamically calculated category
                    'status' => $record->status ?? 'Outstanding',
                    'due_date' => $record->due_date ?? 'N/A',
                    'branch' => $record->branch ?? 'N/A',
                    'terms' => $record->terms ?? 'N/A',
                    'gross_ar_balance' => $record->gross_ar_balance ?? 0,
                    'cwt' => $record->cwt ?? 0,
                    'net_of_cwt' => $record->net_of_cwt ?? 0,
                    'ar_class' => $record->ar_class ?? 'N/A',
                    'include_flag' => $record->include_flag ?? 'yes',
                ];
            });
    }

    /**
     * ✅ NEW: Format SO/DR/PO from imported data
     */
    private function formatImportedSoDrPo($record)
    {
        $parts = [];
        
        if (!empty($record->po_no)) {
            $parts[] = 'PO: ' . $record->po_no;
        }
        
        if (!empty($record->dr_no)) {
            $parts[] = 'DR: ' . $record->dr_no;
        }

        return !empty($parts) ? implode(' | ', $parts) : 'N/A';
    }

    public function search(Request $request)
{
    $customerSearch = $request->input('customer');
    $invoiceSearch = $request->input('invoice');

    // Validate that at least one search parameter is provided
    if (empty($customerSearch) && empty($invoiceSearch)) {
        return response()->json([
            'success' => false,
            'message' => 'Please enter a customer name or invoice number to search.'
        ], 400);
    }

    // Check if we have imported AR Aging data
    $hasImportedData = ArAging::count() > 0;

    if ($hasImportedData) {
        // Get the aging date to use for age calculation (separate from filter_date)
        // If no aging_date is provided, use today's date (ages auto-increment daily)
        $agingDate = $request->filled('aging_date')
            ? Carbon::parse($request->aging_date)
            : now();

        // Search in imported AR Aging data
        $query = ArAging::query();

        // Apply search filters based on what was provided
        $query->where(function($q) use ($customerSearch, $invoiceSearch) {
            if (!empty($customerSearch)) {
                // Search by customer
                $q->where('client_name', 'LIKE', "%{$customerSearch}%")
                  ->orWhere('customer_code', 'LIKE', "%{$customerSearch}%");
            }

            if (!empty($invoiceSearch)) {
                // Search by invoice number
                if (!empty($customerSearch)) {
                    // If both are provided, use OR logic
                    $q->orWhere('invoice_no', 'LIKE', "%{$invoiceSearch}%");
                } else {
                    // If only invoice search is provided
                    $q->where('invoice_no', 'LIKE', "%{$invoiceSearch}%");
                }
            }
        });

        // Apply date filter on record_date
        if ($request->filled('filter_date')) {
            $filterDate = Carbon::parse($request->filter_date);
            $query->whereDate('record_date', '<=', $filterDate);
        }

        // Apply include filter
        if ($request->filled('include') && $request->include !== 'all') {
            $query->where('include_flag', $request->include);
        }

        $agingReports = $query->orderBy('record_date', 'desc')
            ->get()
            ->map(function($record) use ($agingDate, $request) {
                // ✅ Calculate age based on counter_date (days passed since counter_date)
                // Use counter_date if available, otherwise fall back to due_date or invoice_date
                $baseDate = null;
                if ($record->counter_date) {
                    $baseDate = Carbon::parse($record->counter_date)->startOfDay();
                } elseif ($record->due_date) {
                    $baseDate = Carbon::parse($record->due_date)->startOfDay();
                } elseif ($record->invoice_date) {
                    $baseDate = Carbon::parse($record->invoice_date)->startOfDay();
                }

                if ($baseDate) {
                    // If aging_date is provided, use it; otherwise use the record's own record_date
                    if ($request->filled('aging_date')) {
                        $agingDateForCalc = $agingDate->copy()->startOfDay();
                    } else {
                        // Use record_date from the database record
                        $agingDateForCalc = $record->record_date
                            ? Carbon::parse($record->record_date)->startOfDay()
                            : now()->startOfDay();
                    }

                    // Age = days passed since base date (signed difference - can be negative if base date is in future)
                    $age = $baseDate->diffInDays($agingDateForCalc, false);
                } else {
                    $age = $record->age ?? 0; // fallback to pre-calculated
                }
                $ageCategory = $this->getAgeCategory($age);

                return [
                    'id' => $record->id,
                    'aging_date' => $record->aging_date ?? $agingDate->format('Y-m-d'),
                    'counter_date' => $record->counter_date ?? $record->due_date ?? 'N/A',
                    'invoice_date' => $record->invoice_date ? Carbon::parse($record->invoice_date)->format('Y-m-d') : 'N/A',
                    'record_date' => $record->record_date ? Carbon::parse($record->record_date)->format('Y-m-d') : 'N/A',
                    'sales_executive' => $record->sales_executive ?? $record->se2 ?? 'N/A',
                    'customer_name' => $record->client_name ?? $record->customer_code,
                    'customer_code' => $record->customer_code,
                    'invoice_no' => $record->invoice_no ?? 'N/A',
                    'so_dr_po' => $this->formatImportedSoDrPo($record),
                    'invoice_amount' => $record->invoice_amount ?? 0,
                    'settled_amount' => $record->settled_invoice_amount ?? 0,
                    'net_ar' => $record->net_ar_balance ?? 0,
                    'age' => $age, // ✅ Dynamically calculated age
                    'age_category' => $ageCategory, // ✅ Dynamically calculated category
                    'status' => $record->status ?? 'Outstanding',
                    'include_flag' => $record->include_flag ?? 'yes',
                ];
            });
    } else {
        // Fallback to sales invoices
        $query = $this->buildAgingQuery($request);

        // Get aging date for calculated data
        $agingDate = $request->filled('filter_date')
            ? Carbon::parse($request->filter_date)
            : now();

        // Apply search filters based on what was provided
        $query->where(function($q) use ($customerSearch, $invoiceSearch) {
            if (!empty($customerSearch)) {
                // Search by customer
                $q->where('customers.customer_name', 'LIKE', "%{$customerSearch}%")
                  ->orWhere('customers.customer_code', 'LIKE', "%{$customerSearch}%");
            }

            if (!empty($invoiceSearch)) {
                // Search by invoice number
                if (!empty($customerSearch)) {
                    // If both are provided, use OR logic
                    $q->orWhere('sales_invoices.invoice_no', 'LIKE', "%{$invoiceSearch}%");
                } else {
                    // If only invoice search is provided
                    $q->where('sales_invoices.invoice_no', 'LIKE', "%{$invoiceSearch}%");
                }
            }
        });

        $agingReports = $query->get()->map(function($invoice) use ($agingDate) {
            return $this->calculateInvoiceAging($invoice, $agingDate);
        });
    }

    // Determine search type for response message
    $searchType = !empty($customerSearch) && !empty($invoiceSearch)
        ? 'customer/invoice'
        : (!empty($customerSearch) ? 'customer' : 'invoice');

    return response()->json([
        'success' => true,
        'data' => $agingReports,
        'search_type' => $searchType,
        'message' => "Found {$agingReports->count()} record(s) matching your search."
    ]);
}

    public function arAging(Request $request)
    {
        try {
            $filterDate = $request->input('filter_date');
            $include = $request->input('include', 'all');

            // Check if we have imported AR Aging data
            $hasImportedData = ArAging::count() > 0;

            if ($hasImportedData) {
                return $this->arAgingFromImported($request);
            }

            // Get aging date for age calculation
            $agingDate = $filterDate ? Carbon::parse($filterDate) : now();

            // Original logic for sales invoices
            $query = SalesInvoice::with(['customer', 'delivery'])
                ->join('customers', DB::raw('BINARY sales_invoices.customer_code'), '=', DB::raw('BINARY customers.customer_code'))
                ->leftJoin('deliveries', 'sales_invoices.dr_id', '=', 'deliveries.id')
                ->select(
                    'sales_invoices.*',
                    'customers.customer_name',
                    'customers.sales_exec_2 as sales_executive',
                    'deliveries.sales_executive as delivery_sales_exec'
                )
                ->where('sales_invoices.ar_status', '!=', 'Paid');

            if ($filterDate) {
                $query->whereDate('sales_invoices.invoice_date', '<=', $filterDate);
            }

            $invoices = $query->get();

            $agingData = [];
            $grandTotals = [
                'current' => 0,
                '1_30' => 0,
                '31_60' => 0,
                '61_90' => 0,
                '91_120' => 0,
                'over_120' => 0,
                'total' => 0
            ];

            foreach ($invoices as $invoice) {
                $aging = $this->calculateInvoiceAging($invoice, $agingDate);

                $customerKey = $invoice->customer_code . '_' . ($aging['sales_executive'] ?? 'N/A');

                if (!isset($agingData[$customerKey])) {
                    $agingData[$customerKey] = [
                        'customer_name' => $aging['customer_name'],
                        'sales_executive' => $aging['sales_executive'],
                        'current' => 0,
                        '1_30' => 0,
                        '31_60' => 0,
                        '61_90' => 0,
                        '91_120' => 0,
                        'over_120' => 0,
                        'total' => 0
                    ];
                }

                $netAR = $aging['net_ar'];

                // ✅ Only add to age bucket and total if outstanding (net_ar > 0)
                if ($netAR > 0) {
                    $ageBucket = $this->getAgeBucket($aging['age']);
                    $agingData[$customerKey][$ageBucket] += $netAR;
                    $grandTotals[$ageBucket] += $netAR;

                    // Add to total only if outstanding
                    $agingData[$customerKey]['total'] += $netAR;
                    $grandTotals['total'] += $netAR;
                }
            }

            return response()->json([
                'success' => true,
                'aging_data' => array_values($agingData),
                'grand_totals' => $grandTotals
            ]);

        } catch (\Exception $e) {
            Log::error('AR Aging generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AR Aging report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ UPDATED: Generate AR Aging from imported data with proper filtering
     * ✅ FIXED: Only count outstanding balances in age buckets, but include all in total
     * ✅ NEW: Dynamic age calculation based on filter_date
     */
    private function arAgingFromImported(Request $request)
    {
        try {
            $filterDate = $request->input('filter_date');
            $include = $request->input('include', 'all');

            // Get aging date for age calculation (separate from filter_date)
            // If no aging_date is provided, use today's date (ages auto-increment daily)
            $agingDate = $request->filled('aging_date')
                ? Carbon::parse($request->aging_date)
                : now();

            $query = ArAging::query();

            // Filter by record_date
            if ($filterDate) {
                $filterDate = Carbon::parse($filterDate);
                $query->whereDate('record_date', '<=', $filterDate);
            }

            // Filter by include flag
            if ($include !== 'all') {
                $query->where('include_flag', $include);
            }

            $records = $query->get();

            $agingData = [];
            $grandTotals = [
                'current' => 0,
                '1_30' => 0,
                '31_60' => 0,
                '61_90' => 0,
                '91_120' => 0,
                'over_120' => 0,
                'total' => 0
            ];

            foreach ($records as $record) {
                $salesExec = $record->sales_executive ?? $record->se2 ?? 'N/A';
                $customerKey = $record->customer_code . '_' . $salesExec;

                if (!isset($agingData[$customerKey])) {
                    $agingData[$customerKey] = [
                        'customer_name' => $record->client_name ?? $record->customer_code,
                        'sales_executive' => $salesExec,
                        'current' => 0,
                        '1_30' => 0,
                        '31_60' => 0,
                        '61_90' => 0,
                        '91_120' => 0,
                        'over_120' => 0,
                        'total' => 0
                    ];
                }

                $netAR = $record->net_ar_balance ?? 0;

                // ✅ Calculate age based on counter_date (days passed since counter_date)
                // Use counter_date if available, otherwise fall back to due_date or invoice_date
                $baseDate = null;
                if ($record->counter_date) {
                    $baseDate = Carbon::parse($record->counter_date)->startOfDay();
                } elseif ($record->due_date) {
                    $baseDate = Carbon::parse($record->due_date)->startOfDay();
                } elseif ($record->invoice_date) {
                    $baseDate = Carbon::parse($record->invoice_date)->startOfDay();
                }

                if ($baseDate) {
                    // If aging_date is provided, use it; otherwise use the record's own record_date
                    if ($request->filled('aging_date')) {
                        $agingDateForCalc = $agingDate->copy()->startOfDay();
                    } else {
                        // Use record_date from the database record
                        $agingDateForCalc = $record->record_date
                            ? Carbon::parse($record->record_date)->startOfDay()
                            : now()->startOfDay();
                    }

                    // Age = days passed since base date (signed difference - can be negative if base date is in future)
                    $age = $baseDate->diffInDays($agingDateForCalc, false);
                } else {
                    $age = $record->age ?? 0; // fallback to pre-calculated
                }

                // ✅ Only add to age bucket and total if outstanding (net_ar_balance > 0)
                if ($netAR > 0) {
                    $ageBucket = $this->getAgeBucket($age);
                    $agingData[$customerKey][$ageBucket] += $netAR;
                    $grandTotals[$ageBucket] += $netAR;

                    // Add to total using net AR balance (outstanding amount)
                    $agingData[$customerKey]['total'] += $netAR;
                    $grandTotals['total'] += $netAR;
                }
            }

            return response()->json([
                'success' => true,
                'aging_data' => array_values($agingData),
                'grand_totals' => $grandTotals
            ]);

        } catch (\Exception $e) {
            Log::error('AR Aging from imported data failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AR Aging report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export aging reports to Excel
     * ✅ UPDATED: Apply same filtering
     * ✅ NEW: Dynamic age calculation based on filter_date
     * Shows all records (outstanding + closed)
     */
    public function export(Request $request)
    {
        $filterDate = $request->input('filter_date');
        $include = $request->input('include', 'all');

        if (!$filterDate) {
            return back()->with('error', 'Please select a date filter before exporting.');
        }

        // Check if we have imported data
        $hasImportedData = ArAging::count() > 0;

        if ($hasImportedData) {
            $agingReports = $this->getImportedAgingData($request);
        } else {
            // Get aging date for age calculation
            $agingDate = Carbon::parse($filterDate);

            $query = $this->buildAgingQuery($request);
            $agingReports = $query->get()
                ->map(function($invoice) use ($agingDate) {
                    return $this->calculateInvoiceAging($invoice, $agingDate);
                });
        }

        $filename = 'aging_reports_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($agingReports) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'Aging Date',
                'Counter Date',
                'Invoice Date',
                'Record Date',
                'Sales Executive',
                'Client Name',
                'Invoice No',
                'SO/DR/PO',
                'Invoice Amount',
                'Settled Amount',
                'Net AR',
                'Age',
                'Age Category',
                'Status',
                'Include'
            ]);

            $grandTotal = 0;

            foreach ($agingReports as $report) {
                fputcsv($file, [
                    $report['aging_date'],
                    $report['counter_date'],
                    $report['invoice_date'],
                    $report['record_date'],
                    $report['sales_executive'],
                    $report['customer_name'],
                    $report['invoice_no'],
                    $report['so_dr_po'],
                    number_format($report['invoice_amount'], 2),
                    number_format($report['settled_amount'], 2),
                    number_format($report['net_ar'], 2),
                    $report['age'],
                    $report['age_category'],
                    $report['status'],
                    $report['include_flag'] ?? 'yes'
                ]);

                $grandTotal += $report['net_ar'];
            }

            fputcsv($file, []);
            fputcsv($file, [
                '', '', '', '', '', '', '', '',
                '', 'GRAND TOTAL:', number_format($grandTotal, 2)
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export AR Aging summary to Excel
     * ✅ FIXED: Uses filtered data (only outstanding balances)
     */
    public function exportArAging(Request $request)
    {
        $response = $this->arAging($request);
        $data = json_decode($response->getContent(), true);

        if (!$data['success']) {
            return back()->with('error', 'Failed to generate AR Aging report.');
        }

        $agingData = $data['aging_data'];
        $grandTotals = $data['grand_totals'];

        $filename = 'ar_aging_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($agingData, $grandTotals) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'Client Name',
                'SE',
                'Current',
                '1-30 Days',
                '31-60 Days',
                '61-90 Days',
                '91-120 Days',
                'More than 120 Days',
                'Grand Total'
            ]);

            foreach ($agingData as $row) {
                fputcsv($file, [
                    $row['customer_name'],
                    $row['sales_executive'],
                    number_format($row['current'], 2),
                    number_format($row['1_30'], 2),
                    number_format($row['31_60'], 2),
                    number_format($row['61_90'], 2),
                    number_format($row['91_120'], 2),
                    number_format($row['over_120'], 2),
                    number_format($row['total'], 2)
                ]);
            }

            fputcsv($file, [
                'GRAND TOTAL',
                '',
                number_format($grandTotals['current'], 2),
                number_format($grandTotals['1_30'], 2),
                number_format($grandTotals['31_60'], 2),
                number_format($grandTotals['61_90'], 2),
                number_format($grandTotals['91_120'], 2),
                number_format($grandTotals['over_120'], 2),
                number_format($grandTotals['total'], 2)
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Build the base query for aging reports
     */
    private function buildAgingQuery(Request $request)
    {
        $query = SalesInvoice::with(['customer', 'delivery.salesOrder'])
            ->join('customers', DB::raw('BINARY sales_invoices.customer_code'), '=', DB::raw('BINARY customers.customer_code'))
            ->leftJoin('deliveries', 'sales_invoices.dr_id', '=', 'deliveries.id')
            ->leftJoin('sales_orders', 'sales_invoices.so_id', '=', 'sales_orders.id')
            ->select(
                'sales_invoices.*',
                'customers.customer_name',
                'customers.sales_exec_2 as sales_executive',
                'deliveries.sales_executive as delivery_sales_exec',
                'deliveries.dr_no',
                'sales_orders.sales_order_number',
                'sales_orders.po_number'
            )
            ->where('sales_invoices.ar_status', '!=', 'Paid');

        if ($request->filled('filter_date')) {
            $query->whereDate('sales_invoices.invoice_date', '<=', $request->filter_date);
        }

        if ($request->filled('include')) {
            $include = $request->include;
            
            if ($include === 'yes') {
                // Add your specific criteria
            } elseif ($include === 'no') {
                // Add your specific criteria
            }
        }

        return $query;
    }

    /**
     * Calculate aging information for an invoice
     * ✅ Returns net_ar which is already the outstanding balance
     * ✅ NEW: Accepts optional agingDate parameter for dynamic age calculation
     */
    private function calculateInvoiceAging($invoice, $agingDate = null)
    {
        $settledAmount = ARLedger::where('invoice_id', $invoice->id)
            ->where('transaction_type', 'Payment')
            ->sum('credit');

        $invoiceAmount = $invoice->invoice_amount ?? 0;
        $netAR = $invoiceAmount - $settledAmount;

        $invoiceDate = Carbon::parse($invoice->invoice_date)->startOfDay();

        // ✅ Use provided agingDate or default to now()
        $calculationDate = $agingDate ? Carbon::parse($agingDate)->startOfDay() : now()->startOfDay();
        $age = $invoiceDate->diffInDays($calculationDate);

        $ageCategory = $this->getAgeCategory($age);

        $salesExecutive = $invoice->sales_executive ?? $invoice->delivery_sales_exec ?? 'N/A';

        $status = $netAR <= 0 ? 'Paid' : ($age > 60 ? 'Overdue' : 'Outstanding');

        return [
            'aging_date' => $calculationDate->format('Y-m-d'),
            'counter_date' => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('Y-m-d') : 'N/A',
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'record_date' => $invoice->created_at ? $invoice->created_at->format('Y-m-d') : 'N/A',
            'sales_executive' => $salesExecutive,
            'customer_name' => $invoice->customer_name ?? 'N/A',
            'customer_code' => $invoice->customer_code ?? 'N/A',
            'invoice_no' => $invoice->invoice_no ?? 'N/A',
            'so_dr_po' => $this->formatSoDrPo($invoice),
            'invoice_amount' => $invoiceAmount,
            'settled_amount' => $settledAmount,
            'net_ar' => $netAR,
            'age' => $age, // ✅ Dynamically calculated age
            'age_category' => $ageCategory, // ✅ Dynamically calculated category
            'status' => $status,
            'due_date' => $invoice->due_date ?? 'N/A',
            'invoice_id' => $invoice->id
        ];
    }

    /**
     * Format SO/DR/PO for display
     */
    private function formatSoDrPo($invoice)
    {
        $parts = [];
        
        if (!empty($invoice->sales_order_number)) {
            $parts[] = 'SO: ' . $invoice->sales_order_number;
        }
        
        if (!empty($invoice->dr_no)) {
            $parts[] = 'DR: ' . $invoice->dr_no;
        }
        
        if (!empty($invoice->po_number)) {
            $parts[] = 'PO: ' . $invoice->po_number;
        }

        return !empty($parts) ? implode(' | ', $parts) : 'N/A';
    }

    /**
     * Get age category based on days
     */
    private function getAgeCategory($days)
    {
        if ($days <= 0) return 'Current';
        if ($days <= 30) return '1-30 Days';
        if ($days <= 60) return '31-60 Days';
        if ($days <= 90) return '61-90 Days';
        if ($days <= 120) return '91-120 Days';
        return 'More than 120 Days';
    }

    /**
     * Get age bucket for AR Aging summary
     */
    private function getAgeBucket($days)
    {
        if ($days <= 0) return 'current';
        if ($days <= 30) return '1_30';
        if ($days <= 60) return '31_60';
        if ($days <= 90) return '61_90';
        if ($days <= 120) return '91_120';
        return 'over_120';
    }

    /**
     * ✅ UPDATED: Display AR Aging Summary Page (Pivot Table View)
     * ✅ FIXED: Only count outstanding in age buckets, include all in total
     * ✅ NEW: Dynamic age calculation based on filter_date
     */
    public function summary(Request $request)
    {
        $filterDate = $request->input('filter_date');
        $include = $request->input('include', 'all');

        if (!$filterDate) {
            return redirect()->route('aging_reports.view')
                ->with('error', 'Please select a date filter.');
        }

        $hasImportedData = ArAging::count() > 0;

        if (!$hasImportedData) {
            return redirect()->route('aging_reports.view')
                ->with('error', 'No AR Aging data available. Please import data first.');
        }

        // Get aging date for age calculation (separate from filter_date)
        // If no aging_date is provided, use today's date (ages auto-increment daily)
        $agingDate = $request->filled('aging_date')
            ? Carbon::parse($request->aging_date)
            : now();

        // Build query with filters
        $query = ArAging::query();

        $query->whereDate('record_date', '<=', $agingDate);

        if ($include !== 'all') {
            $query->where('include_flag', $include);
        }

        $records = $query->get();

        // Group by Client and SE2
        $agingSummary = [];
        $grandTotals = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '91_120' => 0,
            'over_120' => 0,
            'total' => 0
        ];

        foreach ($records as $record) {
            $clientName = $record->client_name ?? $record->customer_code;
            $se2 = $record->se2 ?? $record->sales_executive ?? 'N/A';
            $key = $clientName . '|' . $se2;

            if (!isset($agingSummary[$key])) {
                $agingSummary[$key] = [
                    'customer_code' => $record->customer_code, // ✅ NEW: Add customer code for detail page links
                    'client_name' => $clientName,
                    'se2' => $se2,
                    'current' => 0,
                    '1_30' => 0,
                    '31_60' => 0,
                    '61_90' => 0,
                    '91_120' => 0,
                    'over_120' => 0,
                    'total' => 0
                ];
            }

            $netAR = $record->net_ar_balance ?? 0;

            // ✅ Dynamically calculate age based on counter_date
            // Use counter_date if available, otherwise fall back to due_date or invoice_date
            $baseDate = null;
            if ($record->counter_date) {
                $baseDate = Carbon::parse($record->counter_date)->startOfDay();
            } elseif ($record->due_date) {
                $baseDate = Carbon::parse($record->due_date)->startOfDay();
            } elseif ($record->invoice_date) {
                $baseDate = Carbon::parse($record->invoice_date)->startOfDay();
            }

            if ($baseDate) {
                // If aging_date is provided, use it; otherwise use the record's own record_date
                if ($request->filled('aging_date')) {
                    $agingDateForCalc = $agingDate->copy()->startOfDay();
                } else {
                    // Use record_date from the database record
                    $agingDateForCalc = $record->record_date
                        ? Carbon::parse($record->record_date)->startOfDay()
                        : now()->startOfDay();
                }

                // Age = days passed since base date (signed difference - can be negative if base date is in future)
                $age = $baseDate->diffInDays($agingDateForCalc, false);
            } else {
                $age = $record->age ?? 0; // fallback to pre-calculated
            }

            // ✅ Only add to age bucket and total if outstanding
            if ($netAR > 0) {
                $ageBucket = $this->getAgeBucket($age);
                $agingSummary[$key][$ageBucket] += $netAR;
                $grandTotals[$ageBucket] += $netAR;

                // Add to total using net AR balance (outstanding amount)
                $agingSummary[$key]['total'] += $netAR;
                $grandTotals['total'] += $netAR;
            }
        }

        // Convert to array and sort by client name
        $agingSummary = array_values($agingSummary);
        usort($agingSummary, function($a, $b) {
            return strcmp($a['client_name'], $b['client_name']);
        });

        // ✅ NEW: Fetch notifications for AR dashboard
        $notifications = $this->getARNotifications($records, $agingDate);

        return view('aging_reports.summary', compact(
            'agingSummary',
            'grandTotals',
            'filterDate',
            'include',
            'notifications'
        ));
    }

    /**
     * ✅ NEW: Display all invoices for a customer in a specific aging bucket
     */
    public function detail(Request $request)
    {
        $customerCode = $request->route('customer_code');
        $bucket = $request->route('bucket');
        $filterDate = $request->input('filter_date');
        $include = $request->input('include', 'all');

        // Get aging date (default to today)
        $agingDate = $request->filled('aging_date')
            ? Carbon::parse($request->aging_date)
            : now();

        // ✅ FIXED: Search by customer_code OR by first customer found with that code
        // This handles cases where customer_code might be #N/A or different variants
        $query = ArAging::where(function($q) use ($customerCode) {
            $q->where('customer_code', $customerCode)
              ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
        });

        // ✅ Use agingDate for record_date filter (same logic as summary)
        $query->whereDate('record_date', '<=', $agingDate);

        if ($include !== 'all') {
            $query->where('include_flag', $include);
        }

        $records = $query->orderBy('due_date', 'desc')->get();

        // If no records found with the first query, try searching by all records for this customer code
        if ($records->isEmpty()) {
            // Fallback: get all records without the record_date filter, then filter manually
            $records = ArAging::where(function($q) use ($customerCode) {
                $q->where('customer_code', $customerCode)
                  ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
            })->get();
        }

        // Filter records by aging bucket and get details
        $invoices = [];
        $totalAmount = 0;
        $bucketLabel = $this->getBucketLabel($bucket);

        foreach ($records as $record) {
            // Calculate age based on due_date
            $baseDate = Carbon::parse($record->due_date)->startOfDay();
            $agingDateForCalc = $agingDate->copy()->startOfDay();
            $age = $baseDate->diffInDays($agingDateForCalc, false);

            // Check if this record belongs to the requested bucket
            if ($this->getAgeBucket($age) === $bucket) {
                $netAR = $record->net_ar_balance ?? 0;
                // Include all records, even with $0 balance to show complete history
                $invoices[] = [
                    'invoice_no' => $record->invoice_no,
                    'dr_no' => $record->dr_no,
                    'po_no' => $record->po_no,
                    'invoice_date' => $record->invoice_date,
                    'due_date' => $record->due_date,
                    'terms' => $record->terms,
                    'days_overdue' => max(0, $age),
                    'invoice_amount' => $record->invoice_amount,
                    'settled_amount' => $record->settled_invoice_amount,
                    'net_ar_balance' => $netAR,
                    'status' => $record->status,
                    'ar_class' => $record->ar_class,
                    'branch' => $record->branch
                ];
                if ($netAR > 0) {
                    $totalAmount += $netAR;
                }
            }
        }

        // Get customer name
        $customerName = $records->first()?->client_name ?? $customerCode;

        return view('aging_reports.detail', compact(
            'customerCode',
            'customerName',
            'bucket',
            'bucketLabel',
            'invoices',
            'totalAmount',
            'filterDate',
            'include'
        ));
    }

    /**
     * Get label for aging bucket
     */
    private function getBucketLabel($bucket)
    {
        return match($bucket) {
            'current' => 'Current (Not Yet Due)',
            '1_30' => '1-30 Days Overdue',
            '31_60' => '31-60 Days Overdue',
            '61_90' => '61-90 Days Overdue',
            '91_120' => '91-120 Days Overdue',
            'over_120' => 'More than 120 Days Overdue',
            default => 'Unknown'
        };
    }

    /**
     * ✅ NEW: Get AR notifications for dashboard alerts
     */
    private function getARNotifications($records, $agingDate)
    {
        $today = $agingDate->copy()->startOfDay();
        $in5Days = $today->copy()->addDays(5);
        $in7Days = $today->copy()->addDays(7);

        $dueSoon = []; // Current bucket + due in 5-7 days
        $justOverdue = []; // 1-30 days overdue
        $seriouslyOverdue = []; // 61+ days overdue

        foreach ($records as $record) {
            $netAR = $record->net_ar_balance ?? 0;
            if ($netAR <= 0) continue; // Skip settled invoices

            $dueDate = Carbon::parse($record->due_date)->startOfDay();
            $daysDiff = $dueDate->diffInDays($today, false); // Negative if future, positive if past

            // Check if invoice is due soon (5-7 days from now)
            if ($daysDiff >= -7 && $daysDiff <= -5 && count($dueSoon) < 5) {
                $dueSoon[] = [
                    'invoice_no' => $record->invoice_no,
                    'customer' => $record->client_name ?? $record->customer_code,
                    'due_date' => $dueDate,
                    'amount' => $netAR,
                    'days_until_due' => abs($daysDiff)
                ];
            }

            // Check if invoice just became overdue (1-30 days)
            if ($daysDiff >= 1 && $daysDiff <= 30 && count($justOverdue) < 5) {
                $justOverdue[] = [
                    'invoice_no' => $record->invoice_no,
                    'customer' => $record->client_name ?? $record->customer_code,
                    'due_date' => $dueDate,
                    'amount' => $netAR,
                    'days_overdue' => $daysDiff
                ];
            }

            // Check if invoice is seriously overdue (61+ days)
            if ($daysDiff >= 61 && count($seriouslyOverdue) < 5) {
                $seriouslyOverdue[] = [
                    'invoice_no' => $record->invoice_no,
                    'customer' => $record->client_name ?? $record->customer_code,
                    'due_date' => $dueDate,
                    'amount' => $netAR,
                    'days_overdue' => $daysDiff
                ];
            }
        }

        return [
            'due_soon' => $dueSoon,
            'just_overdue' => $justOverdue,
            'seriously_overdue' => $seriouslyOverdue
        ];
    }

private function getEmptyAgingSummary()
{
    return [
        'current' => 0,
        '1_30' => 0,
        '31_60' => 0,
        '61_90' => 0,
        '91_120' => 0,
        'over_120' => 0,
        'current_percent' => 0,
        '1_30_percent' => 0,
        '31_60_percent' => 0,
        '61_90_percent' => 0,
        '91_120_percent' => 0,
        'over_120_percent' => 0,
    ];
}

/**
 * Helper method for empty financial summary
 */
private function getEmptyFinancialSummary()
{
    return [
        'net_ar' => 0,
        'invoice_amount' => 0,
        'ar_adjustments' => 0,
        'settled_invoice_amount' => 0,
        'gross_ar_balance' => 0,
        'cwt' => 0,
        'net_of_cwt' => 0,
        'net_ar_balance' => 0,
        'factored_ar_amount' => 0,
    ];
}

public function adjustments()
{
    // TODO: Fetch AR adjustments data
    return view('ar_adjustments.index');
}

   private function logARTransaction($arAgingId, $type, $amount, $date, $reference)
{
    DB::table('ar_transactions')->insert([
        'ar_aging_id' => $arAgingId,
        'transaction_type' => $type,
        'amount' => $amount,
        'transaction_date' => $date,
        'reference_number' => $reference,
        'created_by' => auth()->user()->name ?? 'System',
        'created_at' => now(),
    ]);
}

/**
 * Export AR adjustments to Excel
 */
public function exportAdjustments(Request $request)
{
    // TODO: Implement export logic
    $filename = 'ar_adjustments_' . now()->format('Y-m-d_H-i-s') . '.csv';
    
    return response()->json([
        'success' => true,
        'message' => 'Export functionality coming soon'
    ]);
}

public function exportARProfile(Request $request)
{
    try {
        $customerCode = $request->input('customer_code');
        
        if (!$customerCode) {
            return back()->with('error', 'Customer code is required for export.');
        }

        // Get the AR records for this customer
        $arRecords = ArAging::where('customer_code', $customerCode)
            ->orderBy('invoice_date', 'desc')
            ->get();

        if ($arRecords->isEmpty()) {
            return back()->with('error', 'No records found for this customer.');
        }

        // Get customer info from first record
        $firstRecord = $arRecords->first();
        $customerName = $firstRecord->client_name ?? $customerCode;

        // Calculate totals and aging summary
        $totals = [
            'invoice_amount' => 0,
            'ar_adjustments' => 0,
            'settled_invoice_amount' => 0,
            'gross_ar_balance' => 0,
            'cwt' => 0,
            'net_of_cwt' => 0,
            'net_ar_balance' => 0,
            'factored_ar_amount' => 0,
            'net_ar' => 0,
        ];

        $agingSummary = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '91_120' => 0,
            'over_120' => 0,
        ];

        foreach ($arRecords as $record) {
            $totals['invoice_amount'] += (float) ($record->invoice_amount ?? 0);
            $totals['ar_adjustments'] += (float) ($record->ar_adjustments ?? 0);
            $totals['settled_invoice_amount'] += (float) ($record->settled_invoice_amount ?? 0);
            $totals['gross_ar_balance'] += (float) ($record->gross_ar_balance ?? 0);
            $totals['cwt'] += (float) ($record->cwt ?? 0);
            $totals['net_of_cwt'] += (float) ($record->net_of_cwt ?? 0);
            $totals['net_ar_balance'] += (float) ($record->net_ar_balance ?? 0);
            $totals['factored_ar_amount'] += (float) ($record->factored_ar_amount ?? 0);
            $totals['net_ar'] += (float) ($record->net_ar ?? 0);

            $age = (int) ($record->age ?? 0);
            $netAR = (float) ($record->net_ar ?? 0);

            if ($age <= 30) {
                $agingSummary['current'] += $netAR;
            } elseif ($age <= 60) {
                $agingSummary['1_30'] += $netAR;
            } elseif ($age <= 90) {
                $agingSummary['31_60'] += $netAR;
            } elseif ($age <= 120) {
                $agingSummary['61_90'] += $netAR;
            } elseif ($age <= 150) {
                $agingSummary['91_120'] += $netAR;
            } else {
                $agingSummary['over_120'] += $netAR;
            }
        }

        // Generate filename
        $filename = 'AR_Profile_' . $customerCode . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($arRecords, $customerName, $customerCode, $firstRecord, $totals, $agingSummary) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Customer Information Header
            fputcsv($file, ['CUSTOMER AR PROFILE REPORT']);
            fputcsv($file, []);
            fputcsv($file, ['Customer Information']);
            fputcsv($file, ['Customer Name:', $customerName]);
            fputcsv($file, ['Customer Code:', $customerCode]);
            fputcsv($file, ['Sales Executive:', $firstRecord->sales_executive ?? 'N/A']);
            fputcsv($file, ['SE2:', $firstRecord->se2 ?? 'N/A']);
            fputcsv($file, ['Branch:', $firstRecord->branch ?? 'N/A']);
            fputcsv($file, ['Terms:', $firstRecord->terms ?? 'N/A']);
            fputcsv($file, ['Report Generated:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            // Financial Summary
            fputcsv($file, ['FINANCIAL SUMMARY']);
            fputcsv($file, ['Invoice Amount:', number_format($totals['invoice_amount'], 2)]);
            fputcsv($file, ['AR Adjustments:', number_format($totals['ar_adjustments'], 2)]);
            fputcsv($file, ['Settled Amount:', number_format($totals['settled_invoice_amount'], 2)]);
            fputcsv($file, ['Gross AR Balance:', number_format($totals['gross_ar_balance'], 2)]);
            fputcsv($file, ['CWT:', number_format($totals['cwt'], 2)]);
            fputcsv($file, ['Net of CWT:', number_format($totals['net_of_cwt'], 2)]);
            fputcsv($file, ['Net AR Balance:', number_format($totals['net_ar_balance'], 2)]);
            fputcsv($file, ['Factored AR:', number_format($totals['factored_ar_amount'], 2)]);
            fputcsv($file, ['TOTAL NET AR:', number_format($totals['net_ar'], 2)]);
            fputcsv($file, []);

            // Aging Summary
            fputcsv($file, ['AGING DISTRIBUTION']);
            fputcsv($file, ['Age Category', 'Amount', 'Percentage']);
            $totalNetAR = $totals['net_ar'] > 0 ? $totals['net_ar'] : 1;
            fputcsv($file, ['Current (0-30 days)', number_format($agingSummary['current'], 2), number_format(($agingSummary['current'] / $totalNetAR) * 100, 1) . '%']);
            fputcsv($file, ['1-30 Days', number_format($agingSummary['1_30'], 2), number_format(($agingSummary['1_30'] / $totalNetAR) * 100, 1) . '%']);
            fputcsv($file, ['31-60 Days', number_format($agingSummary['31_60'], 2), number_format(($agingSummary['31_60'] / $totalNetAR) * 100, 1) . '%']);
            fputcsv($file, ['61-90 Days', number_format($agingSummary['61_90'], 2), number_format(($agingSummary['61_90'] / $totalNetAR) * 100, 1) . '%']);
            fputcsv($file, ['91-120 Days', number_format($agingSummary['91_120'], 2), number_format(($agingSummary['91_120'] / $totalNetAR) * 100, 1) . '%']);
            fputcsv($file, ['Over 120 Days', number_format($agingSummary['over_120'], 2), number_format(($agingSummary['over_120'] / $totalNetAR) * 100, 1) . '%']);
            fputcsv($file, []);

            // Detailed Records Header
            fputcsv($file, ['DETAILED AR RECORDS']);
            fputcsv($file, [
                'Invoice No',
                'Aging Date',
                'Counter Date',
                'Invoice Date',
                'Due Date',
                'Record Date',
                'PO No',
                'DR No',
                'Sales Week No',
                'Age (Days)',
                'Age Category',
                'Invoice Amount',
                'AR Adjustments',
                'Settled Amount',
                'Gross AR Balance',
                'CWT',
                'Net of CWT',
                'Net AR Balance',
                'Factored AR',
                'Net AR',
                'Status',
                'AR Class',
                'Include Flag'
            ]);

            // Detailed Records Data
            foreach ($arRecords as $record) {
                $age = $record->age ?? 0;
                
                // Determine age bracket
                if ($age <= 30) {
                    $ageBracket = 'Current (0-30)';
                } elseif ($age <= 60) {
                    $ageBracket = '1-30 Days';
                } elseif ($age <= 90) {
                    $ageBracket = '31-60 Days';
                } elseif ($age <= 120) {
                    $ageBracket = '61-90 Days';
                } elseif ($age <= 150) {
                    $ageBracket = '91-120 Days';
                } else {
                    $ageBracket = 'Over 120 Days';
                }

                fputcsv($file, [
                    $record->invoice_no ?? 'N/A',
                    $record->aging_date ?? 'N/A',
                    $record->counter_date ?? 'N/A',
                    $record->invoice_date ?? 'N/A',
                    $record->due_date ?? 'N/A',
                    $record->record_date ?? 'N/A',
                    $record->po_no ?? 'N/A',
                    $record->dr_no ?? 'N/A',
                    $record->sales_week_no ?? 'N/A',
                    $age,
                    $ageBracket,
                    number_format($record->invoice_amount ?? 0, 2),
                    number_format($record->ar_adjustments ?? 0, 2),
                    number_format($record->settled_invoice_amount ?? 0, 2),
                    number_format($record->gross_ar_balance ?? 0, 2),
                    number_format($record->cwt ?? 0, 2),
                    number_format($record->net_of_cwt ?? 0, 2),
                    number_format($record->net_ar_balance ?? 0, 2),
                    number_format($record->factored_ar_amount ?? 0, 2),
                    number_format($record->net_ar ?? 0, 2),
                    $record->status ?? 'N/A',
                    $record->ar_class ?? 'N/A',
                    $record->include_flag ?? 'yes'
                ]);
            }

            // Grand Total Row
            fputcsv($file, []);
            fputcsv($file, [
                'GRAND TOTAL',
                '', '', '', '', '', '', '', '', '', '',
                number_format($totals['invoice_amount'], 2),
                number_format($totals['ar_adjustments'], 2),
                number_format($totals['settled_invoice_amount'], 2),
                number_format($totals['gross_ar_balance'], 2),
                number_format($totals['cwt'], 2),
                number_format($totals['net_of_cwt'], 2),
                number_format($totals['net_ar_balance'], 2),
                number_format($totals['factored_ar_amount'], 2),
                number_format($totals['net_ar'], 2)
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    } catch (\Exception $e) {
        Log::error('AR Profile export failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()->with('error', 'Failed to export AR Profile: ' . $e->getMessage());
    }
}

public function diagnosticARData($customerCode)
{
    try {
        Log::info('=== AR DATA DIAGNOSTIC START ===');
        Log::info('Customer Code:', ['code' => $customerCode, 'trimmed' => trim($customerCode)]);

        // Check payments table
        $paymentsCount = DB::table('payments')->count();
        $paymentsForCustomer = DB::table('payments')
            ->where('customer_code', $customerCode)
            ->count();
        
        $paymentsSample = DB::table('payments')
            ->where('customer_code', $customerCode)
            ->first();

        // Check all unique customer codes in payments
        $allPaymentCustomers = DB::table('payments')
            ->select('customer_code')
            ->distinct()
            ->limit(10)
            ->pluck('customer_code');

        // Check ar_adjustments table
        $adjustmentsCount = DB::table('ar_adjustments')->count();
        $adjustmentsForCustomer = DB::table('ar_adjustments')
            ->where('customer_code', $customerCode)
            ->count();

        $adjustmentsSample = DB::table('ar_adjustments')
            ->where('customer_code', $customerCode)
            ->first();

        // Check all unique customer codes in adjustments
        $allAdjustmentCustomers = DB::table('ar_adjustments')
            ->select('customer_code')
            ->distinct()
            ->limit(10)
            ->pluck('customer_code');

        $diagnostic = [
            'target_customer_code' => $customerCode,
            'target_customer_code_trimmed' => trim($customerCode),
            'target_customer_code_length' => strlen($customerCode),
            
            'payments' => [
                'total_records' => $paymentsCount,
                'for_this_customer' => $paymentsForCustomer,
                'sample_record' => $paymentsSample,
                'sample_customer_codes' => $allPaymentCustomers,
            ],
            
            'adjustments' => [
                'total_records' => $adjustmentsCount,
                'for_this_customer' => $adjustmentsForCustomer,
                'sample_record' => $adjustmentsSample,
                'sample_customer_codes' => $allAdjustmentCustomers,
            ],
        ];

        Log::info('=== DIAGNOSTIC RESULTS ===', $diagnostic);
        Log::info('=== AR DATA DIAGNOSTIC END ===');

        return response()->json([
            'success' => true,
            'diagnostic' => $diagnostic
        ]);

    } catch (\Exception $e) {
        Log::error('Diagnostic failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

private function fetchCollectionsWithFlexibleMatching($customerCode)
{
    // Strategy 1: Exact match
    $exact = DB::table('payments')
        ->where('customer_code', $customerCode)
        ->get();

    if ($exact->count() > 0) {
        Log::info('Collections found with exact match', ['count' => $exact->count()]);
        return $exact;
    }

    // Strategy 2: Trimmed match
    $trimmed = DB::table('payments')
        ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
        ->get();

    if ($trimmed->count() > 0) {
        Log::info('Collections found with trimmed match', ['count' => $trimmed->count()]);
        return $trimmed;
    }

    // Strategy 3: Case-insensitive match
    $caseInsensitive = DB::table('payments')
        ->whereRaw('LOWER(TRIM(customer_code)) = ?', [strtolower(trim($customerCode))])
        ->get();

    if ($caseInsensitive->count() > 0) {
        Log::info('Collections found with case-insensitive match', ['count' => $caseInsensitive->count()]);
        return $caseInsensitive;
    }

    // Strategy 4: Partial match (LIKE)
    $partial = DB::table('payments')
        ->where('customer_code', 'LIKE', '%' . trim($customerCode) . '%')
        ->get();

    if ($partial->count() > 0) {
        Log::info('Collections found with partial match', ['count' => $partial->count()]);
        return $partial;
    }

    Log::warning('No collections found with any matching strategy', [
        'customer_code' => $customerCode,
        'tried_strategies' => ['exact', 'trimmed', 'case_insensitive', 'partial']
    ]);

    return collect();
}

private function fetchAdjustmentsWithFlexibleMatching($customerCode)
{
    // Strategy 1: Exact match
    $exact = DB::table('ar_adjustments')
        ->where('customer_code', $customerCode)
        ->get();

    if ($exact->count() > 0) {
        Log::info('Adjustments found with exact match', ['count' => $exact->count()]);
        return $exact;
    }

    // Strategy 2: Trimmed match
    $trimmed = DB::table('ar_adjustments')
        ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
        ->get();

    if ($trimmed->count() > 0) {
        Log::info('Adjustments found with trimmed match', ['count' => $trimmed->count()]);
        return $trimmed;
    }

    // Strategy 3: Case-insensitive match
    $caseInsensitive = DB::table('ar_adjustments')
        ->whereRaw('LOWER(TRIM(customer_code)) = ?', [strtolower(trim($customerCode))])
        ->get();

    if ($caseInsensitive->count() > 0) {
        Log::info('Adjustments found with case-insensitive match', ['count' => $caseInsensitive->count()]);
        return $caseInsensitive;
    }

    // Strategy 4: Partial match (LIKE)
    $partial = DB::table('ar_adjustments')
        ->where('customer_code', 'LIKE', '%' . trim($customerCode) . '%')
        ->get();

    if ($partial->count() > 0) {
        Log::info('Adjustments found with partial match', ['count' => $partial->count()]);
        return $partial;
    }

    Log::warning('No adjustments found with any matching strategy', [
        'customer_code' => $customerCode,
        'tried_strategies' => ['exact', 'trimmed', 'case_insensitive', 'partial']
    ]);

    return collect();
}

public function fixPaymentCustomerCodes()
{
    try {
        DB::beginTransaction();
        
        $fixed = 0;
        $failed = 0;
        
        // Get all payments without customer_code
        $payments = DB::table('payments')
            ->whereNull('customer_code')
            ->orWhere('customer_code', '')
            ->get();
        
        Log::info("Found {$payments->count()} payments with missing customer_code");
        
        foreach ($payments as $payment) {
            // Try to find customer from invoice_no in ar_aging
            if (!empty($payment->invoice_no)) {
                $arRecord = DB::table('ar_aging')
                    ->where('invoice_no', $payment->invoice_no)
                    ->first();
                
                if ($arRecord && !empty($arRecord->customer_code)) {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update([
                            'customer_code' => $arRecord->customer_code,
                            'customer_name' => $arRecord->client_name ?? $payment->customer_name,
                        ]);
                    $fixed++;
                    continue;
                }
            }
            
            // Try to find from customer_name match
            if (!empty($payment->customer_name)) {
                $customer = DB::table('customers')
                    ->where('customer_name', 'LIKE', '%' . $payment->customer_name . '%')
                    ->first();
                
                if ($customer) {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update([
                            'customer_code' => $customer->customer_code,
                            'customer_name' => $customer->customer_name,
                        ]);
                    $fixed++;
                    continue;
                }
            }
            
            $failed++;
        }
        
        DB::commit();
        
        Log::info("Payment customer codes fixed", [
            'total' => $payments->count(),
            'fixed' => $fixed,
            'failed' => $failed
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Fixed $fixed payments, $failed could not be matched",
            'total' => $payments->count(),
            'fixed' => $fixed,
            'failed' => $failed
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to fix payment customer codes', [
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage()
        ], 500);
    }
}

public function fixAdjustmentCustomerCodes()
{
    try {
        DB::beginTransaction();
        
        $fixed = 0;
        $failed = 0;
        
        // Get all adjustments without customer_code
        $adjustments = DB::table('ar_adjustments')
            ->whereNull('customer_code')
            ->orWhere('customer_code', '')
            ->get();
        
        Log::info("Found {$adjustments->count()} adjustments with missing customer_code");
        
        foreach ($adjustments as $adjustment) {
            // Try to find customer from invoice_number in ar_aging
            if (!empty($adjustment->invoice_number)) {
                $arRecord = DB::table('ar_aging')
                    ->where('invoice_no', $adjustment->invoice_number)
                    ->first();
                
                if ($arRecord && !empty($arRecord->customer_code)) {
                    DB::table('ar_adjustments')
                        ->where('id', $adjustment->id)
                        ->update([
                            'customer_code' => $arRecord->customer_code,
                            'customer_name' => $arRecord->client_name ?? $adjustment->customer_name,
                        ]);
                    $fixed++;
                    continue;
                }
            }
            
            // Try to find from customer_name match
            if (!empty($adjustment->customer_name)) {
                $customer = DB::table('customers')
                    ->where('customer_name', 'LIKE', '%' . $adjustment->customer_name . '%')
                    ->first();
                
                if ($customer) {
                    DB::table('ar_adjustments')
                        ->where('id', $adjustment->id)
                        ->update([
                            'customer_code' => $customer->customer_code,
                            'customer_name' => $customer->customer_name,
                        ]);
                    $fixed++;
                    continue;
                }
            }
            
            $failed++;
        }
        
        DB::commit();
        
        Log::info("Adjustment customer codes fixed", [
            'total' => $adjustments->count(),
            'fixed' => $fixed,
            'failed' => $failed
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Fixed $fixed adjustments, $failed could not be matched",
            'total' => $adjustments->count(),
            'fixed' => $fixed,
            'failed' => $failed
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to fix adjustment customer codes', [
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage()
        ], 500);
    }
}

public function fixAllCustomerCodes()
{
    $paymentsResult = $this->fixPaymentCustomerCodes();
    $adjustmentsResult = $this->fixAdjustmentCustomerCodes();
    
    return response()->json([
        'success' => true,
        'payments' => json_decode($paymentsResult->getContent()),
        'adjustments' => json_decode($adjustmentsResult->getContent())
    ]);
}


public function debugAdjustments($customerCode)
{
    $arRecord = ArAging::where('customer_code', $customerCode)->first();
    
    if (!$arRecord) {
        return response()->json([
            'error' => 'Customer not found in ar_aging',
            'customer_code' => $customerCode
        ]);
    }

    // Check adjustments table
    $adjustments = DB::table('ar_adjustments')
        ->where('customer_code', $customerCode)
        ->get();

    $adjustmentsByInvoice = DB::table('ar_adjustments')
        ->whereIn('invoice_number', ArAging::where('customer_code', $customerCode)->pluck('invoice_no'))
        ->get();

    // Check all unique customer codes in ar_adjustments
    $allCustomerCodes = DB::table('ar_adjustments')
        ->select('customer_code')
        ->distinct()
        ->pluck('customer_code');

    return response()->json([
        'customer_code_searched' => $customerCode,
        'customer_found_in_ar_aging' => $arRecord ? true : false,
        'ar_aging_customer_name' => $arRecord->client_name ?? null,
        'adjustments_by_customer_code' => [
            'count' => $adjustments->count(),
            'data' => $adjustments
        ],
        'adjustments_by_invoice_number' => [
            'count' => $adjustmentsByInvoice->count(),
            'data' => $adjustmentsByInvoice
        ],
        'all_customer_codes_in_adjustments' => $allCustomerCodes,
        'sample_ar_invoices' => ArAging::where('customer_code', $customerCode)->pluck('invoice_no')->take(5)
    ]);
}
  public function showARProfile($id)
    {
        try {
            Log::info('AR Profile Request', ['id' => $id]);
            
            // Get record by ID
            $record = ArAging::find($id);
            
            if (!$record) {
                Log::error('AR Profile: Record not found by ID', ['id' => $id]);
                return redirect()->route('aging_reports.view')
                    ->with('error', 'AR record not found for ID: ' . $id);
            }

            Log::info('AR Profile: Record found', [
                'id' => $record->id,
                'customer_code' => $record->customer_code,
                'customer_name' => $record->client_name
            ]);

            // 🔥 CRITICAL FIX: Get AR records for THIS specific customer only
            // Many customers have customer_code = #N/A, so we MUST filter by client_name too!
            $arRecords = ArAging::where(function($query) use ($record) {
                if (!empty($record->customer_code) && $record->customer_code !== '#N/A' && $record->customer_code !== 'N/A') {
                    // Valid customer_code - use it
                    $query->where('customer_code', $record->customer_code);
                } else {
                    // Invalid customer_code - MUST filter by client_name only
                    $query->whereRaw('TRIM(LOWER(client_name)) = ?', [trim(strtolower($record->client_name))]);
                }
            })
            ->orderBy('invoice_date', 'desc')
            ->get();

            Log::info('AR Profile: Fetched AR records', [
                'count' => $arRecords->count(),
                'customer_code' => $record->customer_code,
                'client_name' => $record->client_name
            ]);

            // Get all invoice numbers for this customer
            $invoiceNumbers = $arRecords->pluck('invoice_no')->filter()->toArray();

            // ✅ FIXED: Fetch collections with flexible matching
            $collections = $this->fetchCollectionsFlexible($record->customer_code, $invoiceNumbers, $record->client_name);

            Log::info('Collections fetched', ['count' => $collections->count()]);

            // ✅ FIXED: Fetch adjustments with flexible matching (THIS WAS MISSING)
            $adjustments = $this->fetchAdjustmentsFlexible($record->customer_code, $invoiceNumbers, $record->client_name);

            Log::info('Adjustments fetched', ['count' => $adjustments->count()]);

            // Build transaction history
            $transactionHistory = $this->buildTransactionHistory(
                $record->customer_code, 
                $arRecords, 
                $collections, 
                $adjustments
            );

            // Customer info
            $customerInfo = [
                'customer_name' => $record->client_name ?? 'N/A',
                'customer_code' => $record->customer_code,
                'sales_executive' => $record->sales_executive ?? 'N/A',
                'se2' => $record->se2 ?? 'N/A',
                'branch' => $record->branch ?? 'N/A',
                'terms' => $record->terms ?? 'N/A',
            ];

            // Calculate financial summary
            $financialSummary = $this->calculateFinancialSummary(
                $arRecords, 
                $collections, 
                $adjustments
            );

            // Calculate aging summary
            $agingSummary = $this->calculateAgingSummary($arRecords);

            Log::info('AR Profile Success', [
                'customer_code' => $record->customer_code,
                'ar_records' => $arRecords->count(),
                'collections' => $collections->count(),
                'adjustments' => $adjustments->count(),
                'transaction_history' => count($transactionHistory),
            ]);

            return view('aging_reports.ar_profile', [
                'customerInfo' => $customerInfo,
                'arRecords' => $arRecords,
                'collections' => $collections,
                'adjustments' => $adjustments,
                'transactionHistory' => $transactionHistory,
                'totalAR' => $financialSummary['net_ar_balance'],
                'recordCount' => $arRecords->count(),
                'agingSummary' => $agingSummary,
                'financialSummary' => $financialSummary,
            ]);

        } catch (\Exception $e) {
            Log::error('AR Profile Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('aging_reports.view')
                ->with('error', 'Failed to load AR profile: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Flexible collection fetching (improved from original)
     */
    private function fetchCollectionsFlexible($customerCode, $invoiceNumbers, $customerName)
    {
        Log::info('🔍 Fetching collections for customer', [
            'customer_code' => $customerCode,
            'customer_name' => $customerName
        ]);

        // ✅ HYBRID APPROACH: Match by customer_code OR customer_name
        // Most payments have NULL customer_code, so we need to match by name too
        $query = DB::table('payments');

        if (!empty($customerName)) {
            // ALWAYS match by customer_name (since most payments have NULL customer_code)
            Log::info('Matching collections by customer_name', ['name' => $customerName]);
            $query->where(function($q) use ($customerCode, $customerName) {
                // Match by customer_name (most reliable since payments often have NULL customer_code)
                $q->whereRaw('TRIM(LOWER(customer_name)) = ?', [trim(strtolower($customerName))]);

                // OR match by customer_code if it's valid
                if (!empty($customerCode) && $customerCode !== '#N/A' && $customerCode !== 'N/A') {
                    $q->orWhere(function($subQ) use ($customerCode) {
                        $subQ->where('customer_code', $customerCode)
                             ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
                    });
                }
            });
        } elseif (!empty($customerCode) && $customerCode !== '#N/A' && $customerCode !== 'N/A') {
            // Fallback: only customer_code available
            Log::info('Matching collections by customer_code only', ['code' => $customerCode]);
            $query->where(function($q) use ($customerCode) {
                $q->where('customer_code', $customerCode)
                  ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
            });
        } else {
            Log::warning('No valid identifier for collections');
            return collect();
        }

        $collections = $query->get();
        Log::info('Raw collections fetched', ['count' => $collections->count()]);

        // Remove duplicates and format (no post-filter needed, SQL query is already exact)
        return collect($collections)->unique('id')->map(function($payment) {
            $grossAmount = (float)($payment->gross_amount ?? 0);
            $ewt = (float)($payment->ewt ?? 0);
            $otherAdjustment = (float)($payment->other_adjustment ?? 0);
            $factoring = (float)($payment->factoring ?? 0);
            $checkAmount = (float)($payment->check_amount ?? ($grossAmount - $ewt - $otherAdjustment - $factoring));

            return [
                'id' => $payment->id,
                'deposit_date' => $payment->deposit_date ?? $payment->payment_date ?? $payment->created_at,
                'collection_receipt_number' => $payment->collection_receipt_number ?? $payment->cr_number ?? 'N/A',
                'dr_no' => $payment->dr_no ?? null,
                'invoice_no' => $payment->invoice_no ?? null,
                'client_name' => $payment->customer_name ?? $payment->client_name ?? 'N/A',
                'branch' => $payment->branch ?? 'N/A',
                'gross_amount' => $grossAmount,
                'ewt' => $ewt,
                'other_adjustment' => $otherAdjustment,
                'factoring' => $factoring,
                'check_amount' => $checkAmount,
                'checking_si' => $payment->checking_si ?? null,
                'week_no' => $payment->week_no ?? null,
                'ar_class' => $payment->ar_class ?? 'N/A',
                'remarks' => $payment->remarks ?? $payment->payment_notes ?? null,
                'data_check' => $payment->data_check ?? null,
                'status' => $payment->status ?? 'Posted',
                'signed_by' => $payment->signed_by ?? $payment->created_by ?? 'N/A',
                'created_by' => $payment->created_by ?? 'System',
                'created_at' => $payment->created_at,
                'payment_posting_date' => $payment->payment_posting_date ?? $payment->deposit_date ?? $payment->payment_date,
                'payment_notes' => $payment->remarks ?? $payment->payment_notes ?? null,
                'net_amount' => $checkAmount,
            ];
        })->values();
    }

    /**
     * ✅ NEW: Flexible adjustment fetching (THIS WAS THE MISSING PIECE!)
     */
    private function fetchAdjustmentsFlexible($customerCode, $invoiceNumbers, $customerName)
    {
        Log::info('🔍 Fetching adjustments for customer', [
            'customer_code' => $customerCode,
            'invoice_count' => count($invoiceNumbers),
            'customer_name' => $customerName
        ]);

        // ✅ HYBRID APPROACH: Match by customer_code OR customer_name
        // Most adjustments have NULL customer_code, so we need to match by name too
        $query = DB::table('ar_adjustments');

        if (!empty($customerName)) {
            // ALWAYS match by customer_name (since most adjustments have NULL customer_code)
            Log::info('Matching adjustments by customer_name', ['name' => $customerName]);
            $query->where(function($q) use ($customerCode, $customerName) {
                // Match by customer_name (most reliable since adjustments often have NULL customer_code)
                $q->whereRaw('TRIM(LOWER(customer_name)) = ?', [trim(strtolower($customerName))]);

                // OR match by customer_code if it's valid
                if (!empty($customerCode) && $customerCode !== '#N/A' && $customerCode !== 'N/A') {
                    $q->orWhere(function($subQ) use ($customerCode) {
                        $subQ->where('customer_code', $customerCode)
                             ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
                    });
                }
            });
        } elseif (!empty($customerCode) && $customerCode !== '#N/A' && $customerCode !== 'N/A') {
            // Fallback: only customer_code available
            Log::info('Matching adjustments by customer_code only', ['code' => $customerCode]);
            $query->where(function($q) use ($customerCode) {
                $q->where('customer_code', $customerCode)
                  ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
            });
        } else {
            Log::warning('No valid identifier for adjustments');
            return collect();
        }

        $adjustments = $query->get();
        Log::info('Raw adjustments fetched', ['count' => $adjustments->count()]);

        // Remove duplicates and format (no post-filter needed, SQL query is already exact)
        return collect($adjustments)->unique('id')->map(function($adj) {
            $amount = (float)($adj->amount ?? 0);
            $isDecrease = $amount < 0;

            return [
                'id' => $adj->id,
                'reference_number' => $adj->reference_number ?? 'N/A',
                'transaction_type' => $adj->transaction_type ?? 'Adjustment',
                'transaction_date' => $adj->transaction_date ?? $adj->created_at,
                'amount' => $amount,
                'is_decrease' => $isDecrease,
                'invoice_number' => $adj->invoice_number ?? null,
                'dr_no' => $adj->dr_no ?? null,
                'gl_account' => $adj->gl_account ?? 'N/A',
                'signed_by' => $adj->signed_by ?? 'N/A',
                'remarks' => $adj->remarks ?? null,
                'created_by' => $adj->created_by ?? 'System',
                'created_at' => $adj->created_at,
            ];
        })->values();
    }

    /**
     * ✅ Build complete transaction history
     */
    private function buildTransactionHistory($customerCode, $arRecords, $collections, $adjustments)
    {
        $history = [];

        // Add invoices
        foreach ($arRecords as $ar) {
            $history[] = [
                'date' => $ar->invoice_date ?? $ar->record_date ?? $ar->created_at,
                'type' => 'Invoice',
                'reference' => $ar->invoice_no ?? 'N/A',
                'description' => "Invoice #{$ar->invoice_no} created",
                'debit' => (float)($ar->invoice_amount ?? 0),
                'credit' => 0,
                'balance' => null,
                'created_by' => 'System',
            ];
        }

        // Add collections
        foreach ($collections as $coll) {
            $history[] = [
                'date' => $coll['deposit_date'],
                'type' => 'Collection',
                'reference' => $coll['collection_receipt_number'],
                'description' => "Payment received - CR #{$coll['collection_receipt_number']}" . 
                               ($coll['invoice_no'] ? " for Invoice #{$coll['invoice_no']}" : '') .
                               ($coll['remarks'] ? " - {$coll['remarks']}" : ''),
                'debit' => 0,
                'credit' => (float)$coll['check_amount'],
                'balance' => null,
                'created_by' => $coll['created_by'],
            ];
        }

        // Add adjustments
        foreach ($adjustments as $adj) {
            $amount = (float)$adj['amount'];
            $isDecrease = $amount < 0;
            
            $history[] = [
                'date' => $adj['transaction_date'],
                'type' => 'Adjustment',
                'reference' => $adj['reference_number'],
                'description' => "{$adj['transaction_type']}" . 
                               ($adj['invoice_number'] ? " - Invoice: {$adj['invoice_number']}" : '') .
                               ($adj['remarks'] ? " - {$adj['remarks']}" : ''),
                'debit' => $isDecrease ? 0 : abs($amount),
                'credit' => $isDecrease ? abs($amount) : 0,
                'balance' => null,
                'created_by' => $adj['created_by'],
            ];
        }

        // Sort by date (oldest first)
        usort($history, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        // Calculate running balance
        $runningBalance = 0;
        foreach ($history as &$entry) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['balance'] = $runningBalance;
        }

        // Reverse to show newest first
        return array_reverse($history);
    }

    /**
     * ✅ Calculate financial summary
     */
    private function calculateFinancialSummary($arRecords, $collections, $adjustments)
    {
        $summary = [
            'original_invoice_amount' => 0,
            'total_collections' => 0,
            'ewt_total' => 0,
            'total_adjustments_increase' => 0,
            'total_adjustments_decrease' => 0,
            'net_adjustments' => 0,
            'net_ar_balance' => 0,
        ];

        foreach ($arRecords as $record) {
            $summary['original_invoice_amount'] += (float)($record->invoice_amount ?? 0);
            $summary['net_ar_balance'] += (float)($record->net_ar_balance ?? 0);
        }

        foreach ($collections as $coll) {
            $summary['total_collections'] += (float)$coll['check_amount'];
            $summary['ewt_total'] += (float)$coll['ewt'];
        }

        foreach ($adjustments as $adj) {
            $amount = (float)$adj['amount'];
            if ($amount < 0) {
                $summary['total_adjustments_decrease'] += abs($amount);
            } else {
                $summary['total_adjustments_increase'] += $amount;
            }
            $summary['net_adjustments'] += $amount;
        }

        return $summary;
    }

    /**
     * ✅ Calculate aging summary
     */
    private function calculateAgingSummary($arRecords)
    {
        $summary = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '91_120' => 0,
            'over_120' => 0,
        ];

        foreach ($arRecords as $record) {
            $age = (int)($record->age ?? 0);
            $netAR = (float)($record->net_ar_balance ?? 0);

            if ($netAR <= 0) continue;

            if ($age <= 30) {
                $summary['current'] += $netAR;
            } elseif ($age <= 60) {
                $summary['1_30'] += $netAR;
            } elseif ($age <= 90) {
                $summary['31_60'] += $netAR;
            } elseif ($age <= 120) {
                $summary['61_90'] += $netAR;
            } elseif ($age <= 150) {
                $summary['91_120'] += $netAR;
            } else {
                $summary['over_120'] += $netAR;
            }
        }

        $totalNetAR = array_sum($summary);
        if ($totalNetAR > 0) {
            foreach (['current', '1_30', '31_60', '61_90', '91_120', 'over_120'] as $key) {
                $summary["{$key}_percent"] = round(($summary[$key] / $totalNetAR) * 100, 1);
            }
        } else {
            foreach (['current', '1_30', '31_60', '61_90', '91_120', 'over_120'] as $key) {
                $summary["{$key}_percent"] = 0;
            }
        }

        return $summary;
    }

    /**
     * ✅ Store collection with AR update
     */
    public function storeCollection(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_code' => 'required|string',
                'customer_name' => 'required|string',
                'deposit_date' => 'required|date',
                'collection_receipt_number' => 'required|string|unique:payments,collection_receipt_number',
                'dr_no' => 'nullable|string',
                'invoice_no' => 'nullable|string',
                'branch' => 'nullable|string',
                'gross_amount' => 'required|numeric|min:0.01',
                'ewt' => 'nullable|numeric|min:0',
                'other_adjustment' => 'nullable|numeric',
                'factoring' => 'nullable|numeric',
                'check_amount' => 'nullable|numeric',
                'checking_si' => 'nullable|string',
                'week_no' => 'nullable|string',
                'ar_class' => 'nullable|string',
                'remarks' => 'nullable|string',
                'data_check' => 'nullable|string',
                'status' => 'nullable|string',
                'signed_by' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $grossAmount = $validated['gross_amount'];
            $ewt = $validated['ewt'] ?? 0;
            $otherAdjustment = $validated['other_adjustment'] ?? 0;
            $factoring = $validated['factoring'] ?? 0;
            $checkAmount = $validated['check_amount'] ?? ($grossAmount - $ewt - $otherAdjustment - $factoring);

            $paymentId = DB::table('payments')->insertGetId([
                'customer_code' => trim($validated['customer_code']),
                'customer_name' => $validated['customer_name'],
                'deposit_date' => $validated['deposit_date'],
                'collection_receipt_number' => $validated['collection_receipt_number'],
                'cr_number' => $validated['collection_receipt_number'],
                'dr_no' => $validated['dr_no'] ?? null,
                'invoice_no' => $validated['invoice_no'] ?? null,
                'branch' => $validated['branch'] ?? null,
                'gross_amount' => $grossAmount,
                'ewt' => $ewt,
                'other_adjustment' => $otherAdjustment,
                'factoring' => $factoring,
                'check_amount' => $checkAmount,
                'checking_si' => $validated['checking_si'] ?? null,
                'week_no' => $validated['week_no'] ?? null,
                'ar_class' => $validated['ar_class'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'data_check' => $validated['data_check'] ?? null,
                'status' => $validated['status'] ?? 'Posted',
                'signed_by' => $validated['signed_by'] ?? auth()->user()->name ?? 'System',
                'payment_date' => $validated['deposit_date'],
                'payment_posting_date' => $validated['deposit_date'],
                'amount' => $checkAmount,
                'created_by' => auth()->user()->name ?? 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Apply to AR
            $this->applyCollectionToAR(
                trim($validated['customer_code']), 
                $checkAmount,
                $validated['deposit_date'],
                $validated['collection_receipt_number']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Collection saved and AR updated successfully!',
                'payment_id' => $paymentId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Collection store failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save collection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Store adjustment with AR update
     */
    public function storeAdjustment(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_code' => 'required|string',
                'customer_name' => 'required|string',
                'transaction_date' => 'required|date',
                'reference_number' => 'required|string|unique:ar_adjustments,reference_number',
                'transaction_type' => 'required|string',
                'invoice_number' => 'nullable|string',
                'amount' => 'required|numeric',
                'gl_account' => 'required|string',
                'signed_by' => 'required|string',
                'remarks' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $adjustmentId = DB::table('ar_adjustments')->insertGetId([
                'customer_code' => trim($validated['customer_code']),
                'customer_name' => $validated['customer_name'],
                'transaction_date' => $validated['transaction_date'],
                'reference_number' => $validated['reference_number'],
                'transaction_type' => $validated['transaction_type'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'amount' => $validated['amount'],
                'gl_account' => $validated['gl_account'],
                'signed_by' => $validated['signed_by'],
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->user()->name ?? 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Apply to AR
            $this->applyAdjustmentToAR(
                trim($validated['customer_code']),
                $validated['amount'],
                $validated['invoice_number'] ?? null,
                $validated['transaction_date'],
                $validated['reference_number']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'AR adjustment created and applied successfully!',
                'adjustment_id' => $adjustmentId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Adjustment store failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Apply collection to AR (FIFO)
     */
    private function applyCollectionToAR($customerCode, $collectionAmount, $paymentDate, $receiptNumber)
    {
        $outstandingRecords = ArAging::where('customer_code', $customerCode)
            ->where('net_ar_balance', '>', 0)
            ->orderBy('invoice_date', 'asc')
            ->get();

        $remainingAmount = $collectionAmount;

        foreach ($outstandingRecords as $record) {
            if ($remainingAmount <= 0) break;

            $amountToApply = min($remainingAmount, $record->net_ar_balance);

            $record->settled_invoice_amount += $amountToApply;
            $record->net_ar_balance -= $amountToApply;
            $record->net_ar -= $amountToApply;
            
            if ($record->net_ar_balance <= 0.01) {
                $record->status = 'Paid';
                $record->net_ar_balance = 0;
                $record->net_ar = 0;
            } else {
                $record->status = 'Partial';
            }

            $record->save();
            $remainingAmount -= $amountToApply;
        }

        return $remainingAmount;
    }

    /**
     * ✅ Apply adjustment to AR
     */
    private function applyAdjustmentToAR($customerCode, $adjustmentAmount, $invoiceNumber, $transactionDate, $referenceNumber)
    {
        $query = ArAging::where('customer_code', $customerCode);

        if ($invoiceNumber) {
            $query->where('invoice_no', $invoiceNumber);
        } else {
            $query->where('net_ar_balance', '>', 0)
                  ->orderBy('invoice_date', 'asc')
                  ->limit(1);
        }

        $record = $query->first();

        if (!$record) {
            throw new \Exception('No matching AR record found for adjustment');
        }

        $record->ar_adjustments += $adjustmentAmount;
        $record->gross_ar_balance += $adjustmentAmount;
        $record->net_ar_balance += $adjustmentAmount;
        $record->net_ar += $adjustmentAmount;

        if ($record->net_ar_balance <= 0.01) {
            $record->status = 'Paid';
            $record->net_ar_balance = 0;
            $record->net_ar = 0;
        } elseif ($record->settled_invoice_amount > 0) {
            $record->status = 'Partial';
        } else {
            $record->status = 'Outstanding';
        }

        $record->save();
    }
}