<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\Platform\PlatformServiceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly PlatformServiceClient $platformServiceClient,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        try {
            $payload = $this->platformServiceClient->users($accessToken, array_filter([
                'search' => $request->string('search')->toString(),
                'role' => $request->string('role')->toString(),
            ]));
        } catch (RuntimeException) {
            return $this->redirectToLoginAfterSessionReset($request);
        }

        $roles = collect($payload['roles'] ?? [])
            ->map(fn (array $role): Role => $this->hydrateRole($role));

        $users = $this->paginateUsers(
            $request,
            collect($payload['items'] ?? [])->map(fn (array $user): User => $this->hydrateUser($request, $user, $roles))
        );

        return view('settings.users.index', [
            'users' => $users,
            'roles' => $roles,
            'registrationEnabled' => (bool) ($payload['registration_enabled'] ?? false),
            'summary' => $payload['summary'] ?? [
                'total_users' => 0,
                'with_roles' => 0,
                'pending_access' => 0,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        try {
            $this->platformServiceClient->createUser($accessToken, [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'roles' => array_values($validated['roles'] ?? []),
            ]);
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.users.index')->with('error', $exception->getMessage())->withInput();
        }

        return redirect()->route('settings.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        try {
            $this->platformServiceClient->updateUser($accessToken, $user, [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'] ?: null,
                'roles' => array_values($validated['roles'] ?? []),
            ]);
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.users.index')->with('error', $exception->getMessage())->withInput();
        }

        return redirect()->route('settings.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, int $user): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        try {
            $this->platformServiceClient->deleteUser($accessToken, $user);
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.users.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.users.index')->with('success', 'User berhasil dihapus.');
    }

    public function updateRegistration(Request $request): RedirectResponse
    {
        $accessToken = $this->accessTokenFromSession($request);

        if ($accessToken === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'registration_enabled' => ['required', 'boolean'],
        ]);

        try {
            $settings = $this->platformServiceClient->registrationSettings($accessToken);

            $this->platformServiceClient->updateRegistrationSettings($accessToken, [
                'registration_enabled' => (bool) $validated['registration_enabled'],
                'approval_mode' => (string) ($settings['approval_mode'] ?? 'admin_approval'),
                'default_new_user_status' => (string) ($settings['default_new_user_status'] ?? 'pending_access'),
                'notes' => $settings['notes'] ?? null,
            ]);
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.users.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.users.index')->with('success', 'Pengaturan register berhasil diperbarui.');
    }

    private function paginateUsers(Request $request, Collection $items): LengthAwarePaginator
    {
        $perPage = 20;
        $currentPage = max(1, (int) $request->integer('page', 1));
        $offset = ($currentPage - 1) * $perPage;

        return new LengthAwarePaginator(
            items: $items->slice($offset, $perPage)->values(),
            total: $items->count(),
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'path' => route('settings.users.index'),
                'query' => $request->query(),
            ],
        );
    }

    private function hydrateUser(Request $request, array $payload, Collection $roles): User
    {
        $user = new User([
            'name' => $payload['name'],
            'email' => $payload['email'],
        ]);
        $user->id = $payload['id'];
        $user->exists = true;
        $user->status = $payload['status'] ?? 'pending_access';
        $user->is_current_user = $request->user()?->email === ($payload['email'] ?? null);

        $selectedRoleNames = collect($payload['roles'] ?? []);
        $user->setRelation('roles', $roles
            ->filter(fn (Role $role) => $selectedRoleNames->contains($role->name))
            ->values());

        return $user;
    }

    private function hydrateRole(array $payload): Role
    {
        $role = new Role([
            'code' => $payload['code'] ?? null,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'is_system' => $payload['is_system'] ?? false,
            'is_deletable' => $payload['is_deletable'] ?? true,
        ]);
        $role->id = $payload['id'];
        $role->exists = true;
        $role->users_count = $payload['users_count'] ?? 0;

        return $role;
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
