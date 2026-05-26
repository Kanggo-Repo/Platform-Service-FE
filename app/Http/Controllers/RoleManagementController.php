<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Platform\PlatformServiceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly PlatformServiceClient $platformServiceClient,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        try {
            $rolesPayload = $this->platformServiceClient->roles($accessToken);
            $permissionsPayload = $this->platformServiceClient->permissions($accessToken);
        } catch (RuntimeException) {
            return $this->redirectToLoginAfterSessionReset($request);
        }

        $roleItems = collect($rolesPayload['items'] ?? [])
            ->map(function (array $role): Role {
                $roleModel = new Role([
                    'code' => $role['code'],
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'is_system' => $role['is_system'] ?? false,
                    'is_deletable' => $role['is_deletable'] ?? true,
                ]);
                $roleModel->id = $role['id'];
                $roleModel->exists = true;
                $roleModel->users_count = $role['users_count'] ?? 0;

                $permissions = collect($role['permissions'] ?? [])
                    ->map(function (string $permissionCode): Permission {
                        $permission = new Permission([
                            'code' => $permissionCode,
                            'name' => $permissionCode,
                            'module' => Str::before($permissionCode, '.'),
                            'description' => null,
                            'service_scope' => 'platform',
                        ]);
                        $permission->exists = true;

                        return $permission;
                    });

                $roleModel->setRelation('permissions', $permissions);

                return $roleModel;
            });

        $roles = new LengthAwarePaginator(
            items: $roleItems->values(),
            total: $roleItems->count(),
            perPage: max(1, $roleItems->count()),
            currentPage: 1,
            options: [
                'path' => route('settings.roles.index'),
                'query' => $request->query(),
            ],
        );

        $permissions = collect($permissionsPayload['all'] ?? [])
            ->map(function (string $permissionCode): Permission {
                $permission = new Permission([
                    'code' => $permissionCode,
                    'name' => $permissionCode,
                    'module' => Str::before($permissionCode, '.'),
                    'description' => null,
                    'service_scope' => 'platform',
                ]);
                $permission->exists = true;

                return $permission;
            });

        $permissionGroups = $permissionsPayload['groups'] ?? [];
        $summary = $rolesPayload['summary'] ?? [
            'total_roles' => 0,
            'total_permissions' => 0,
            'assigned_users' => 0,
        ];

        return view('settings.roles.index', compact('roles', 'permissions', 'permissionGroups', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->persistRole($request, fn (array $payload, string $token) => $this->platformServiceClient->createRole($token, $payload), 'Role berhasil ditambahkan.');
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        return $this->persistRole($request, fn (array $payload, string $token) => $this->platformServiceClient->updateRole($token, $role, $payload), 'Role berhasil diperbarui.');
    }

    public function destroy(Request $request, int $role): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        try {
            $this->platformServiceClient->deleteRole($accessToken, $role);
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.roles.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.roles.index')->with('success', 'Role berhasil dihapus.');
    }

    private function persistRole(Request $request, callable $action, string $successMessage): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $payload = [
            'code' => Str::of($validated['name'])->slug('_')->toString(),
            'name' => $validated['name'],
            'description' => null,
            'permissions' => array_values($validated['permissions'] ?? []),
        ];

        try {
            $action($payload, $accessToken);
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.roles.index')->with('error', $exception->getMessage())->withInput();
        }

        return redirect()->route('settings.roles.index')->with('success', $successMessage);
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
