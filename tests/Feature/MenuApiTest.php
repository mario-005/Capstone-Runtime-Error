<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_update_and_deactivate_menu(): void
    {
        $owner = $this->userWithRole(RoleCode::OwnerAdmin);

        $createResponse = $this->actingAs($owner)->postJson('/api/v1/menus', [
            'code' => ' geprek-01 ',
            'name' => ' Ayam Geprek ',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.code', 'GEPREK-01')
            ->assertJsonPath('data.name', 'Ayam Geprek')
            ->assertJsonPath('data.active', true);

        $menuId = $createResponse->json('data.id');

        $this->actingAs($owner)->patchJson("/api/v1/menus/{$menuId}", [
            'name' => 'Ayam Geprek Sambal Bawang',
            'active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Ayam Geprek Sambal Bawang')
            ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('menus', [
            'id' => $menuId,
            'code' => 'GEPREK-01',
            'active' => false,
        ]);
    }

    public function test_operational_role_can_read_but_cannot_mutate_menu(): void
    {
        $clerk = $this->userWithRole(RoleCode::OrderClerk);
        $menu = Menu::factory()->create(['name' => 'Ayam Geprek']);

        $this->actingAs($clerk)
            ->getJson('/api/v1/menus')
            ->assertOk()
            ->assertJsonPath('data.0.id', $menu->id);

        $this->actingAs($clerk)->postJson('/api/v1/menus', [
            'code' => 'NEW-MENU',
            'name' => 'Menu Baru',
        ])->assertForbidden();

        $this->assertDatabaseMissing('menus', ['code' => 'NEW-MENU']);
    }

    public function test_menu_list_supports_search_active_filter_and_bounded_pagination(): void
    {
        $kitchen = $this->userWithRole(RoleCode::Kitchen);
        Menu::factory()->create(['code' => 'GEPREK', 'name' => 'Ayam Geprek', 'active' => true]);
        Menu::factory()->create(['code' => 'BAKAR', 'name' => 'Ayam Bakar', 'active' => false]);
        Menu::factory()->create(['code' => 'SOTO', 'name' => 'Soto Ayam', 'active' => true]);

        $this->actingAs($kitchen)
            ->getJson('/api/v1/menus?q=ayam&active=1&per_page=1000')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_active_menu_name_must_be_unique_after_normalization(): void
    {
        $owner = $this->userWithRole(RoleCode::OwnerAdmin);
        Menu::factory()->create(['name' => 'Ayam Geprek', 'active' => true]);

        $this->actingAs($owner)->postJson('/api/v1/menus', [
            'code' => 'GEPREK-02',
            'name' => ' ayam geprek ',
            'active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('menus', 1);
    }

    private function userWithRole(RoleCode $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create([
            'code' => $roleCode,
            'name' => $roleCode->value,
        ]);
        $user->roles()->attach($role);

        return $user;
    }
}
