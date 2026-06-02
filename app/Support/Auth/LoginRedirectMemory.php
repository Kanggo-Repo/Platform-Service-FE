<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;

class LoginRedirectMemory
{
    private const SESSION_KEY = 'auth_return_to';

    public static function remember(Request $request): void
    {
        static::store($request, static::capture($request));
    }

    public static function store(Request $request, ?string $target): void
    {
        if (! is_string($target) || $target === '') {
            $request->session()->forget(static::SESSION_KEY);

            return;
        }

        $request->session()->put(static::SESSION_KEY, $target);
    }

    public static function pull(Request $request): ?string
    {
        $target = $request->session()->pull(static::SESSION_KEY);

        return is_string($target) && $target !== '' ? $target : null;
    }

    public static function capture(Request $request): ?string
    {
        if ($request->routeIs('login', 'auth.*')) {
            return null;
        }

        if ($request->isMethod('GET')) {
            return static::normalizePath($request->getRequestUri());
        }

        $previousUrl = url()->previous();

        return static::normalizePreviousUrl($request, is_string($previousUrl) ? $previousUrl : null);
    }

    private static function normalizePreviousUrl(Request $request, ?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }

        $requestHost = $request->getHost();
        $requestPort = $request->getPort();

        $targetHost = (string) ($parts['host'] ?? '');
        $targetPort = (int) ($parts['port'] ?? $requestPort);

        if ($targetHost !== '' && ! hash_equals($requestHost, $targetHost)) {
            return null;
        }

        if ($targetPort !== $requestPort) {
            return null;
        }

        $path = static::normalizePath((string) ($parts['path'] ?? '/'));
        if ($path === null) {
            return null;
        }

        $query = (string) ($parts['query'] ?? '');

        return $query !== '' ? $path.'?'.$query : $path;
    }

    private static function normalizePath(string $path): ?string
    {
        $normalized = trim($path);

        if ($normalized === '' || str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return null;
        }

        if (! str_starts_with($normalized, '/')) {
            $normalized = '/'.$normalized;
        }

        if ($normalized === '/login' || str_starts_with($normalized, '/auth/')) {
            return null;
        }

        return $normalized;
    }
}
