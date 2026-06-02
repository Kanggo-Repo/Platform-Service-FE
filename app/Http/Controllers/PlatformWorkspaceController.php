<?php

namespace App\Http\Controllers;

use App\Services\Platform\PlatformServiceClient;
use App\Support\Auth\LoginRedirectMemory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PlatformWorkspaceController extends Controller
{
    public function __construct(
        private readonly PlatformServiceClient $platformServiceClient,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $accessToken = $request->session()->get('platform_access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            LoginRedirectMemory::remember($request);

            return redirect()->route('auth.redirect');
        }

        try {
            $me = $this->platformServiceClient->me($accessToken);
            $navigation = $this->platformServiceClient->navigation($accessToken);
        } catch (RuntimeException) {
            LoginRedirectMemory::store($request, LoginRedirectMemory::capture($request));
            $request->session()->forget([
                'platform_access_token',
                'platform_refresh_token',
                'platform_id_token',
                'platform_token_expires_at',
            ]);

            return redirect()->route('auth.redirect');
        }

        if (($navigation['pending_access'] ?? false) === true) {
            return redirect()->route('platform.access.pending');
        }

        if ($request->session()->pull('platform_post_login_redirect') === true) {
            $preferredServiceUrl = $this->resolvePreferredServiceUrl($navigation);

            if ($preferredServiceUrl !== null) {
                return redirect()->away($preferredServiceUrl);
            }
        }

        try {
            $dashboard = $this->platformServiceClient->dashboard($accessToken);
        } catch (RuntimeException) {
            LoginRedirectMemory::store($request, LoginRedirectMemory::capture($request));
            $request->session()->forget([
                'platform_access_token',
                'platform_refresh_token',
                'platform_id_token',
                'platform_token_expires_at',
            ]);

            return redirect()->route('auth.redirect');
        }

        return view('workspace.index', [
            'me' => $me,
            'navigation' => $navigation,
            'dashboard' => $dashboard,
        ]);
    }

    private function resolvePreferredServiceUrl(array $navigation): ?string
    {
        $preferredApp = trim((string) ($navigation['preferred_app'] ?? ''));

        return match ($preferredApp) {
            'supply' => $this->normalizeUrl((string) config('services.supply_fe.base_url')),
            'calculation' => $this->normalizeUrl((string) config('services.calculation_fe.base_url')),
            default => null,
        };
    }

    private function normalizeUrl(string $url): ?string
    {
        $normalized = rtrim(trim($url), '/');

        return $normalized !== '' ? $normalized : null;
    }
}
