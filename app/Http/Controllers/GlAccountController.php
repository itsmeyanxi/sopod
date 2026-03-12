<?php

namespace App\Http\Controllers;

use App\Models\GlAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GlAccountController extends Controller
{
    /**
     * Display list page
     */
    public function index()
    {
        return view('gl_accounts.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('gl_accounts.create');
    }

    /**
     * Get GL accounts via AJAX with search
     */
    public function getAccounts(Request $request)
    {
        try {
            $search = $request->get('search', '');

            $query = GlAccount::query();

            if (!empty($search)) {
                $query->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            }

            $accounts = $query->orderBy('account_code', 'asc')->get();

            return response()->json([
                'success' => true,
                'accounts' => $accounts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch GL accounts', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GL accounts',
            ], 500);
        }
    }

    /**
     * Store new GL account
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_code' => 'required|string|unique:gl_accounts,account_code',
                'account_name' => 'required|string',
                'fs_line_item' => 'nullable|string',
                'fs_notes' => 'nullable|string',
            ]);

            $validated['created_by'] = Auth::user()->name;

            $account = GlAccount::create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'GL account created successfully',
                    'account' => $account,
                ]);
            }

            return redirect()->route('gl_accounts.show', $account->id)
                ->with('success', 'GL account created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create GL account', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create GL account: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show GL account details
     */
    public function show($id)
    {
        try {
            $account = GlAccount::findOrFail($id);
            return view('gl_accounts.show', compact('account'));
        } catch (\Exception $e) {
            Log::error('Failed to load GL account', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('gl_accounts.index')
                ->with('error', 'GL account not found');
        }
    }

    /**
     * Show edit form
     */
    public function editForm($id)
    {
        try {
            $account = GlAccount::findOrFail($id);
            return view('gl_accounts.edit', compact('account'));
        } catch (\Exception $e) {
            Log::error('Failed to load GL account for edit', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('gl_accounts.index')
                ->with('error', 'GL account not found');
        }
    }

    /**
     * Update GL account
     */
    public function update(Request $request, $id)
    {
        try {
            $account = GlAccount::findOrFail($id);

            $validated = $request->validate([
                'account_code' => "required|string|unique:gl_accounts,account_code,{$id}",
                'account_name' => 'required|string',
                'fs_line_item' => 'nullable|string',
                'fs_notes' => 'nullable|string',
            ]);

            $account->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'GL account updated successfully',
                    'account' => $account,
                ]);
            }

            return redirect()->route('gl_accounts.show', $account->id)
                ->with('success', 'GL account updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update GL account', ['id' => $id, 'error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update GL account: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete GL account
     */
    public function destroy($id)
    {
        try {
            $account = GlAccount::findOrFail($id);
            $account->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'GL account deleted successfully',
                ]);
            }

            return redirect()->route('gl_accounts.index')
                ->with('success', 'GL account deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete GL account', ['id' => $id, 'error' => $e->getMessage()]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete GL account: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to delete GL account');
        }
    }

    /**
     * Export GL accounts to CSV
     */
    public function export(Request $request)
    {
        try {
            $search = $request->get('search', '');

            $query = GlAccount::query();

            if (!empty($search)) {
                $query->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            }

            $accounts = $query->orderBy('account_code', 'asc')->get();

            $filename = 'gl_accounts_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($accounts) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Header row
                fputcsv($file, ['Account Code', 'Account Name', 'FS Line Item', 'FS Notes']);

                // Data rows
                foreach ($accounts as $account) {
                    fputcsv($file, [
                        $account->account_code,
                        $account->account_name,
                        $account->fs_line_item,
                        $account->fs_notes,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Failed to export GL accounts', ['error' => $e->getMessage()]);
            return redirect()->route('gl_accounts.index')
                ->with('error', 'Failed to export GL accounts');
        }
    }
}
