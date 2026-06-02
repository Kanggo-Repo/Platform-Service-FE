<?php

namespace App\Http\Middleware;

use App\Support\Auth\SharedAuthSubjectCookie;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            $localSubject = trim((string) ($user->auth_subject ?? ''));
            $sharedSubject = SharedAuthSubjectCookie::current($request);

            if ($localSubject !== '' && $sharedSubject !== '' && ! hash_equals($localSubject, $sharedSubject)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                SharedAuthSubjectCookie::queueForget($request);

                return redirect()->guest(route('auth.redirect'));
            }

            if ($localSubject !== '' && $sharedSubject === '') {
                $hasOidcSession = $request->session()->has('platform_access_token')
                    || $request->session()->has('platform_refresh_token')
                    || $request->session()->has('platform_id_token');

                if ($hasOidcSession) {
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->guest(route('auth.redirect'));
                }

                SharedAuthSubjectCookie::queue($request, $localSubject);
            }

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
