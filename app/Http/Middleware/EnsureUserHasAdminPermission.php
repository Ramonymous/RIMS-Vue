<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAdminPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasPermission('admin')) {
            abort(403, 'Access denied. Admin permission required.');
        }

        return $next($request);
    }
}
