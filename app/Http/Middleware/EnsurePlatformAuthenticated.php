<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return redirect()->guest(route('auth.redirect'));
    }
}
