<?php

namespace App\Http\Controllers;

use App\Models\ArAging;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class StatementOfAccountController extends Controller
{
    /**
     * List all customers with outstanding balances
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $query = ArAging::select(
                'customer_code',
                DB::raw('MAX(client_name) as client_name'),
                DB::raw('MAX(branch) as branch'),
                DB::raw('MAX(sales_executive) as sales_executive'),
                DB::raw('MAX(terms) as terms'),
                DB::raw('SUM(CAST(invoice_amount AS DECIMAL(15,2))) as total_invoice'),
                DB::raw('SUM(CAST(settled_invoice_amount AS DECIMAL(15,2))) as total_settled'),
                DB::raw('SUM(CASE WHEN CAST(net_ar_balance AS DECIMAL(15,2)) > 0 THEN CAST(net_ar_balance AS DECIMAL(15,2)) ELSE CAST(invoice_amount AS DECIMAL(15,2)) - CAST(COALESCE(cwt,0) AS DECIMAL(15,2)) - CAST(settled_invoice_amount AS DECIMAL(15,2)) + CAST(COALESCE(ewt,0) AS DECIMAL(15,2)) END) as outstanding_balance'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->whereRaw('CASE WHEN CAST(net_ar_balance AS DECIMAL(15,2)) > 0 THEN CAST(net_ar_balance AS DECIMAL(15,2)) ELSE CAST(invoice_amount AS DECIMAL(15,2)) - CAST(COALESCE(cwt,0) AS DECIMAL(15,2)) - CAST(settled_invoice_amount AS DECIMAL(15,2)) + CAST(COALESCE(ewt,0) AS DECIMAL(15,2)) END > 0')
            ->groupBy('customer_code');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->orderBy('outstanding_balance', 'desc')->paginate(25);

        return view('soa.index', compact('customers', 'search'));
    }

    /**
     * Show SOA detail for a specific customer
     */
    public function show($customerCode)
    {
        $today = Carbon::now();

        $records = ArAging::where('customer_code', $customerCode)
            ->whereRaw('CASE WHEN CAST(net_ar_balance AS DECIMAL(15,2)) > 0 THEN CAST(net_ar_balance AS DECIMAL(15,2)) ELSE CAST(invoice_amount AS DECIMAL(15,2)) - CAST(COALESCE(cwt,0) AS DECIMAL(15,2)) - CAST(settled_invoice_amount AS DECIMAL(15,2)) + CAST(COALESCE(ewt,0) AS DECIMAL(15,2)) END > 0')
            ->orderBy('invoice_date', 'asc')
            ->get();

        if ($records->isEmpty()) {
            return redirect()->route('soa.index')->with('error', 'No outstanding records found for this customer.');
        }

        $firstRecord = $records->first();
        $customerName = $firstRecord->client_name;
        $branch = $firstRecord->branch;
        $terms = $firstRecord->terms;
        $salesExec = $firstRecord->sales_executive;

        // Calculate each record's details
        $totalCurrent = 0;
        $totalPastDue = 0;
        $detailRows = [];

        foreach ($records as $record) {
            // Match AR Profile formula: invoice - cwt - settled + ewt
            $netAr = (float)($record->net_ar_balance ?? 0);
            if ($netAr > 0) {
                $outstanding = $netAr;
            } else {
                $outstanding = (float)($record->invoice_amount ?? 0)
                             - (float)($record->cwt ?? 0)
                             - (float)($record->settled_invoice_amount ?? 0)
                             + (float)($record->ewt ?? 0);
            }

            if ($outstanding <= 0) continue;

            $dueDate = $record->due_date ? Carbon::parse($record->due_date) : null;
            $invoiceDate = $record->invoice_date ? Carbon::parse($record->invoice_date) : null;

            // Days outstanding from due date
            $daysOutstanding = 0;
            if ($dueDate) {
                $daysOutstanding = $dueDate->diffInDays($today, false);
            }

            // Current vs Past Due: negative days = not yet due (current), positive = past due
            $current = 0;
            $pastDue = 0;
            if ($daysOutstanding <= 0) {
                $current = $outstanding;
            } else {
                $pastDue = $outstanding;
            }

            $totalCurrent += $current;
            $totalPastDue += $pastDue;

            $detailRows[] = (object)[
                'invoice_date' => $record->invoice_date,
                'due_date' => $record->due_date,
                'soa_date' => $today->format('Y-m-d'),
                'dr_no' => $record->dr_no,
                'invoice_no' => $record->invoice_no,
                'days_outstanding' => (int)$daysOutstanding,
                'current' => $current,
                'past_due' => $pastDue,
                'outstanding' => $outstanding,
            ];
        }

        // Deduct actual collections (payments) not yet reflected in ar_aging settled amounts
        $actualCollections = Payment::where(function ($q) use ($customerCode, $customerName) {
                $q->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
                  ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->sum('gross_amount');

        $derivedCollections = ($records->sum('invoice_amount') - ($totalCurrent + $totalPastDue));
        $collectionsToDeduct = max(0, $actualCollections - $derivedCollections);

        // Adjustments
        $netAdjustments = (float) DB::table('ar_adjustments')
            ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
            ->sum('amount');

        $totalBalance = $totalCurrent + $totalPastDue - $collectionsToDeduct + $netAdjustments;

        // Credit balance (overpayments minus credits already applied)
        $overpayments = Payment::where('overpayment', '>', 0)
            ->where(function ($q) use ($customerCode, $customerName) {
                $q->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
                  ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->get();

        $creditBalance = 0;
        foreach ($overpayments as $op) {
            $usedLegacy = (float) Payment::where('credit_from_payment_id', $op->id)
                ->sum('credit_applied');
            $usedJunction = (float) DB::table('payment_credit_applications')
                ->where('credit_source_payment_id', $op->id)
                ->sum('amount');
            $used = max($usedLegacy, $usedJunction);
            $remaining = (float) $op->overpayment - $used;
            if ($remaining > 0) {
                $creditBalance += $remaining;
            }
        }

        return view('soa.show', compact(
            'customerCode', 'customerName', 'branch', 'terms', 'salesExec',
            'detailRows', 'totalCurrent', 'totalPastDue', 'totalBalance', 'today',
            'collectionsToDeduct', 'netAdjustments', 'creditBalance'
        ));
    }

    /**
     * Export SOA as Excel matching the Statement of Account format
     */
    public function export($customerCode)
    {
        $today = Carbon::now();

        $records = ArAging::where('customer_code', $customerCode)
            ->whereRaw('CASE WHEN CAST(net_ar_balance AS DECIMAL(15,2)) > 0 THEN CAST(net_ar_balance AS DECIMAL(15,2)) ELSE CAST(invoice_amount AS DECIMAL(15,2)) - CAST(COALESCE(cwt,0) AS DECIMAL(15,2)) - CAST(settled_invoice_amount AS DECIMAL(15,2)) + CAST(COALESCE(ewt,0) AS DECIMAL(15,2)) END > 0')
            ->orderBy('invoice_date', 'asc')
            ->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'No outstanding records found.');
        }

        $firstRecord = $records->first();
        $customerName = $firstRecord->client_name;

        // Build detail rows
        $detailRows = [];
        $totalCurrent = 0;
        $totalPastDue = 0;

        foreach ($records as $record) {
            $netAr = (float)($record->net_ar_balance ?? 0);
            if ($netAr > 0) {
                $outstanding = $netAr;
            } else {
                $outstanding = (float)($record->invoice_amount ?? 0)
                             - (float)($record->cwt ?? 0)
                             - (float)($record->settled_invoice_amount ?? 0)
                             + (float)($record->ewt ?? 0);
            }
            if ($outstanding <= 0) continue;

            $dueDate = $record->due_date ? Carbon::parse($record->due_date) : null;
            $daysOutstanding = $dueDate ? (int)$dueDate->diffInDays($today, false) : 0;

            $current = 0;
            $pastDue = 0;
            if ($daysOutstanding <= 0) {
                $current = $outstanding;
            } else {
                $pastDue = $outstanding;
            }

            $totalCurrent += $current;
            $totalPastDue += $pastDue;

            $detailRows[] = [
                'invoice_date' => $record->invoice_date,
                'due_date' => $record->due_date,
                'soa_date' => $today->format('m/d/Y'),
                'dr_no' => $record->dr_no,
                'invoice_no' => $record->invoice_no,
                'days_outstanding' => $daysOutstanding,
                'current' => $current,
                'past_due' => $pastDue,
            ];
        }

        $totalBalance = $totalCurrent + $totalPastDue;

        // Credit balance (overpayments minus credits applied)
        $creditBalance = 0;
        $exportOverpayments = Payment::where('overpayment', '>', 0)
            ->where(function ($q) use ($customerCode, $customerName) {
                $q->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
                  ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->get();
        foreach ($exportOverpayments as $op) {
            $usedLegacy = (float) Payment::where('credit_from_payment_id', $op->id)->sum('credit_applied');
            $usedJunction = (float) DB::table('payment_credit_applications')->where('credit_source_payment_id', $op->id)->sum('amount');
            $remaining = (float) $op->overpayment - max($usedLegacy, $usedJunction);
            if ($remaining > 0) $creditBalance += $remaining;
        }

        // Build Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Statement of Account');

        // Colors
        $darkRed = '8B0000';
        $white = 'FFFFFF';
        $lightGray = 'F5F5F5';

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(24);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);

        // ====== HEADER SECTION ======
        // Row 1: Logo area + Title
        $sheet->setCellValue('A1', 'MEATPLUS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color($darkRed));
        $sheet->setCellValue('G1', 'Statement of Account');
        $sheet->mergeCells('G1:H1');
        $sheet->getStyle('G1:H1')->getFont()->setBold(true)->setSize(14)->setColor(new Color($darkRed))->setItalic(true);
        $sheet->getStyle('G1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Row 2: Bill To + Receivable
        $sheet->setCellValue('A3', 'Bill to');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9);
        $sheet->setCellValue('B3', $customerName);
        $sheet->mergeCells('B3:D3');
        $sheet->getStyle('B3:D3')->getFont()->setBold(true)->setSize(11);

        $sheet->setCellValue('G3', 'Statement Date');
        $sheet->setCellValue('H3', $today->format('F d, Y'));
        $sheet->getStyle('G3')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('H3')->getFont()->setSize(9);
        $sheet->getStyle('H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Row 3: Receivable amount + Statement Period
        $sheet->setCellValue('A4', 'Receivable as of ' . $today->format('M d, Y'));
        $sheet->mergeCells('A4:B4');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(8);
        $sheet->setCellValue('C4', $totalBalance);
        $sheet->getStyle('C4')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('C4')->getFont()->setBold(true)->setSize(10);

        $sheet->setCellValue('G4', 'Statement Period');
        $sheet->setCellValue('H4', $today->format('F Y'));
        $sheet->getStyle('G4')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('H4')->getFont()->setSize(9);
        $sheet->getStyle('H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ====== TABLE HEADER ======
        $headerRow = 6;
        $headers = ['Invoice Date', 'Due Date', 'SOA Date', 'DR No.', 'SI No.', 'Number of Days Outstanding', 'Current', 'Past Due'];

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $i => $col) {
            $sheet->setCellValue("{$col}{$headerRow}", $headers[$i]);
        }

        $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $white], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkRed]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $darkRed]]],
        ]);

        // ====== DATA ROWS ======
        $dataRow = $headerRow + 1;
        foreach ($detailRows as $idx => $row) {
            $r = $dataRow + $idx;
            $sheet->setCellValue("A{$r}", $row['invoice_date'] ? Carbon::parse($row['invoice_date'])->format('n/j/Y') : '');
            $sheet->setCellValue("B{$r}", $row['due_date'] ? Carbon::parse($row['due_date'])->format('n/j/Y') : '');
            $sheet->setCellValue("C{$r}", $row['soa_date']);
            $sheet->setCellValue("D{$r}", $row['dr_no']);
            $sheet->setCellValue("E{$r}", $row['invoice_no']);
            $sheet->setCellValue("F{$r}", $row['days_outstanding']);
            $sheet->setCellValue("G{$r}", $row['current'] > 0 ? $row['current'] : '');
            $sheet->setCellValue("H{$r}", $row['past_due'] > 0 ? $row['past_due'] : '');

            $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$r}:H{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("G{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Alternate row color
            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF5F5');
            }

            $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('DDDDDD');
        }

        // ====== NOTHING FOLLOWS ======
        $nfRow = $dataRow + count($detailRows);
        $sheet->setCellValue("A{$nfRow}", '—Nothing Follows—');
        $sheet->mergeCells("A{$nfRow}:H{$nfRow}");
        $sheet->getStyle("A{$nfRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$nfRow}")->getFont()->setItalic(true)->setSize(9);

        // ====== CURRENT BALANCE ROW ======
        $totRow = $nfRow + 1;
        $sheet->setCellValue("A{$totRow}", 'Current Balance:');
        $sheet->mergeCells("A{$totRow}:F{$totRow}");
        $sheet->getStyle("A{$totRow}")->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle("A{$totRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue("G{$totRow}", $totalCurrent);
        $sheet->setCellValue("H{$totRow}", $totalPastDue);
        $sheet->getStyle("G{$totRow}:H{$totRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("G{$totRow}:H{$totRow}")->getFont()->setBold(true)->setSize(10)->setColor(new Color($darkRed));
        $sheet->getStyle("A{$totRow}:H{$totRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkRed]],
            'font' => ['bold' => true, 'color' => ['rgb' => $white]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $darkRed]]],
        ]);
        $sheet->getStyle("G{$totRow}:H{$totRow}")->getFont()->setColor(new Color($white));

        // ====== CREDIT BALANCE ROW ======
        if ($creditBalance > 0) {
            $creditRow = $totRow + 1;
            $sheet->setCellValue("A{$creditRow}", 'Credit Balance / Overpayment:');
            $sheet->mergeCells("A{$creditRow}:F{$creditRow}");
            $sheet->getStyle("A{$creditRow}")->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle("A{$creditRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("G{$creditRow}", $creditBalance);
            $sheet->mergeCells("G{$creditRow}:H{$creditRow}");
            $sheet->getStyle("G{$creditRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("A{$creditRow}:H{$creditRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                'font' => ['bold' => true, 'color' => ['rgb' => '15803D']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '86EFAC']]],
            ]);
            $totRow = $creditRow;
        }

        // ====== FOOTER ======
        $footRow = $totRow + 2;
        $sheet->setCellValue("A{$footRow}", 'For any comments and questions about this statement of account, please contact');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Meatplus Trading Corporation');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setBold(true)->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Telephone: 244-4618 / 244-4619');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Email: treasury@meatplus.ph');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Suite 1207 Victoria Building, 429 U.N. Avenue, Ermita, Manila');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow += 2;
        $sheet->setCellValue("A{$footRow}", 'If we do not hear from you within three (3) days from receipt of this statement, we will assume that you are in agreement with the outstanding balance.');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(8)->setItalic(true);

        $footRow += 2;
        $sheet->setCellValue("A{$footRow}", 'Prepared by:');
        $sheet->getStyle("A{$footRow}")->getFont()->setBold(true)->setSize(9);
        $sheet->setCellValue("G{$footRow}", 'Noted By:');
        $sheet->getStyle("G{$footRow}")->getFont()->setBold(true)->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", auth()->user()->name ?? 'Treasury');
        $sheet->getStyle("A{$footRow}")->getFont()->setItalic(true)->setSize(9);

        // Print setup
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);

        // Write to temp and stream download
        $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $customerCode);
        $filename = 'SOA_' . $safeCode . '_' . $today->format('Y-m-d') . '.xlsx';
        $tempPath = storage_path('app/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
