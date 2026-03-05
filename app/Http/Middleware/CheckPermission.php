<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage in routes: ->middleware('permission:can_edit,3')
     * where 3 = sub_department_id
     */
    public function handle(Request $request, Closure $next, string $flag, int $subDepartmentId): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasPermission($flag, $subDepartmentId)) {
            return response()->view('errors.noaccess', [], 403);
        }

        return $next($request);
    }
}
