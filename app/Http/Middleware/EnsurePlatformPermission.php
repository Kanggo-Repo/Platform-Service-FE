<?php

namespace App\Http\Middleware;

use App\Support\Auth\PlatformPermissionGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformPermission
{
    public function __construct(
        private readonly PlatformPermissionGate $permissionGate,
    ) {
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $this->permissionGate->allows($user, $permission)) {
            abort(403, 'Anda tidak memiliki akses ke area platform ini.');
        }

        return $next($request);
    }
}
