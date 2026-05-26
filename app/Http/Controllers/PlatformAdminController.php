<?php

namespace App\Http\Controllers;

use App\Services\Platform\PlatformServiceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PlatformAdminController extends Controller
{
    public function __construct(
        private readonly PlatformServiceClient $platformServiceClient,
    ) {
    }

    public function roles(Request $request): View|RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        try {
            $roles = $this->platformServiceClient->roles($accessToken);
            $permissions = $this->platformServiceClient->permissions($accessToken);
        } catch (RuntimeException) {
            return $this->redirectToLoginAfterSessionReset($request);
        }

        return view('admin.roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function registration(Request $request): View|RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        try {
            $policy = $this->platformServiceClient->registrationSettings($accessToken);
        } catch (RuntimeException) {
            return $this->redirectToLoginAfterSessionReset($request);
        }

        return view('admin.registration.edit', [
            'policy' => $policy,
        ]);
    }

    public function updateRegistration(Request $request): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'registration_enabled' => ['required', 'boolean'],
            'approval_mode' => ['required', 'string', 'in:admin_approval,auto_approve'],
            'default_new_user_status' => ['required', 'string', 'in:pending_access,active,suspended,archived'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->platformServiceClient->updateRegistrationSettings($accessToken, $validated);
        } catch (RuntimeException) {
            return $this->redirectToLoginAfterSessionReset($request);
        }

        return redirect()->route('admin.registration.edit');
    }

    private function accessTokenFromSession(Request $request): ?string
    {
        $accessToken = $request->session()->get('platform_access_token');

        return is_string($accessToken) && $accessToken !== '' ? $accessToken : null;
    }

    private function redirectToLoginAfterSessionReset(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'platform_access_token',
            'platform_refresh_token',
            'platform_id_token',
            'platform_token_expires_at',
        ]);

        return redirect()->route('login');
    }
}
