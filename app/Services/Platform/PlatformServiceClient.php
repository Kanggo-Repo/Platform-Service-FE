<?php

namespace App\Services\Platform;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlatformServiceClient
{
    public function me(string $accessToken): array
    {
        return $this->request('/api/v1/me', $accessToken);
    }

    public function navigation(string $accessToken): array
    {
        return $this->request('/api/v1/navigation', $accessToken);
    }

    public function dashboard(string $accessToken): array
    {
        return $this->request('/api/v1/dashboard', $accessToken);
    }

    public function profile(string $accessToken): array
    {
        return $this->request('/api/v1/profile', $accessToken);
    }

    public function updateProfile(string $accessToken, array $payload): array
    {
        return $this->write('/api/v1/profile', 'PUT', $accessToken, $payload);
    }

    public function roles(string $accessToken): array
    {
        return $this->request('/api/v1/roles', $accessToken);
    }

    public function createRole(string $accessToken, array $payload): array
    {
        return $this->write('/api/v1/roles', 'POST', $accessToken, $payload);
    }

    public function updateRole(string $accessToken, int $roleId, array $payload): array
    {
        return $this->write("/api/v1/roles/{$roleId}", 'PUT', $accessToken, $payload);
    }

    public function deleteRole(string $accessToken, int $roleId): array
    {
        return $this->write("/api/v1/roles/{$roleId}", 'DELETE', $accessToken);
    }

    public function permissions(string $accessToken): array
    {
        return $this->request('/api/v1/permissions', $accessToken);
    }

    public function users(string $accessToken, array $query = []): array
    {
        return $this->request('/api/v1/users', $accessToken, $query);
    }

    public function createUser(string $accessToken, array $payload): array
    {
        return $this->write('/api/v1/users', 'POST', $accessToken, $payload);
    }

    public function updateUser(string $accessToken, int $userId, array $payload): array
    {
        return $this->write("/api/v1/users/{$userId}", 'PUT', $accessToken, $payload);
    }

    public function deleteUser(string $accessToken, int $userId): array
    {
        return $this->write("/api/v1/users/{$userId}", 'DELETE', $accessToken);
    }

    public function registrationSettings(string $accessToken): array
    {
        return $this->request('/api/v1/settings/registration', $accessToken);
    }

    public function updateRegistrationSettings(string $accessToken, array $payload): array
    {
        return $this->write('/api/v1/settings/registration', 'PUT', $accessToken, $payload);
    }

    private function request(string $path, string $accessToken, array $query = []): array
    {
        $response = Http::baseUrl((string) config('services.platform_service.base_url'))
            ->acceptJson()
            ->withToken($accessToken)
            ->get($path, $query);

        if ($response->failed()) {
            throw new RuntimeException($this->resolveErrorMessage($response));
        }

        return $response->json('data', []);
    }

    private function write(string $path, string $method, string $accessToken, array $payload = []): array
    {
        $response = Http::baseUrl((string) config('services.platform_service.base_url'))
            ->acceptJson()
            ->withToken($accessToken)
            ->send($method, $path, [
                'json' => $payload,
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->resolveErrorMessage($response));
        }

        return $response->json('data', []);
    }

    private function resolveErrorMessage(Response $response): string
    {
        $message = trim((string) ($response->json('message') ?? ''));

        if ($message !== '') {
            return $message;
        }

        $errors = $response->json('errors');

        if (is_array($errors)) {
            $flattened = collect($errors)
                ->flatten()
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();

            if ($flattened->isNotEmpty()) {
                return $flattened->implode(' ');
            }
        }

        $body = trim($response->body());

        return $body !== '' ? $body : 'Platform service request failed.';
    }
}
