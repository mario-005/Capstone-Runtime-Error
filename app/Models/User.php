<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\RoleCode;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password_hash', 'active'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(RoleCode|string $role): bool
    {
        $roleCode = $role instanceof RoleCode ? $role->value : $role;

        return $this->roles()->where('code', $roleCode)->exists();
    }

    /**
     * @param  array<int, RoleCode|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $roleCodes = array_map(
            static fn (RoleCode|string $role): string => $role instanceof RoleCode ? $role->value : $role,
            $roles,
        );

        return $this->roles()->whereIn('code', $roleCodes)->exists();
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'active' => 'boolean',
        ];
    }
}
