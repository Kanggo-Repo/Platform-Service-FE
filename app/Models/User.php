<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'auth_provider', 'auth_subject', 'role_snapshot', 'permission_snapshot', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'role_snapshot' => 'array',
            'permission_snapshot' => 'array',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return in_array('super_admin', $this->role_snapshot ?? [], true);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permission_snapshot;

        return is_array($permissions) && in_array($permission, $permissions, true);
    }

    public function getRoleNames(): Collection
    {
        return collect($this->role_snapshot ?? []);
    }
}
