<?php

namespace App\Services\Supply;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupplyServiceClient
{
    /**
     * @return array<string, mixed>
     */
    public function storeSidebarSummary(?User $user): array
    {
        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->acceptJson()
            ->asJson()
            ->withHeaders($this->headers($user))
            ->withOptions([
                'verify' => (bool) config('services.supply_service.verify_ssl', false),
            ])
            ->get($this->url('api/v1/stores/sidebar-summary'));

        return $this->parseResponse($response);
    }

    /**
     * @return array<string, string>
     */
    private function headers(?User $user): array
    {
        $headers = [
            'X-Service-Name' => (string) config('services.supply_service.service_name', 'platform-service-be'),
            'X-Service-Token' => (string) config('services.supply_service.token', ''),
        ];

        if (! $user) {
            return $headers;
        }

        $headers['X-Actor-Id'] = (string) $user->getAuthIdentifier();
        $headers['X-Actor-Name'] = (string) $user->name;
        $headers['X-Actor-Email'] = (string) $user->email;
        $headers['X-Actor-Auth-Provider'] = (string) ($user->auth_provider ?? '');
        $headers['X-Actor-Auth-Subject'] = (string) ($user->auth_subject ?? '');

        $roles = $user->getRoleNames()->all();
        if ($roles !== []) {
            $headers['X-Actor-Roles'] = implode(',', $roles);
        }

        $permissions = is_array($user->permission_snapshot ?? null) ? $user->permission_snapshot : [];
        $permissions = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $permissions,
        )));

        if ($permissions !== []) {
            $headers['X-Actor-Permissions'] = implode(',', $permissions);
        }

        return $headers;
    }

    private function url(string $path): string
    {
        $baseUrl = rtrim((string) config('services.supply_service.base_url', ''), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Supply service base URL is not configured.');
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(Response $response): array
    {
        $payload = $response->json();

        if (! $response->successful()) {
            $message = is_array($payload)
                ? (string) ($payload['message'] ?? $payload['error'] ?? 'Supply service request failed.')
                : 'Supply service request failed.';

            throw new RuntimeException($message);
        }

        return is_array($payload) ? $payload : [];
    }
}
