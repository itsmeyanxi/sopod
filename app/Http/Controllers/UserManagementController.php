<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Helpers\RoleHelper;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.users.create', compact('users'));
    }

    public function create()
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        $roles = ['Admin', 'IT', 'CSR_Approver','CSR_Creator',  'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'];
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role' => 'required|in:Admin,IT,CSR_Approver,CSR_Creator,Delivery_Creator,Delivery_Approver,CC_Creator,CC_Approver,Accounting_Creator,Accounting_Approver',
            ]);

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => $request->role,
            ]);

            // ✅ FIXED: Redirect back to create page with success message
            return redirect()->route('admin.users.create')
                ->with('success', 'Account created successfully!');

        } catch (\Exception $e) {
            \Log::error('Error creating user: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating account: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        $user = User::findOrFail($id);
        $roles = ['Admin', 'IT', 'CSR_Approver','CSR_Creator',  'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver','Accounting_Creator', 'Accounting_Approver'];
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'role' => 'required|in:Admin,IT,CSR_Approver,CSR_Creator,Delivery_Creator, Delivery_Approver,CC_Creator,CC_Approver,Accounting_Creator,Accounting_Approver',
                'password' => 'nullable|min:6',
            ]);

            $user->name = $request->name;
            $user->email = $request->email;
            $user->role = $request->role;

            if ($request->filled('password')) {
                $user->password = $request->password;
            }

            $user->save();

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Error updating user: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating account: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            $user = User::findOrFail($id);

            // Prevent deleting yourself
            if ($user->id === Auth::id()) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'You cannot delete your own account!');
            }

            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully!');

        } catch (\Exception $e) {
            \Log::error('Error deleting user: ' . $e->getMessage());
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Error deleting account: ' . $e->getMessage());
        }
    }
}