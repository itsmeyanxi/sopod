<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display list of all users.
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form to create a new user.
     * ⚠️ DISABLED - Only Admin/IT can create users via UserManagementController
     */
    public function create()
    {
        // ❌ Redirect regular users trying to register
        return redirect()->route('login')
            ->with('error', 'Registration is disabled. Please contact an administrator to create your account.');
    }

    /**
     * Store a newly created user in the database.
     * ⚠️ DISABLED - Only Admin/IT can create users via UserManagementController
     */
    public function store(Request $request)
    {
        // ❌ Block registration attempts
        return redirect()->route('login')
            ->with('error', 'Registration is disabled. Please contact an administrator to create your account.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = ['Admin', 'IT', 'CSR_Approver','CSR_Creator', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'];
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the user in the database.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email,' . $user->id],
            'role'     => ['required', 'string'],
            'password' => ['nullable', 'min:6'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    // ===================================================
    // 🔐 LOGIN & LOGOUT METHODS
    // ===================================================

    /**
     * Show login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Check if user exists
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            return back()->withErrors([
                'email' => 'No account found with this email address.',
            ])->onlyInput('email');
        }

        // Try to login
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        // If we get here, password was wrong
        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    /**
     * Handle user logout.
     */
  public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');  // ✅ Use route name instead
}
}