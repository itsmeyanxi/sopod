<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\UserRole;
use App\Models\User;
use App\Models\Activity;
use App\Helpers\RoleHelper;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        if (!RoleHelper::canManageUsers()) {
            return RoleHelper::unauthorized();
        }

        $departments = Department::with('subDepartments')->orderBy('name')->get();
        $users = User::with(['userRoles.subDepartment.department', 'lockedBy'])->orderBy('name')->get();

        return view('rbac.index', compact('departments', 'users'));
    }

    // ==================== RBAC METHODS ====================

    public function togglePermission(Request $request)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'permission' => 'required|in:can_create,can_edit,can_delete,can_approve,can_export,can_manage',
        ]);

        $role = UserRole::where('user_id', $request->user_id)
            ->where('sub_department_id', $request->sub_department_id)
            ->firstOrFail();

        $flag = $request->permission;
        $role->$flag = !$role->$flag;
        $role->level = $role->computeLevel();
        $role->save();

        return response()->json([
            'success' => true,
            'newValue' => $role->$flag,
            'level' => $role->level,
        ]);
    }

    public function changeLevel(Request $request)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'level' => 'required|in:Viewer,Editor,Admin',
        ]);

        $role = UserRole::where('user_id', $request->user_id)
            ->where('sub_department_id', $request->sub_department_id)
            ->firstOrFail();

        $role->applyLevel($request->level);
        $role->save();

        return response()->json([
            'success' => true,
            'level' => $role->level,
            'can_view' => $role->can_view,
            'can_create' => $role->can_create,
            'can_edit' => $role->can_edit,
            'can_delete' => $role->can_delete,
            'can_approve' => $role->can_approve,
            'can_export' => $role->can_export,
            'can_manage' => $role->can_manage,
        ]);
    }

    public function addRole(Request $request)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'role_type' => 'required|in:primary,secondary',
            'level' => 'required|in:Viewer,Editor,Admin',
        ]);

        $exists = UserRole::where('user_id', $request->user_id)
            ->where('sub_department_id', $request->sub_department_id)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'User already has a role in this sub-department'], 422);
        }

        $role = new UserRole([
            'user_id' => $request->user_id,
            'sub_department_id' => $request->sub_department_id,
            'role_type' => $request->role_type,
        ]);
        $role->applyLevel($request->level);
        $role->save();

        $role->load('subDepartment.department');

        return response()->json([
            'success' => true,
            'role' => $role,
        ]);
    }

    public function removeRole($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $role = UserRole::findOrFail($id);

        if ($role->role_type === 'primary') {
            $primaryCount = UserRole::where('user_id', $role->user_id)
                ->where('role_type', 'primary')
                ->count();

            if ($primaryCount <= 1) {
                return response()->json(['error' => 'Cannot remove the only primary role'], 422);
            }
        }

        $role->delete();

        return response()->json(['success' => true]);
    }

    public function changeRoleType($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $role = UserRole::findOrFail($id);
        $newType = $role->role_type === 'primary' ? 'secondary' : 'primary';

        // If changing from primary to secondary, ensure at least one primary remains
        if ($role->role_type === 'primary') {
            $primaryCount = UserRole::where('user_id', $role->user_id)
                ->where('role_type', 'primary')
                ->count();

            if ($primaryCount <= 1) {
                return response()->json(['error' => 'User must have at least one primary role'], 422);
            }
        }

        $role->role_type = $newType;
        $role->save();

        return response()->json(['success' => true, 'new_type' => $newType]);
    }

    // ==================== USER MANAGEMENT METHODS ====================

    public function storeUser(Request $request)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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

        $user->load(['userRoles.subDepartment.department', 'lockedBy']);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
        ]);

        $user = User::findOrFail($id);
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

        $user->load(['userRoles.subDepartment.department', 'lockedBy']);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function destroyUser($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot delete your own account'], 422);
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

        return response()->json(['success' => true]);
    }

    public function toggleLockUser($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot lock your own account'], 422);
        }

        $isEffectivelyLocked = $user->is_locked || $user->login_attempts >= 6;

        if ($isEffectivelyLocked) {
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

            return response()->json([
                'success' => true,
                'locked' => false,
                'login_attempts' => 0,
                'message' => "Account for {$user->name} has been unlocked!",
            ]);
        } else {
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

            return response()->json([
                'success' => true,
                'locked' => true,
                'message' => "Account for {$user->name} has been locked!",
            ]);
        }
    }

    public function resetLoginAttempts($id)
    {
        if (!RoleHelper::canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        $user->update(['login_attempts' => 0]);

        Activity::create([
            'user_name' => auth()->user()->name,
            'action' => 'Reset Attempts',
            'item' => $user->name,
            'target' => 'User Account',
            'type' => 'User Management',
            'message' => "Login attempts reset by " . auth()->user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Login attempts reset for {$user->name}",
        ]);
    }

}
