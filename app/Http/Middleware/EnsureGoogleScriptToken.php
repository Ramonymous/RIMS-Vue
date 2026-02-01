<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGoogleScriptToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.google_script.token', '');

        if ($expected === '') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $provided = $request->header('X-APP-TOKEN')
            ?? $request->query('token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
