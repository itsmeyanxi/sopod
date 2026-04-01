<?php

namespace App\Http\Controllers;

use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use App\Models\GlAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JournalVoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalVoucher::withCount('lines');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('jv_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('reference_no', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('jv_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('jv_date', '<=', $request->date_to);
        }

        $vouchers = $query->orderByDesc('jv_date')->orderByDesc('id')->paginate(50);

        $summary = [
            'total'   => JournalVoucher::count(),
            'draft'   => JournalVoucher::draft()->count(),
            'posted'  => JournalVoucher::posted()->count(),
            'void'    => JournalVoucher::where('status', 'Void')->count(),
        ];

        return view('journal_vouchers.index', compact('vouchers', 'summary'));
    }

    public function create()
    {
        $nextJvNumber = $this->generateNextJvNumber();

        $glAccounts = GlAccount::orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'fs_line_item'])
            ->map(function ($account) {
                return [
                    'id'      => $account->id,
                    'code'    => $account->account_code,
                    'name'    => $account->account_name,
                    'display' => $account->account_code . ' - ' . $account->account_name,
                    'search'  => strtolower($account->account_code . ' ' . $account->account_name),
                ];
            });

        return view('journal_vouchers.create', compact('nextJvNumber', 'glAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jv_date'          => 'required|date',
            'transaction_type' => 'required|string',
            'description'      => 'required|string',
            'lines'            => 'required|array|min:2',
            'lines.*.account_code' => 'required|string',
            'lines.*.debit'    => 'nullable|numeric|min:0',
            'lines.*.credit'   => 'nullable|numeric|min:0',
        ]);

        $lines = $request->lines;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float)($line['debit'] ?? 0);
            $totalCredit += (float)($line['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withErrors(['lines' => 'Total Debit must equal Total Credit. Difference: ' . number_format(abs($totalDebit - $totalCredit), 2)])->withInput();
        }

        DB::beginTransaction();
        try {
            $attachmentPath = null;
            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentName = $file->getClientOriginalName();
                $attachmentPath = $file->store('journal_vouchers', 'public');
            }

            $jv = JournalVoucher::create([
                'jv_number'       => $this->generateNextJvNumber(),
                'jv_date'         => $request->jv_date,
                'transaction_type'=> $request->transaction_type,
                'description'     => $request->description,
                'reference_no'    => $request->reference_no,
                'department'      => $request->department,
                'total_debit'     => $totalDebit,
                'total_credit'    => $totalCredit,
                'status'          => 'Draft',
                'remarks'         => $request->remarks,
                'prepared_by'     => $request->prepared_by ?? Auth::user()->name,
                'checked_by'      => $request->checked_by,
                'approved_by'     => $request->approved_by,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'created_by'      => Auth::user()->name,
            ]);

            foreach ($lines as $i => $line) {
                if (empty($line['account_code'])) continue;
                $debit = (float)($line['debit'] ?? 0);
                $credit = (float)($line['credit'] ?? 0);
                if ($debit == 0 && $credit == 0) continue;

                JournalVoucherLine::create([
                    'journal_voucher_id' => $jv->id,
                    'account_code'       => $line['account_code'],
                    'account_name'       => $line['account_name'] ?? null,
                    'line_description'   => $line['line_description'] ?? null,
                    'cost_center'        => $line['cost_center'] ?? null,
                    'debit'              => $debit,
                    'credit'             => $credit,
                    'sort_order'         => $i,
                ]);
            }

            DB::commit();
            return redirect()->route('journal_vouchers.show', $jv->id)->with('success', 'Journal Voucher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create JV: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $voucher = JournalVoucher::with('lines')->findOrFail($id);
        return view('journal_vouchers.show', compact('voucher'));
    }

    public function edit($id)
    {
        $voucher = JournalVoucher::with('lines')->findOrFail($id);

        if ($voucher->status === 'Posted') {
            return redirect()->route('journal_vouchers.show', $id)->with('error', 'Cannot edit a posted Journal Voucher.');
        }

        $glAccounts = GlAccount::orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'fs_line_item'])
            ->map(function ($account) {
                return [
                    'id'      => $account->id,
                    'code'    => $account->account_code,
                    'name'    => $account->account_name,
                    'display' => $account->account_code . ' - ' . $account->account_name,
                    'search'  => strtolower($account->account_code . ' ' . $account->account_name),
                ];
            });

        return view('journal_vouchers.edit', compact('voucher', 'glAccounts'));
    }

    public function update(Request $request, $id)
    {
        $voucher = JournalVoucher::findOrFail($id);

        if ($voucher->status === 'Posted') {
            return redirect()->route('journal_vouchers.show', $id)->with('error', 'Cannot edit a posted Journal Voucher.');
        }

        $request->validate([
            'jv_date'          => 'required|date',
            'transaction_type' => 'required|string',
            'description'      => 'required|string',
            'lines'            => 'required|array|min:2',
            'lines.*.account_code' => 'required|string',
        ]);

        $lines = $request->lines;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float)($line['debit'] ?? 0);
            $totalCredit += (float)($line['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withErrors(['lines' => 'Total Debit must equal Total Credit.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $voucher->update([
                'jv_date'         => $request->jv_date,
                'transaction_type'=> $request->transaction_type,
                'description'     => $request->description,
                'reference_no'    => $request->reference_no,
                'department'      => $request->department,
                'total_debit'     => $totalDebit,
                'total_credit'    => $totalCredit,
                'remarks'         => $request->remarks,
                'prepared_by'     => $request->prepared_by,
                'checked_by'      => $request->checked_by,
                'approved_by'     => $request->approved_by,
            ]);

            // Delete old lines and recreate
            $voucher->lines()->delete();

            foreach ($lines as $i => $line) {
                if (empty($line['account_code'])) continue;
                $debit = (float)($line['debit'] ?? 0);
                $credit = (float)($line['credit'] ?? 0);
                if ($debit == 0 && $credit == 0) continue;

                JournalVoucherLine::create([
                    'journal_voucher_id' => $voucher->id,
                    'account_code'       => $line['account_code'],
                    'account_name'       => $line['account_name'] ?? null,
                    'line_description'   => $line['line_description'] ?? null,
                    'cost_center'        => $line['cost_center'] ?? null,
                    'debit'              => $debit,
                    'credit'             => $credit,
                    'sort_order'         => $i,
                ]);
            }

            DB::commit();
            return redirect()->route('journal_vouchers.show', $voucher->id)->with('success', 'Journal Voucher updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Update failed: ' . $e->getMessage())->withInput();
        }
    }

    public function post($id)
    {
        $voucher = JournalVoucher::with('lines')->findOrFail($id);

        if (!$voucher->is_balanced) {
            return back()->with('error', 'Cannot post: Debit and Credit totals do not match.');
        }

        if ($voucher->lines->isEmpty()) {
            return back()->with('error', 'Cannot post: No journal lines found.');
        }

        $voucher->update([
            'status'    => 'Posted',
            'posted_at' => now(),
        ]);

        return back()->with('success', 'Journal Voucher posted successfully.');
    }

    public function void($id)
    {
        $voucher = JournalVoucher::findOrFail($id);
        $voucher->update(['status' => 'Void']);

        return back()->with('success', 'Journal Voucher voided.');
    }

    public function destroy($id)
    {
        $voucher = JournalVoucher::findOrFail($id);

        if ($voucher->status === 'Posted') {
            return back()->with('error', 'Cannot delete a posted Journal Voucher. Void it instead.');
        }

        $voucher->delete();
        return redirect()->route('journal_vouchers.index')->with('success', 'Journal Voucher deleted.');
    }

    public function print($id)
    {
        $voucher = JournalVoucher::with('lines')->findOrFail($id);
        return view('journal_vouchers.print', compact('voucher'));
    }

    private function generateNextJvNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "JV-{$year}{$month}-";

        $last = JournalVoucher::where('jv_number', 'LIKE', "{$prefix}%")
            ->orderByDesc('jv_number')
            ->value('jv_number');

        $nextNum = 1;
        if ($last) {
            $lastNum = (int)str_replace($prefix, '', $last);
            $nextNum = $lastNum + 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
