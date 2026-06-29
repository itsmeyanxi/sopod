<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsControllerActions;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    use LogsControllerActions;

    // Maximum login attempts before account lock
    protected $maxAttempts = 6;

    protected function getLogChannel(): string
    {
        return 'auth';
    }

    public function index()
    {
        Log::channel('operations_network')->info('UNC FILE LOG TEST');
        $this->logInfo('Viewed users list', [
            'action' => 'index',
            'total_users' => User::count(),
        ]);

        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->logWarning('Attempted to access disabled registration', [
            'action' => 'create',
            'route' => 'users.create',
        ]);

        return redirect()->route('login')
            ->with('error', 'Registration is disabled. Please contact an administrator to create your account.');
    }

    public function store(Request $request)
    {
        $this->logWarning('Attempted to register via disabled route', [
            'action' => 'store',
            'email' => $request->email,
        ]);

        return redirect()->route('login')
            ->with('error', 'Registration is disabled. Please contact an administrator to create your account.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        
        $this->logInfo('Viewed user edit form', [
            'action' => 'edit',
            'target_user_id' => $id,
            'target_user_name' => $user->name,
            'target_user_email' => $user->email,
        ]);

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldData = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'min:6'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        $passwordChanged = false;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            // Reset login attempts when password is changed
            $user->login_attempts = 0;
            $passwordChanged = true;
        }

        $user->save();

        $this->logInfo('User updated', [
            'action' => 'update',
            'target_user_id' => $id,
            'target_user_name' => $user->name,
            'old_data' => $oldData,
            'new_data' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'password_changed' => $passwordChanged,
            'login_attempts_reset' => $passwordChanged,
        ]);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        $user->delete();

        $this->logInfo('User deleted', [
            'action' => 'delete',
            'deleted_user' => $userData,
        ]);

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    // ===================================================
    // 🔐 LOGIN & LOGOUT METHODS
    // ===================================================

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Check if user exists
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            $this->logWarning('Failed login attempt - User not found', [
                'action' => 'login_failed',
                'reason' => 'user_not_found',
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => "No account found with this email.",
            ])->onlyInput('email');
        }

        // CHECK 1: Is account manually locked by admin?
        if ($user->is_locked) {
            $this->logWarning('Login blocked - Account locked by admin', [
                'action' => 'login_blocked',
                'reason' => 'account_locked_by_admin',
                'email' => $credentials['email'],
                'user_id' => $user->id,
                'locked_at' => $user->locked_at,
                'locked_by' => $user->locked_by,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'Your account has been locked by an administrator. Please contact IT support to unlock your account.',
            ])->onlyInput('email')->with('account_locked', true);
        }

        // CHECK 2: Has user exceeded max login attempts?
        if ($user->login_attempts >= $this->maxAttempts) {
            $this->logWarning('Login blocked - Too many failed attempts', [
                'action' => 'login_blocked',
                'reason' => 'max_attempts_reached',
                'email' => $credentials['email'],
                'user_id' => $user->id,
                'attempts' => $user->login_attempts,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'Maximum login attempts reached (' . $user->login_attempts . ' failed attempts). Your account has been locked. Please contact IT support to unlock your account or reset your password.',
            ])->onlyInput('email')->with('locked_out', true);
        }

        // ATTEMPT LOGIN
        if (Auth::attempt($credentials)) {
            // ✅ SUCCESS: Reset login attempts
            $user->login_attempts = 0;
            $user->save();
            
            $request->session()->regenerate();
            
            $this->logInfo('✅ User logged in successfully', [
                'action' => 'login_success',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'session_id' => session()->getId(),
                'ip' => $request->ip(),
            ]);

            return redirect()->intended('/dashboard');
        }

        // ❌ FAILED LOGIN: Increment attempt counter
        $user->increment('login_attempts');
        $user->refresh();

        $remainingAttempts = max(0, $this->maxAttempts - $user->login_attempts);

        // 🔒 AUTO-LOCK if max attempts reached
        if ($user->login_attempts >= $this->maxAttempts) {
            $this->logWarning('Account auto-locked due to max failed attempts', [
                'action' => 'account_auto_locked',
                'email' => $credentials['email'],
                'user_id' => $user->id,
                'user_name' => $user->name,
                'attempts' => $user->login_attempts,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => "Invalid password. Maximum login attempts reached ({$user->login_attempts} attempts). Your account has been locked. Please contact IT support to unlock your account or reset your password.",
            ])->onlyInput('email')->with('locked_out', true);
        }

        $this->logWarning('Failed login attempt - Invalid password', [
            'action' => 'login_failed',
            'reason' => 'invalid_password',
            'email' => $credentials['email'],
            'user_id' => $user->id,
            'user_name' => $user->name,
            'ip' => $request->ip(),
            'attempts' => $user->login_attempts,
            'remaining_attempts' => $remainingAttempts,
        ]);

        return back()->withErrors([
            'email' => "Invalid password. You have {$remainingAttempts} attempt(s) remaining before your account is locked. (Current attempts: {$user->login_attempts}/{$this->maxAttempts})",
        ])->onlyInput('email');
    }

    // ===================================================
    // 👤 USER PROFILE METHODS
    // ===================================================

    public function profile()
    {
        $this->logInfo('Viewed own profile', [
            'action' => 'view_profile',
        ]);

        return view('admin.users.profile');
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);
            $user->name  = $request->name;
            $user->email = $request->email;


            $user->save();
            return redirect()->route('profile')->with('success', 'Profile info updated!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required|min:6|confirmed',
            ]);
            $user = Auth::user();
            $user->password = Hash::make($request->password);
            $user->save();
            return redirect()->route('profile')->with('success', 'Password updated!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function updateSignature(Request $request)
    {
        try {
            $request->validate([
                'esignature' => 'required|image|mimes:png,jpg,jpeg|max:2048',
            ]);
            $user = Auth::user();
            if ($user->esignature) {
                \Storage::disk('public')->delete($user->esignature);
            }
            $path = $request->file('esignature')->store('esignatures', 'public');
            $user->esignature = $path;
            $user->save();
            return redirect()->route('profile')->with('success', 'E-Signature updated!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        // Get user info before logout (might be null if session expired)
        $user = Auth::user();
        $sessionId = session()->getId();

        // Log the logout attempt
        if ($user) {
            $this->logInfo('User logged out', [
                'action' => 'logout',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'session_id' => $sessionId,
            ]);
        }

        // Perform logout
        Auth::logout();
        
        // Safely invalidate session (handle expired sessions)
        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Exception $e) {
            // Session might already be invalid, continue anyway
            \Log::info('Session invalidation skipped (already expired)');
        }

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    /**
     * 🔧 ADMIN: Reset login attempts for a user
     */
    public function resetLoginAttempts($userId)
    {
        $user = User::findOrFail($userId);
        
        $user->login_attempts = 0;
        $user->save();

        $this->logInfo('Admin reset login attempts', [
            'action' => 'reset_login_attempts',
            'target_user_id' => $userId,
            'target_user_name' => $user->name,
            'target_email' => $user->email,
            'reset_by' => Auth::user()->name ?? 'Unknown',
        ]);

        return back()->with('success', 'Login attempts reset successfully for ' . $user->name . '. They can now log in again.');
    }
}