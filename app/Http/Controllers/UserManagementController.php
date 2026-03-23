<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Activity;
use App\Models\UserModuleOverride;
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

        $users = User::with('lockedBy')->get();
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

        return view('admin.users.create');
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
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'roles' => ['User'],
                'role' => 'User',
                'login_attempts' => 0,
            ]);

            Activity::create([
                'user_name' => auth()->user()->name,
                'action' => 'Created',
                'item' => $user->name,
                'target' => 'User Account',
                'type' => 'User Management',
                'message' => "User account created",
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

        $user = User::with(['lockedBy', 'moduleOverrides'])->findOrFail($id);
        $overrides = $user->moduleOverrides->keyBy('module');

        return view('admin.users.edit', compact('user', 'overrides'));
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
                $request->validate([
                    'password' => 'required|min:6',
                ]);

                $user->password = $request->password;
                $user->login_attempts = 0;
                $user->save();

                Activity::create([
                    'user_name' => auth()->user()->name,
                    'action' => 'Reset Password',
                    'item' => $user->name,
                    'target' => 'User Account',
                    'type' => 'User Management',
                    'message' => "Password reset by " . auth()->user()->name,
                ]);

                return redirect()->route('admin.users.index')
                    ->with('success', 'Password reset successfully! Login attempts have been cleared.');
            }

            // Normal update flow
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|min:6',
            ]);

            $user->name = $request->name;
            $user->email = $request->email;

            if ($request->filled('password')) {
                $user->password = $request->password;
                $user->login_attempts = 0;
            }

            $user->save();

            Activity::create([
                'user_name' => auth()->user()->name,
                'action' => 'Updated',
                'item' => $user->name,
                'target' => 'User Account',
                'type' => 'User Management',
                'message' => "User account updated",
            ]);

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

            $userName = $user->name;
            $user->delete();

            Activity::create([
                'user_name' => auth()->user()->name,
                'action' => 'Deleted',
                'item' => $userName,
                'target' => 'User Account',
                'type' => 'User Management',
                'message' => "User account deleted by " . auth()->user()->name,
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully!');

        } catch (\Exception $e) {
            \Log::error('Error deleting user: ' . $e->getMessage());
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Error deleting account: ' . $e->getMessage());
        }
    }

    /**
     * Set or remove a per-user module access override.
     * override: 'grant' = always allow, 'deny' = always block, 'default' = remove override
     */
    public function updateModuleOverride(Request $request, $id)
    {
        if (!auth()->user()->isAdminUser()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'module'   => 'required|string',
            'override' => 'required|in:default,grant,deny',
        ]);

        $user   = User::findOrFail($id);
        $module = $request->module;

        if ($request->override === 'default') {
            $user->moduleOverrides()->where('module', $module)->delete();
            $action = 'removed';
        } else {
            $allowed = $request->override === 'grant';
            $user->moduleOverrides()->updateOrCreate(
                ['module' => $module],
                ['allowed' => $allowed]
            );
            $action = $allowed ? 'granted' : 'denied';
        }

        Activity::create([
            'user_name' => auth()->user()->name,
            'action'    => 'Module Override',
            'item'      => $user->name,
            'target'    => "Module: {$module}",
            'type'      => 'User Management',
            'message'   => "Module override {$action} for {$module}",
        ]);

        return response()->json(['success' => true, 'action' => $action]);
    }

    /**
     * Toggle lock/unlock user account
     */
    public function toggleLock($user)
    {
        // If $user is passed as ID (string/int), fetch the model
        if (!$user instanceof \App\Models\User) {
            $user = User::findOrFail($user);
        }

        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            // Prevent locking yourself
            if ($user->id === Auth::id()) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'You cannot lock your own account!');
            }

            // ✅ FIX: Check if account is EFFECTIVELY locked (either manually OR via failed attempts)
            $isEffectivelyLocked = $user->is_locked || $user->login_attempts >= 6;

            if ($isEffectivelyLocked) {
                // ✅ UNLOCK THE ACCOUNT - Clear BOTH manual lock AND login attempts
                $user->update([
                    'is_locked' => false,
                    'locked_at' => null,
                    'locked_by' => null,
                    'login_attempts' => 0, 
                ]);
                
                Activity::create([
                    'user_name' => auth()->user()->name,
                    'action' => 'Unlocked',
                    'item' => $user->name,
                    'target' => 'User Account',
                    'type' => 'User Management',
                    'message' => "Account unlocked by " . auth()->user()->name,
                ]);

                \Log::info('Account unlocked', [
                    'unlocked_user' => $user->name,
                    'unlocked_by' => auth()->user()->name,
                    'was_manually_locked' => $user->is_locked,
                    'had_failed_attempts' => $user->login_attempts,
                    'login_attempts_reset' => true,
                ]);

                return redirect()->route('admin.users.index')
                    ->with('success', "Account for {$user->name} has been unlocked! Login attempts have been reset.");
            } else {
                // ✅ LOCK THE ACCOUNT
                $user->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => Auth::id(),
                ]);

                Activity::create([
                    'user_name' => auth()->user()->name,
                    'action' => 'Locked',
                    'item' => $user->name,
                    'target' => 'User Account',
                    'type' => 'User Management',
                    'message' => "Account locked by " . auth()->user()->name,
                ]);

                \Log::warning('Account locked', [
                    'locked_user' => $user->name,
                    'locked_by' => auth()->user()->name,
                    'reason' => 'Manual lock by administrator',
                ]);

                return redirect()->route('admin.users.index')
                    ->with('success', "Account for {$user->name} has been locked!");
            }

        } catch (\Exception $e) {
            \Log::error('Error toggling account lock: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Error toggling account lock: ' . $e->getMessage());
        }
    }
}