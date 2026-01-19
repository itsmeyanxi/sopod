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
        // Only handle HTML requests with custom pages
        if ($request->expectsHtml()) {
            $user = auth()->user();
            
            // IT users see detailed debug page
            if ($user && strtoupper($user->role) === 'IT') {
                return response()->view('errors.it-debug', [
                    'exception' => $e,
                ], 500);
            }
            
            // Admin users see detailed debug page
            if ($user && strtoupper($user->role) === 'ADMIN') {
                return response()->view('errors.it-debug', [
                    'exception' => $e,
                ], 500);
            }
            
            // Everyone else (including guests) sees friendly error page
            return response()->view('errors.500', [
                'timestamp' => now()->format('M d, Y H:i'),
                'errorId' => \Illuminate\Support\Str::random(8),
                'userRole' => $user->role ?? 'Guest',
            ], 500);
        }

        return parent::render($request, $e);
    }
}