<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\KeycloakOidcService;
use App\Services\Platform\PlatformServiceClient;
use App\Support\Auth\LoginRedirectMemory;
use App\Support\Auth\SharedAuthSubjectCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PlatformOidcController extends Controller
{
    public function __construct(
        private readonly KeycloakOidcService $keycloakOidcService,
        private readonly PlatformServiceClient $platformServiceClient,
    ) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function redirect(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(20));
        $codeVerifier = bin2hex(random_bytes(32));

        $request->session()->put('oidc_state', $state);
        $request->session()->put('oidc_code_verifier', $codeVerifier);

        return redirect()->away($this->keycloakOidcService->authorizationUrl(
            state: $state,
            codeVerifier: $codeVerifier,
            redirectUri: route('auth.callback'),
        ));
    }

    public function callback(Request $request): RedirectResponse
    {
        // A duplicate or prefetched callback can arrive after a concurrent
        // request has already consumed the single-use authorization code and
        // signed the user in. Re-exchanging the same code would fail with
        // invalid_grant, so short-circuit straight to the workspace instead.
        if (Auth::guard('web')->check()) {
            $redirectTarget = LoginRedirectMemory::pull($request);

            return $redirectTarget !== null
                ? redirect()->to($redirectTarget)
                : redirect()->route('workspace.index');
        }

        if ($request->string('state')->toString() !== $request->session()->pull('oidc_state')) {
            return redirect()->route('login');
        }

        $codeVerifier = $request->session()->pull('oidc_code_verifier');
        $code = $request->string('code')->toString();

        if (! is_string($codeVerifier) || $codeVerifier === '' || $code === '') {
            return redirect()->route('login');
        }

        try {
            $tokens = $this->keycloakOidcService->exchangeCode(
                code: $code,
                codeVerifier: $codeVerifier,
                redirectUri: route('auth.callback'),
            );

            $accessToken = $tokens['access_token'] ?? null;
            if (! is_string($accessToken) || $accessToken === '') {
                return redirect()->route('login');
            }

            $me = $this->platformServiceClient->me($accessToken);
            $user = $this->upsertUser($me);
        } catch (Throwable $exception) {
            // A replayed/expired authorization code (invalid_grant) or a
            // transient platform-service failure must not surface as a 500.
            // Send the user back through /login, which re-authenticates
            // cleanly against the active Keycloak SSO session.
            report($exception);

            return redirect()->route('login');
        }

        $request->session()->put('platform_access_token', $accessToken);
        $request->session()->put('platform_refresh_token', $tokens['refresh_token'] ?? null);
        $request->session()->put('platform_id_token', $tokens['id_token'] ?? null);
        $request->session()->put('platform_token_expires_at', now()->addSeconds((int) ($tokens['expires_in'] ?? 0))->timestamp);

        $redirectTarget = LoginRedirectMemory::pull($request);

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        if ($redirectTarget === null) {
            $request->session()->put('platform_post_login_redirect', true);
        } else {
            $request->session()->forget('platform_post_login_redirect');
        }

        SharedAuthSubjectCookie::queue($request, (string) $user->auth_subject);

        return $redirectTarget !== null
            ? redirect()->to($redirectTarget)
            : redirect()->route('workspace.index');
    }

    public function consume(Request $request): RedirectResponse
    {
        return $this->callback($request);
    }

    public function logout(Request $request): RedirectResponse
    {
        $idTokenHint = $request->session()->pull('platform_id_token');

        $request->session()->forget([
            'platform_access_token',
            'platform_refresh_token',
            'platform_token_expires_at',
            'oidc_state',
            'oidc_code_verifier',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::guard('web')->logout();
        SharedAuthSubjectCookie::queueForget($request);

        return redirect()->away($this->keycloakOidcService->logoutUrl(
            postLogoutRedirectUri: route('login'),
            idTokenHint: $idTokenHint,
        ));
    }

    private function upsertUser(array $payload): User
    {
        $identity = is_array($payload['identity'] ?? null) ? $payload['identity'] : [];
        $subject = trim((string) ($identity['subject'] ?? ''));
        $email = trim((string) ($identity['email'] ?? ''));
        $name = trim((string) ($identity['name'] ?? ''));

        if ($subject === '' || $email === '') {
            throw new RuntimeException('Platform identity payload is incomplete.');
        }

        $authSubject = 'keycloak:'.$subject;

        $user = User::query()
            ->where('auth_provider', 'keycloak')
            ->where('auth_subject', $authSubject)
            ->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = new User;
            $user->password = Str::random(64);
        }

        $roles = is_array($payload['roles'] ?? null) ? $payload['roles'] : [];
        $permissions = is_array($payload['permissions'] ?? null) ? $payload['permissions'] : [];

        $user->fill([
            'name' => $name !== '' ? $name : $email,
            'email' => $email,
            'auth_provider' => 'keycloak',
            'auth_subject' => $authSubject,
            'role_snapshot' => $this->normalizeStringList($roles),
            'permission_snapshot' => $this->normalizeStringList($permissions),
            'last_login_at' => Carbon::now(),
            'email_verified_at' => Carbon::now(),
        ]);
        $user->save();

        return $user->fresh();
    }

    private function normalizeStringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $values,
        )));
    }
}
