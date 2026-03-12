<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\RoleHelper;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    // Show all customers (list)
    public function index()
    {
        $request = request(); // Get the current request
        
        $query = Customer::query();

        // ✅ Filter by flag status
        if ($request->filled('flag_filter')) {
            if ($request->flag_filter === 'flagged') {
                $query->where('is_flagged', true);
            } elseif ($request->flag_filter === 'unflagged') {
                $query->where('is_flagged', false);
            }
        }

        // ✅ Filter by status (enabled/disabled)
        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        // ✅ Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_code', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->orderBy('customer_code')->get();
        return view('customers.index', compact('customers'));
    }

    // Show create form
    public function create()
    {
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        return view('customers.create');
    }

    public function export()
    {
        $customers = Customer::orderBy('customer_code')->get();
        
        $filename = 'customers_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Customer Code',
                'Customer Name',
                'Business Style',
                'Billing Address',
                'TIN',
                'Shipping Address',
                'Status',
                'Flag Status',
                'Created At',
                'Updated At'
            ]);

            // CSV Data
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->id,
                    $customer->customer_code,
                    $customer->customer_name,
                    $customer->business_style ?? 'N/A',
                    $customer->billing_address ?? 'N/A',
                    $customer->tin_no ?? '000-000-000-00000',
                    $customer->shipping_address ?? 'N/A',
                    ucfirst($customer->status),
                    $customer->is_flagged ? 'Flagged' : 'Unflagged',
                    $customer->created_at ? $customer->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $customer->updated_at ? $customer->updated_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

        public function arProfile()
    {
        return view('customers.ar_profile');
    }

    // Save new customer to database
    public function store(Request $request)
    {
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        $validated = $request->validate([
            'customer_code' => 'required|string|max:50|unique:customers,customer_code',
            'customer_name' => 'required|string|max:255',
            'business_style' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'customer_group' => 'nullable|string|max:255',
            'customer_type' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:50',
            'telephone_1' => 'nullable|string|max:50',
            'telephone_2' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'name_of_contact' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'whtrate' => 'nullable|numeric',
            'whtcode' => 'nullable|string|max:50',
            'require_si' => 'nullable|in:yes,no',
            'ar_type' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:50',
            'collection_terms' => 'nullable|string|max:255',
            'sales_rep' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric',
            'assigned_bank' => 'nullable|string|max:255',
        ]);

        $validated['status'] = 'enabled';
        $validated['is_flagged'] = false; // Default to unflagged

        $customer = Customer::create($validated);

        // ✅ Send email notification
        // app(\App\Services\NotificationService::class)->notifyNewItem($customer);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Created',
            'item' => $customer->customer_code . ' - ' . $customer->customer_name,
            'target' => $customer->billing_address ?? 'N/A',
            'type' => 'Customer',
            'message' => 'Added new customer: ' . $customer->customer_name,
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully!');
    }

    // Show single customer details
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.show', compact('customer'));
    }

    // Show edit form
    public function edit($id)
    {
        if (!auth()->user()->canEditCustomers()) {
            return response()->view('errors.noaccess', [], 403);
        }

        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    // Update customer
    public function update(Request $request, $id)
    {
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'customer_code' => 'required|string|max:50|unique:customers,customer_code,' . $id,
            'customer_name' => 'required|string|max:255',
            'business_style' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'customer_group' => 'nullable|string|max:255',
            'customer_type' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:50',
            'telephone_1' => 'nullable|string|max:50',
            'telephone_2' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'name_of_contact' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'whtrate' => 'nullable|numeric',
            'whtcode' => 'nullable|string|max:50',
            'require_si' => 'nullable|in:yes,no',
            'ar_type' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:50',
            'collection_terms' => 'nullable|string|max:255',
            'sales_rep' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric',
            'assigned_bank' => 'nullable|string|max:255',
        ]);

        // Track if collection terms changed
        $oldTerms = $customer->collection_terms;
        $newTerms = $validated['collection_terms'] ?? null;

        $customer->update($validated);

        // Log collection terms change if it occurred
        if ($oldTerms !== $newTerms) {
            \App\Models\CustomerTermsHistory::create([
                'customer_code' => $customer->customer_code,
                'old_terms' => $oldTerms,
                'new_terms' => $newTerms,
                'changed_by' => Auth::user()->name ?? 'System',
            ]);
        }

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Updated',
            'item' => $customer->customer_code . ' - ' . $customer->customer_name,
            'target' => $customer->billing_address ?? 'N/A',
            'type' => 'Customer',
            'message' => 'Updated customer: ' . $customer->customer_name,
        ]);

        return redirect()->route('customers.edit', $customer->id)->with('success', 'Customer updated successfully!');
    }

    // Delete customer
    public function destroy($id)
    {
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        $customer = Customer::findOrFail($id);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Deleted',
            'item' => $customer->customer_code . ' - ' . $customer->customer_name,
            'target' => $customer->billing_address ?? 'N/A',
            'type' => 'Customer',
            'message' => 'Deleted customer: ' . $customer->customer_name,
        ]);

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully!');
    }

    // Get customer by code (API endpoint)
    public function getByCode($code)
    {
        $customer = Customer::where('customer_code', $code)->first();

        if ($customer) {
            return response()->json($customer);
        } else {
            return response()->json(['error' => 'Customer not found'], 404);
        }
    }

    // Toggle customer status
    public function toggleStatus($id)
    {
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        $customer = Customer::findOrFail($id);

        if ($customer->is_locked) {
            return redirect()->back()
                ->with('error', 'This Customer is locked and cannot be modified.');
        }

        $customer->status = $customer->status === 'enabled' ? 'disabled' : 'enabled';
        $customer->save();

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Status Changed',
            'item' => $customer->customer_code . ' - ' . $customer->customer_name,
            'target' => $customer->status,
            'type' => 'Customer',
            'message' => 'Changed customer status to: ' . $customer->status,
        ]);

        return redirect()->back()->with('success', 'Customer status updated successfully!');
    }

    public function toggleFlag($id)
    {
        // Check if user is CC_Approver
        $user = auth()->user();
        if (!$user || !$user->canPerformInModule('can_manage', 'customers')) {
            return redirect()->back()->with('error', 'Unauthorized: Only CC_Approver can flag/unflag customers.');
        }

        $customer = Customer::findOrFail($id);

        if ($customer->is_locked) {
            return redirect()->back()
                ->with('error', 'This Customer is locked and cannot be modified.');
        }

        $customer->is_flagged = !$customer->is_flagged;
        $customer->save();

        $flagStatus = $customer->is_flagged ? 'Flagged' : 'Unflagged';

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Flag Status Changed',
            'item' => $customer->customer_code . ' - ' . $customer->customer_name,
            'target' => $flagStatus,
            'type' => 'Customer',
            'message' => "Changed customer flag status to: {$flagStatus}",
        ]);

        return redirect()->back()->with('success', "Customer {$flagStatus} successfully!");
    }

 /**
 * ✅ STEP 1: Add this method to your CustomerController.php or create a new FixController
 * This will populate missing customer_code in ar_adjustments by matching customer_name
 */

public function fixAdjustmentsCustomerCodes()
{
    try {
        DB::beginTransaction();
        
        $fixed = 0;
        $failed = 0;
        $results = [];
        
        // Get all adjustments with NULL or empty customer_code
        $adjustments = DB::table('ar_adjustments')
            ->whereNull('customer_code')
            ->orWhere('customer_code', '')
            ->get();
        
        Log::info("🔍 Found {$adjustments->count()} adjustments with missing customer_code");
        
        foreach ($adjustments as $adjustment) {
            $customerCode = null;
            $customerName = null;
            $matchStrategy = null;
            
            // Strategy 1: Match by DR number first (PRIORITY - since most have this)
            if (!empty($adjustment->dr_no)) {
                $delivery = DB::table('deliveries')
                    ->where('dr_no', $adjustment->dr_no)
                    ->first();
                
                if ($delivery && !empty($delivery->customer_code)) {
                    $customerCode = $delivery->customer_code;
                    $matchStrategy = 'DR number match from deliveries';
                    
                    // Get customer name too
                    $customer = DB::table('customers')
                        ->where('customer_code', $customerCode)
                        ->first();
                    
                    if ($customer) {
                        $customerName = $customer->customer_name;
                    }
                }
            }
            
            // Strategy 2: Match by invoice_number in ar_aging
            if (!$customerCode && !empty($adjustment->invoice_number)) {
                $arRecord = DB::table('ar_aging')
                    ->where('invoice_no', $adjustment->invoice_number)
                    ->first();
                
                if ($arRecord && !empty($arRecord->customer_code)) {
                    $customerCode = $arRecord->customer_code;
                    $customerName = $arRecord->client_name ?? null;
                    $matchStrategy = 'Invoice match from ar_aging';
                }
            }
            
            // Strategy 3: Match by customer_name from customers table
            if (!$customerCode && !empty($adjustment->customer_name)) {
                // Try exact match first
                $customer = DB::table('customers')
                    ->where('customer_name', $adjustment->customer_name)
                    ->first();
                
                if ($customer) {
                    $customerCode = $customer->customer_code;
                    $customerName = $customer->customer_name;
                    $matchStrategy = 'Exact customer_name match';
                } else {
                    // Try LIKE match (fuzzy)
                    $customer = DB::table('customers')
                        ->where('customer_name', 'LIKE', '%' . trim($adjustment->customer_name) . '%')
                        ->first();
                    
                    if ($customer) {
                        $customerCode = $customer->customer_code;
                        $customerName = $customer->customer_name;
                        $matchStrategy = 'Fuzzy customer_name match';
                    }
                }
            }
            
            // Update if we found a match
            if ($customerCode) {
                DB::table('ar_adjustments')
                    ->where('id', $adjustment->id)
                    ->update([
                        'customer_code' => $customerCode,
                        'customer_name' => $customerName, // Also update customer_name
                        'updated_at' => now()
                    ]);
                
                $fixed++;
                $results[] = [
                    'id' => $adjustment->id,
                    'reference' => $adjustment->reference_number,
                    'customer_name' => $adjustment->customer_name,
                    'matched_code' => $customerCode,
                    'strategy' => $matchStrategy,
                    'status' => 'FIXED ✅'
                ];
                
                Log::info("✅ Fixed adjustment #{$adjustment->id}: {$adjustment->reference_number} -> {$customerCode} ({$matchStrategy})");
            } else {
                $failed++;
                $results[] = [
                    'id' => $adjustment->id,
                    'reference' => $adjustment->reference_number,
                    'customer_name' => $adjustment->customer_name,
                    'matched_code' => null,
                    'strategy' => 'No match found',
                    'status' => 'FAILED ❌'
                ];
                
                Log::warning("❌ Could not match adjustment #{$adjustment->id}: {$adjustment->reference_number}");
            }
        }
        
        DB::commit();
        
        $summary = [
            'total_adjustments' => $adjustments->count(),
            'fixed' => $fixed,
            'failed' => $failed,
            'success_rate' => $adjustments->count() > 0 ? round(($fixed / $adjustments->count()) * 100, 2) . '%' : '0%'
        ];
        
        Log::info('🎯 Fix Summary', $summary);
        
        return response()->json([
            'success' => true,
            'message' => "Fixed {$fixed} adjustments, {$failed} could not be matched",
            'summary' => $summary,
            'details' => $results
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('❌ Failed to fix adjustment customer codes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage()
        ], 500);
    }
}

public function fixAdjustmentsViaArAging()
{
    try {
        DB::beginTransaction();
        
        $fixed = 0;
        $failed = 0;
        $results = [];
        
        // Get all adjustments with NULL customer_code
        $adjustments = DB::table('ar_adjustments')
            ->whereNull('customer_code')
            ->orWhere('customer_code', '')
            ->get();
        
        Log::info("🔍 Found {$adjustments->count()} adjustments to fix");
        
        foreach ($adjustments as $adjustment) {
            $customerCode = null;
            $customerName = null;
            $matchStrategy = null;
            
            // Strategy 1: Match by DR number in ar_aging table
            if (!empty($adjustment->dr_no)) {
                $arRecord = DB::table('ar_aging')
                    ->where('dr_no', $adjustment->dr_no)
                    ->first();
                
                if ($arRecord && !empty($arRecord->customer_code)) {
                    $customerCode = $arRecord->customer_code;
                    $customerName = $arRecord->client_name ?? null;
                    $matchStrategy = 'DR number match from ar_aging';
                }
            }
            
            // Strategy 2: Match by invoice_number in ar_aging
            if (!$customerCode && !empty($adjustment->invoice_number)) {
                $arRecord = DB::table('ar_aging')
                    ->where('invoice_no', $adjustment->invoice_number)
                    ->first();
                
                if ($arRecord && !empty($arRecord->customer_code)) {
                    $customerCode = $arRecord->customer_code;
                    $customerName = $arRecord->client_name ?? null;
                    $matchStrategy = 'Invoice number match from ar_aging';
                }
            }
            
            // Strategy 3: Match by reference_number pattern (CM-YYYY-XXXX might contain invoice info)
            if (!$customerCode && !empty($adjustment->reference_number)) {
                // Try to extract any numbers that might be invoice-related
                // This is a fallback strategy
                $matchStrategy = 'Reference number (no match)';
            }
            
            // Update if we found a match
            if ($customerCode) {
                DB::table('ar_adjustments')
                    ->where('id', $adjustment->id)
                    ->update([
                        'customer_code' => $customerCode,
                        'customer_name' => $customerName,
                        'updated_at' => now()
                    ]);
                
                $fixed++;
                $results[] = [
                    'id' => $adjustment->id,
                    'dr_no' => $adjustment->dr_no,
                    'reference' => $adjustment->reference_number,
                    'matched_code' => $customerCode,
                    'matched_name' => $customerName,
                    'strategy' => $matchStrategy,
                    'status' => 'FIXED ✅'
                ];
                
                Log::info("✅ Fixed adjustment #{$adjustment->id}: DR {$adjustment->dr_no} -> {$customerCode}");
            } else {
                $failed++;
                $results[] = [
                    'id' => $adjustment->id,
                    'dr_no' => $adjustment->dr_no,
                    'reference' => $adjustment->reference_number,
                    'matched_code' => null,
                    'matched_name' => null,
                    'strategy' => 'No match found',
                    'status' => 'FAILED ❌'
                ];
            }
        }
        
        DB::commit();
        
        $summary = [
            'total_adjustments' => $adjustments->count(),
            'fixed' => $fixed,
            'failed' => $failed,
            'success_rate' => $adjustments->count() > 0 ? round(($fixed / $adjustments->count()) * 100, 2) . '%' : '0%'
        ];
        
        Log::info('🎯 Fix Summary', $summary);
        
        return response()->json([
            'success' => true,
            'message' => "Fixed {$fixed} adjustments, {$failed} could not be matched",
            'summary' => $summary,
            'details' => $results
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('❌ Failed to fix adjustments', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage()
        ], 500);
    }
}

public function diagnosticAdjustments()
{
    $adjustmentsWithoutCode = DB::table('ar_adjustments')
        ->whereNull('customer_code')
        ->orWhere('customer_code', '')
        ->get();
    
    $adjustmentsWithCode = DB::table('ar_adjustments')
        ->whereNotNull('customer_code')
        ->where('customer_code', '!=', '')
        ->get();
    
    // Check DR number matches
    $drNumbers = DB::table('ar_adjustments')
        ->whereNull('customer_code')
        ->whereNotNull('dr_no')
        ->select('dr_no')
        ->distinct()
        ->limit(10)
        ->pluck('dr_no');
    
    $drMatchAttempts = [];
    foreach ($drNumbers as $drNo) {
        $delivery = DB::table('deliveries')
            ->where('dr_no', $drNo)
            ->first();
        
        $drMatchAttempts[] = [
            'dr_no' => $drNo,
            'found_in_deliveries' => $delivery ? 'Yes ✅' : 'No ❌',
            'customer_code' => $delivery->customer_code ?? 'N/A',
        ];
    }
    
    // Sample adjustments breakdown
    $withDR = DB::table('ar_adjustments')
        ->whereNull('customer_code')
        ->whereNotNull('dr_no')
        ->count();
    
    $withInvoice = DB::table('ar_adjustments')
        ->whereNull('customer_code')
        ->whereNotNull('invoice_number')
        ->count();
    
    $withCustomerName = DB::table('ar_adjustments')
        ->whereNull('customer_code')
        ->whereNotNull('customer_name')
        ->count();
    
    return response()->json([
        'summary' => [
            'total_adjustments' => DB::table('ar_adjustments')->count(),
            'without_customer_code' => $adjustmentsWithoutCode->count(),
            'with_customer_code' => $adjustmentsWithCode->count(),
            'percentage_missing' => round(($adjustmentsWithoutCode->count() / DB::table('ar_adjustments')->count()) * 100, 2) . '%'
        ],
        'matching_potential' => [
            'have_dr_no' => $withDR . ' adjustments',
            'have_invoice_number' => $withInvoice . ' adjustments',
            'have_customer_name' => $withCustomerName . ' adjustments',
        ],
        'dr_number_test' => [
            'sample_dr_numbers_tested' => $drNumbers->count(),
            'matches' => $drMatchAttempts
        ],
        'sample_adjustments_without_code' => $adjustmentsWithoutCode->take(5),
        'sample_adjustments_with_code' => $adjustmentsWithCode->take(5),
    ]);
}

/**
 * ✅ DIAGNOSTIC: Check if DR numbers exist in ar_aging table
 */
public function diagnosticArAgingMatch()
{
    // Get sample DR numbers from adjustments
    $sampleDRs = DB::table('ar_adjustments')
        ->whereNull('customer_code')
        ->whereNotNull('dr_no')
        ->select('dr_no', 'reference_number')
        ->distinct()
        ->limit(20)
        ->get();
    
    $matchResults = [];
    $totalMatches = 0;
    
    foreach ($sampleDRs as $adj) {
        $arRecord = DB::table('ar_aging')
            ->where('dr_no', $adj->dr_no)
            ->first();
        
        if ($arRecord) {
            $totalMatches++;
        }
        
        $matchResults[] = [
            'adjustment_dr_no' => $adj->dr_no,
            'adjustment_reference' => $adj->reference_number,
            'found_in_ar_aging' => $arRecord ? 'Yes ✅' : 'No ❌',
            'customer_code' => $arRecord->customer_code ?? 'N/A',
            'customer_name' => $arRecord->client_name ?? 'N/A',
        ];
    }
    
    return response()->json([
        'summary' => [
            'total_adjustments_checked' => $sampleDRs->count(),
            'matches_found' => $totalMatches,
            'match_rate' => round(($totalMatches / $sampleDRs->count()) * 100, 2) . '%'
        ],
        'sample_matches' => $matchResults,
        'recommendation' => $totalMatches > 0 
            ? "✅ Great! Found {$totalMatches} matches. Run the fix at /fix-adjustments-via-ar-aging"
            : "❌ No matches found. DR numbers in adjustments don't exist in ar_aging table. Use bulk assign instead."
    ]);
}

/**
 * ✅ BULK ASSIGN: Assign all adjustments to one customer
 */
public function bulkAssignCustomer(Request $request)
{
    $customerCode = $request->input('customer_code');
    
    if (!$customerCode) {
        return response()->json([
            'success' => false,
            'message' => 'Customer code is required'
        ], 400);
    }
    
    $customer = DB::table('customers')
        ->where('customer_code', $customerCode)
        ->first();
    
    if (!$customer) {
        return response()->json([
            'success' => false,
            'message' => 'Customer not found'
        ], 404);
    }
    
    try {
        DB::beginTransaction();
        
        $updated = DB::table('ar_adjustments')
            ->whereNull('customer_code')
            ->orWhere('customer_code', '')
            ->update([
                'customer_code' => $customerCode,
                'customer_name' => $customer->customer_name,
                'updated_at' => now()
            ]);
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => "Successfully assigned {$updated} adjustments to {$customer->customer_name}",
            'updated_count' => $updated
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage()
        ], 500);
    }
}
}