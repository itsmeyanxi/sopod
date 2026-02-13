<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        // Let Laravel handle these natively (redirects, validation, 404, CSRF, auth)
        if ($e instanceof TokenMismatchException
            || $e instanceof AuthenticationException
            || $e instanceof ValidationException
            || $e instanceof NotFoundHttpException
            || ($e instanceof HttpException && $e->getStatusCode() < 500)
        ) {
            return parent::render($request, $e);
        }

        // Only customize 500-level errors for HTML requests
        if ($request->expectsHtml()) {
            $user = auth()->user();

            // IT/Admin users see detailed debug page
            if ($user && in_array(strtoupper($user->role), ['IT', 'ADMIN'])) {
                return response()->view('errors.it-debug', [
                    'exception' => $e,
                ], 500);
            }

            // Everyone else sees friendly error page
            return response()->view('errors.500', [
                'timestamp' => now()->format('M d, Y H:i'),
                'errorId' => \Illuminate\Support\Str::random(8),
                'userRole' => $user?->role ?? 'Guest',
            ], 500);
        }

        return parent::render($request, $e);
    }
}
