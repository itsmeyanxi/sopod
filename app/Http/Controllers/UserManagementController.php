<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Helpers\RoleHelper;

class UserManagementController extends Controller
{
    /**
     * Display list of all users (IT only - for user list page).
     */
    public function index()
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form to create a new user.
     */
    public function create()
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        $roles = ['Admin', 'IT', 'CSR_Approver','CSR_Creator',  'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'];
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in the database.
     */
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

            return redirect()->route('admin.users.create')
                ->with('success', 'Account created successfully!');

        } catch (\Exception $e) {
            \Log::error('Error creating user: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating account: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a user.
     */
    public function edit($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        $user = User::findOrFail($id);
        $roles = ['Admin', 'IT', 'CSR_Approver','CSR_Creator',  'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver','Accounting_Creator', 'Accounting_Approver'];
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the user in the database.
     */
    public function update(Request $request, $id)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            $user = User::findOrFail($id);

            // Check if this is a password reset from the user list
            if ($request->has('password_reset')) {
                // Only validate and update password
                $request->validate([
                    'password' => 'required|min:6',
                ]);

                $user->password = $request->password; // Will be hashed by User model
                $user->save();

                return redirect()->route('admin.users.index')
                    ->with('success', 'Password reset successfully!');
            }

            // Normal update flow
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'role' => 'required|in:Admin,IT,CSR_Approver,CSR_Creator,Delivery_Creator,Delivery_Approver,CC_Creator,CC_Approver,Accounting_Creator,Accounting_Approver',
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

    /**
     * Delete a user.
     */
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