<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

class SharedAuthSubjectCookie
{
    public static function name(): string
    {
        return trim(
            (string) config(
                "services.keycloak.shared_subject_cookie",
                "kanggo_active_subject",
            ),
        );
    }

    public static function current(Request $request): string
    {
        return trim((string) $request->cookies->get(self::name(), ""));
    }

    public static function queue(Request $request, string $authSubject): void
    {
        $value = trim($authSubject);

        if ($value === "") {
            return;
        }

        Cookie::queue(self::makeCookie($request, $value));
    }

    public static function queueForget(Request $request): void
    {
        Cookie::queue(
            Cookie::forget(
                self::name(),
                config("session.path", "/"),
                self::resolveDomain($request),
            ),
        );
    }

    private static function makeCookie(
        Request $request,
        string $value,
    ): HttpCookie {
        return Cookie::make(
            self::name(),
            $value,
            60 * 24 * 30,
            config("session.path", "/"),
            self::resolveDomain($request),
            (bool) config("session.secure", false),
            false,
            false,
            config("session.same_site", "lax"),
        );
    }

    private static function resolveDomain(Request $request): ?string
    {
        $configured = trim((string) config("session.domain", ""));
        if ($configured !== "") {
            return $configured;
        }

        $host = trim((string) $request->getHost());
        if ($host === "" || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (str_ends_with($host, ".lvh.me")) {
            return ".lvh.me";
        }

        $segments = array_values(array_filter(explode(".", $host)));
        if (count($segments) < 2) {
            return null;
        }

        $slice = 2;
        $topLevel = $segments[count($segments) - 1] ?? "";
        $secondLevel = $segments[count($segments) - 2] ?? "";

        // Handle public suffixes like my.id by keeping one extra label.
        if (
            strlen($topLevel) === 2 &&
            strlen($secondLevel) <= 3 &&
            count($segments) >= 3
        ) {
            $slice = 3;
        }

        return "." . implode(".", array_slice($segments, -$slice));
    }
}
