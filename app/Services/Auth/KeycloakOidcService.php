<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;

class KeycloakOidcService
{
    public function authorizationUrl(string $state, string $codeVerifier, string $redirectUri): string
    {
        return $this->realmBaseUrl().'/protocol/openid-connect/auth?'.http_build_query([
            'client_id' => config('services.keycloak.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'code_challenge' => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ]);
    }

    public function exchangeCode(string $code, string $codeVerifier, string $redirectUri): array
    {
        return Http::asForm()
            ->post($this->realmBaseUrl().'/protocol/openid-connect/token', [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.keycloak.client_id'),
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'code_verifier' => $codeVerifier,
            ])
            ->throw()
            ->json();
    }

    public function logoutUrl(string $postLogoutRedirectUri, ?string $idTokenHint = null): string
    {
        $query = [
            'client_id' => config('services.keycloak.client_id'),
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ];

        if (is_string($idTokenHint) && $idTokenHint !== '') {
            $query['id_token_hint'] = $idTokenHint;
        }

        return $this->realmBaseUrl().'/protocol/openid-connect/logout?'.http_build_query($query);
    }

    private function realmBaseUrl(): string
    {
        return rtrim((string) config('services.keycloak.base_url'), '/').'/realms/'.config('services.keycloak.realm');
    }

    private function codeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }
}
