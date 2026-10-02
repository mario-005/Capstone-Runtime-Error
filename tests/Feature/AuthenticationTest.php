<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_with_role_can_login_read_profile_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password_hash' => 'secret-password',
        ]);
        $role = Role::factory()->create([
            'code' => RoleCode::OwnerAdmin,
            'name' => 'Owner / Admin',
        ]);
        $user->roles()->attach($role);

        $loginResponse = $this->withHeaders($this->statefulHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles.0', RoleCode::OwnerAdmin->value)
            ->assertJsonMissingPath('data.password_hash');
        $this->assertAuthenticatedAs($user);

        $this->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'owner@example.com');

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_invalid_credentials_and_inactive_users_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password_hash' => 'secret-password',
            'active' => false,
        ]);

        $this->withHeaders($this->statefulHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret-password',
        ])->assertUnauthorized()->assertJsonPath('error.code', 'INVALID_CREDENTIALS');

        $this->withHeaders($this->statefulHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()->assertJsonPath('error.code', 'INVALID_CREDENTIALS');

        $this->assertGuest();
    }

    public function test_profile_requires_authentication_and_an_assigned_role(): void
    {
        $this->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/me')
            ->assertUnauthorized();

        $user = User::factory()->create([
            'email' => 'unassigned@example.com',
            'password_hash' => 'secret-password',
        ]);

        $this->withHeaders($this->statefulHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'unassigned@example.com',
            'password' => 'secret-password',
        ])->assertOk();

        $this->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');

        $this->assertDatabaseCount('user_roles', 0);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_payload_is_validated(): void
    {
        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => 'not-an-email',
                'password' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return [
            'Origin' => (string) config('app.url'),
            'Referer' => rtrim((string) config('app.url'), '/').'/',
        ];
    }
}
