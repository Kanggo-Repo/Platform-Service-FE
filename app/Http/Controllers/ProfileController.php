<?php

namespace App\Http\Controllers;

use App\Services\Platform\PlatformServiceClient;
use App\Support\Auth\LoginRedirectMemory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(
        private readonly PlatformServiceClient $platformServiceClient,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            LoginRedirectMemory::remember($request);

            return redirect()->route('auth.redirect');
        }

        try {
            $profile = $this->platformServiceClient->profile($accessToken);
        } catch (RuntimeException) {
            return $this->redirectToLoginAfterSessionReset($request);
        }

        return view('profile.show', [
            'profile' => $profile,
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            LoginRedirectMemory::remember($request);

            return redirect()->route('auth.redirect');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
        ]);

        try {
            $profile = $this->platformServiceClient->updateProfile($accessToken, $validated);
        } catch (RuntimeException $exception) {
            return redirect()->route('profile.show')->with('error', $exception->getMessage())->withInput();
        }

        $user = $request->user();
        $user->forceFill([
            'name' => $profile['full_name'] ?? trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? '')),
            'email' => $profile['email'] ?? $user->email,
        ])->save();

        return redirect()->route('profile.show')->with('success', 'Profile berhasil diperbarui.');
    }

    private function accessTokenFromSession(Request $request): ?string
    {
        $accessToken = $request->session()->get('platform_access_token');

        return is_string($accessToken) && $accessToken !== '' ? $accessToken : null;
    }

    private function redirectToLoginAfterSessionReset(Request $request): RedirectResponse
    {
        $redirectTarget = LoginRedirectMemory::capture($request);

        $request->session()->forget([
            'platform_access_token',
            'platform_refresh_token',
            'platform_id_token',
            'platform_token_expires_at',
        ]);

        LoginRedirectMemory::store($request, $redirectTarget);

        return redirect()->route('auth.redirect');
    }
}
