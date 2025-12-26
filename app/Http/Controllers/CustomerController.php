<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\RoleHelper;
use Illuminate\Support\Facades\Response;

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
        app(\App\Services\NotificationService::class)->notifyNewItem($customer);

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
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
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

        $customer->update($validated);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Updated',
            'item' => $customer->customer_code . ' - ' . $customer->customer_name,
            'target' => $customer->billing_address ?? 'N/A',
            'type' => 'Customer',
            'message' => 'Updated customer: ' . $customer->customer_name,
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
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

    // ✅ NEW: Toggle customer flag status (CC_Approver only)
    public function toggleFlag($id)
    {
        // Check if user is CC_Approver
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['CC_Approver', 'Admin', 'IT'])) {
            return redirect()->back()->with('error', 'Unauthorized: Only CC_Approver can flag/unflag customers.');
        }

        $customer = Customer::findOrFail($id);

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
}