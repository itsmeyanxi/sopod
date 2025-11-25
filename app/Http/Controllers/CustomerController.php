<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\RoleHelper;

class CustomerController extends Controller
{
    // Show all customers (list)
    public function index()
    {
        $customers = Customer::all();
        return view('customers.index', compact('customers'));
    }

    // Show create form
    public function create()
    {
        // ✅ Only Admin, IT, and CC can create customers
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        return view('customers.create');
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
        'branch' => 'required|string|max:255',
        'business_style' => 'nullable|string|max:255',
        'billing_address' => 'nullable|string|max:500',
        'tin' => 'nullable|string|max:50',
        'shipping_address' => 'nullable|string|max:500',
        'sales_executive' => 'nullable|string|max:255',
    ]);

    $validated['status'] = 'enabled';

    $customer = Customer::create($validated);

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
        // ✅ Only Admin, IT, and CC can edit customers
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
        'branch' => 'required|string|max:255',
        'business_style' => 'nullable|string|max:255',
        'billing_address' => 'nullable|string|max:500',
        'tin' => 'nullable|string|max:50',
        'shipping_address' => 'nullable|string|max:500',
        'sales_executive' => 'nullable|string|max:255',
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
        // ✅ Only Admin, IT, and CC can delete customers
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        $customer = Customer::findOrFail($id);

        // ✅ Log the activity before deleting
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
        // ✅ Only Admin, IT, and CC can toggle status
        if (!RoleHelper::canManageCustomers()) {
            return RoleHelper::unauthorized();
        }

        $customer = Customer::findOrFail($id);

        $customer->status = $customer->status === 'enabled' ? 'disabled' : 'enabled';
        $customer->save();

        // ✅ Log the activity
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
}