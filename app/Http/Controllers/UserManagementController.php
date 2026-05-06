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
    const AVAILABLE_ROLES = [
        'User'               => 'User (Default)',
        'Admin'              => 'Admin',
        'IT'                 => 'IT / Information System',
        'SCM'                => 'Supply Chain Management (SCM)',
        'Finance'            => 'Finance',
        'Accounting'         => 'Accounting',
        'Sales'              => 'Sales',
        'Credit & Collection'=> 'Credit & Collection',
        'Treasury'           => 'Treasury',
        'Delivery'           => 'Delivery',
        'Purchasing'         => 'Purchasing',
        'President'          => 'President',
        'Vice President'     => 'Vice President',
    ];

    /**
     * Display list of all users.
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

        $availableRoles = self::AVAILABLE_ROLES;
        return view('admin.users.create', compact('availableRoles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role'     => 'required|string|in:' . implode(',', array_keys(self::AVAILABLE_ROLES)),
            ]);

            $selectedRole = $request->role;
            $user = User::create([
                'name'           => $request->name,
                'email'          => $request->email,
                'password'       => $request->password,
                'roles'          => [$selectedRole],
                'role'           => $selectedRole,
                'login_attempts' => 0,
            ]);

            Activity::create([
                'user_name' => auth()->user()->name,
                'action'    => 'Created',
                'item'      => $user->name,
                'target'    => 'User Account',
                'type'      => 'User Management',
                'message'   => 'User account created',
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

        $user           = User::with(['lockedBy', 'moduleOverrides'])->findOrFail($id);
        $overrides      = $user->moduleOverrides->keyBy('module');
        $availableRoles = self::AVAILABLE_ROLES;

        return view('admin.users.edit', compact('user', 'overrides', 'availableRoles'));
    }

    /**
     * Update the user.
     */
    public function update(Request $request, $id)
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            $user = User::findOrFail($id);

            // Password reset from user list
            if ($request->has('password_reset')) {
                $request->validate([
                    'password' => 'required|min:6',
                ]);

                $user->password       = $request->password;
                $user->login_attempts = 0;
                $user->save();

                Activity::create([
                    'user_name' => auth()->user()->name,
                    'action'    => 'Reset Password',
                    'item'      => $user->name,
                    'target'    => 'User Account',
                    'type'      => 'User Management',
                    'message'   => 'Password reset by ' . auth()->user()->name,
                ]);

                return redirect()->route('admin.users.index')
                    ->with('success', 'Password reset successfully! Login attempts have been cleared.');
            }

            // Normal update
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|min:6',
                'role'     => 'required|string|in:' . implode(',', array_keys(self::AVAILABLE_ROLES)),
            ]);

            $selectedRole     = $request->role;
            $user->name       = $request->name;
            $user->email      = $request->email;
            $user->role       = $selectedRole;
            $user->roles      = [$selectedRole];

            if ($request->filled('password')) {
                $user->password       = $request->password;
                $user->login_attempts = 0;
            }

            $user->save();

            Activity::create([
                'user_name' => auth()->user()->name,
                'action'    => 'Updated',
                'item'      => $user->name,
                'target'    => 'User Account',
                'type'      => 'User Management',
                'message'   => 'User account updated',
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

            if ($user->id === Auth::id()) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'You cannot delete your own account!');
            }

            $userName = $user->name;
            $user->delete();

            Activity::create([
                'user_name' => auth()->user()->name,
                'action'    => 'Deleted',
                'item'      => $userName,
                'target'    => 'User Account',
                'type'      => 'User Management',
                'message'   => 'User account deleted by ' . auth()->user()->name,
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
     * override: 'grant' = always allow, 'default' = remove override
     *
     * NOTE: 'deny' has been removed. Access restriction is handled naturally
     * by sub-department assignments. Grant overrides only ADD extra access
     * on top of what sub-departments already provide.
     * IT/Admin users are never affected by overrides.
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
            // Remove any existing override — sub-dept rules take over
            $user->moduleOverrides()->where('module', $module)->delete();
            $action = 'removed';
        } elseif ($request->override === 'deny') {
            // Explicitly deny access regardless of sub-dept assignment
            $user->moduleOverrides()->updateOrCreate(
                ['module' => $module],
                ['allowed' => false]
            );
            $action = 'denied';
        } else {
            // Grant extra access on top of sub-dept assignment
            $user->moduleOverrides()->updateOrCreate(
                ['module' => $module],
                ['allowed' => true]
            );
            $action = 'granted';
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
     * Toggle lock/unlock user account.
     */
    public function toggleLock($user)
    {
        if (!$user instanceof \App\Models\User) {
            $user = User::findOrFail($user);
        }

        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        try {
            if ($user->id === Auth::id()) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'You cannot lock your own account!');
            }

            $isEffectivelyLocked = $user->is_locked || $user->login_attempts >= 6;

            if ($isEffectivelyLocked) {
                $user->update([
                    'is_locked'      => false,
                    'locked_at'      => null,
                    'locked_by'      => null,
                    'login_attempts' => 0,
                ]);

                Activity::create([
                    'user_name' => auth()->user()->name,
                    'action'    => 'Unlocked',
                    'item'      => $user->name,
                    'target'    => 'User Account',
                    'type'      => 'User Management',
                    'message'   => 'Account unlocked by ' . auth()->user()->name,
                ]);

                \Log::info('Account unlocked', [
                    'unlocked_user'          => $user->name,
                    'unlocked_by'            => auth()->user()->name,
                    'was_manually_locked'    => $user->is_locked,
                    'had_failed_attempts'    => $user->login_attempts,
                    'login_attempts_reset'   => true,
                ]);

                return redirect()->route('admin.users.index')
                    ->with('success', "Account for {$user->name} has been unlocked! Login attempts have been reset.");
            } else {
                $user->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => Auth::id(),
                ]);

                Activity::create([
                    'user_name' => auth()->user()->name,
                    'action'    => 'Locked',
                    'item'      => $user->name,
                    'target'    => 'User Account',
                    'type'      => 'User Management',
                    'message'   => 'Account locked by ' . auth()->user()->name,
                ]);

                \Log::warning('Account locked', [
                    'locked_user' => $user->name,
                    'locked_by'   => auth()->user()->name,
                    'reason'      => 'Manual lock by administrator',
                ]);

                return redirect()->route('admin.users.index')
                    ->with('success', "Account for {$user->name} has been locked!");
            }

        } catch (\Exception $e) {
            \Log::error('Error toggling account lock: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Error toggling account lock: ' . $e->getMessage());
        }
    }
}