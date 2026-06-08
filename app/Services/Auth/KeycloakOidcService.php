<?php

namespace App\Services\Auth;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class KeycloakOidcService
{
    public function authorizationUrl(
        string $state,
        string $codeVerifier,
        string $redirectUri,
    ): string {
        return $this->realmBaseUrl() .
            "/protocol/openid-connect/auth?" .
            http_build_query([
                "client_id" => config("services.keycloak.client_id"),
                "redirect_uri" => $redirectUri,
                "response_type" => "code",
                "scope" => "openid profile email",
                "state" => $state,
                "code_challenge" => $this->codeChallenge($codeVerifier),
                "code_challenge_method" => "S256",
            ]);
    }

    public function exchangeCode(
        string $code,
        string $codeVerifier,
        string $redirectUri,
    ): array {
        return $this->httpClient()
            ->asForm()
            ->post(
                $this->internalRealmBaseUrl() .
                    "/protocol/openid-connect/token",
                [
                    "grant_type" => "authorization_code",
                    "client_id" => config("services.keycloak.client_id"),
                    "code" => $code,
                    "redirect_uri" => $redirectUri,
                    "code_verifier" => $codeVerifier,
                ],
            )
            ->throw()
            ->json();
    }

    public function logoutUrl(
        string $postLogoutRedirectUri,
        ?string $idTokenHint = null,
    ): string {
        $query = [
            "client_id" => config("services.keycloak.client_id"),
            "post_logout_redirect_uri" => $postLogoutRedirectUri,
        ];

        if (is_string($idTokenHint) && $idTokenHint !== "") {
            $query["id_token_hint"] = $idTokenHint;
        }

        return $this->realmBaseUrl() .
            "/protocol/openid-connect/logout?" .
            http_build_query($query);
    }

    private function httpClient(): PendingRequest
    {
        $verify = $this->resolveVerifyOption();

        if ($verify === true) {
            return Http::acceptJson();
        }

        return Http::acceptJson()->withOptions([
            "verify" => $verify,
        ]);
    }

    private function resolveVerifyOption(): bool|string
    {
        if (!(bool) config("services.keycloak.verify_ssl", true)) {
            return false;
        }

        $caBundle = trim((string) config("services.keycloak.ca_bundle", ""));

        return $caBundle !== "" ? $caBundle : true;
    }

    private function realmBaseUrl(): string
    {
        return rtrim((string) config("services.keycloak.base_url"), "/") .
            "/realms/" .
            config("services.keycloak.realm");
    }

    private function internalRealmBaseUrl(): string
    {
        $internalBaseUrl = trim(
            (string) config("services.keycloak.internal_base_url"),
        );
        $baseUrl =
            $internalBaseUrl !== ""
                ? $internalBaseUrl
                : (string) config("services.keycloak.base_url");
        return rtrim($baseUrl, "/") .
            "/realms/" .
            config("services.keycloak.realm");
    }

    private function codeChallenge(string $codeVerifier): string
    {
        return rtrim(
            strtr(
                base64_encode(hash("sha256", $codeVerifier, true)),
                "+/",
                "-_",
            ),
            "=",
        );
    }
}
