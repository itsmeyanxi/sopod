<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];
    protected $dontReport = [];
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Roles that should NOT see detailed error messages
     */
    protected $protectedRoles = [
        'admin',
        'cc_approver',
        'cc_creator',
        'accounting_creator',
        'accounting_approver',
        'delivery_creator',
        'delivery_approver',
        'csr_approver',
        'csr_creator',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Get the authenticated user
        $user = auth()->user();
        
        // Always handle HTML requests with custom pages
        if ($request->expectsHtml()) {
            
            // Check if user has a protected role
            if ($user && in_array(strtolower($user->role), $this->protectedRoles)) {
                // Show friendly error page
                return response()->view('errors.500', [
                    'timestamp' => now()->format('M d, Y H:i'),
                    'errorId' => \Illuminate\Support\Str::random(8),
                    'userRole' => $user->role, // For debugging
                ], 500);
            }
            
            // Guest users also see friendly page
            if (!$user) {
                return response()->view('errors.500', [
                    'timestamp' => now()->format('M d, Y H:i'),
                    'errorId' => \Illuminate\Support\Str::random(8),
                    'userRole' => 'Guest', // For debugging
                ], 500);
            }
            
            // IT and other roles see detailed error
            return parent::render($request, $e);
        }

        return parent::render($request, $e);
    }
}