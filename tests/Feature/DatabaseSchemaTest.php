<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_design_tables_and_indexes_are_created(): void
    {
        $tables = [
            'users',
            'roles',
            'user_roles',
            'units',
            'ingredients',
            'unit_conversions',
            'menus',
            'recipe_versions',
            'recipe_items',
            'orders',
            'order_items',
            'order_requirements',
            'material_allocations',
            'productions',
            'production_fulfillments',
            'material_usages',
            'stock_movements',
            'order_status_histories',
            'production_status_histories',
            'idempotency_keys',
            'audit_logs',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table [{$table}] was not created.");
        }

        $indexes = collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'index'"))
            ->pluck('name');

        $this->assertContains('recipe_versions_one_active_per_menu', $indexes);
        $this->assertContains('unit_conversions_general_unique', $indexes);
        $this->assertContains('unit_conversions_ingredient_unique', $indexes);
        $this->assertContains('stock_movements_ingredient_created_index', $indexes);
    }

    public function test_sqlite_foreign_keys_and_domain_constraints_are_enabled(): void
    {
        $foreignKeys = DB::selectOne('PRAGMA foreign_keys');

        $this->assertSame(1, $foreignKeys->foreign_keys);

        $this->expectException(QueryException::class);

        DB::table('units')->insert([
            'id' => (string) Str::uuid(),
            'code' => 'INVALID',
            'name' => 'Invalid unit',
            'dimension' => 'INVALID',
            'decimal_places' => 3,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_only_one_recipe_version_can_be_active_per_menu(): void
    {
        $user = User::factory()->create();
        $unitId = (string) Str::uuid();
        $menuId = (string) Str::uuid();

        DB::table('units')->insert([
            'id' => $unitId,
            'code' => 'PORTION',
            'name' => 'Portion',
            'dimension' => 'COUNT',
            'decimal_places' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('menus')->insert([
            'id' => $menuId,
            'code' => 'MENU-001',
            'name' => 'Test menu',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('recipe_versions')->insert([
            'id' => (string) Str::uuid(),
            'menu_id' => $menuId,
            'version_no' => 1,
            'recipe_type' => 'PER_PORTION',
            'yield_quantity' => 1,
            'yield_unit_id' => $unitId,
            'active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('recipe_versions')->insert([
            'id' => (string) Str::uuid(),
            'menu_id' => $menuId,
            'version_no' => 2,
            'recipe_type' => 'PER_PORTION',
            'yield_quantity' => 1,
            'yield_unit_id' => $unitId,
            'active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
    }
}
