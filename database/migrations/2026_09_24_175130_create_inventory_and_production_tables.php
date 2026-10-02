<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TABLE roles (
                id TEXT PRIMARY KEY,
                code TEXT NOT NULL UNIQUE CHECK (code IN ('OWNER_ADMIN', 'ORDER_CLERK', 'KITCHEN', 'INVENTORY')),
                name TEXT NOT NULL
            );

            CREATE TABLE units (
                id TEXT PRIMARY KEY,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                dimension TEXT NOT NULL CHECK (dimension IN ('MASS', 'VOLUME', 'COUNT', 'LENGTH', 'OTHER')),
                decimal_places INTEGER NOT NULL DEFAULT 3 CHECK (decimal_places BETWEEN 0 AND 6),
                active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE user_roles (
                user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                role_id TEXT NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
                PRIMARY KEY (user_id, role_id)
            );

            CREATE TABLE ingredients (
                id TEXT PRIMARY KEY,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                base_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                purchase_unit_id TEXT REFERENCES units(id) ON DELETE RESTRICT,
                purchase_pack_quantity NUMERIC CHECK (purchase_pack_quantity > 0),
                physical_quantity NUMERIC NOT NULL DEFAULT 0 CHECK (physical_quantity >= 0),
                allocated_quantity NUMERIC NOT NULL DEFAULT 0 CHECK (
                    allocated_quantity >= 0 AND allocated_quantity <= physical_quantity
                ),
                active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE unit_conversions (
                id TEXT PRIMARY KEY,
                ingredient_id TEXT REFERENCES ingredients(id) ON DELETE RESTRICT,
                from_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                to_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                factor NUMERIC NOT NULL CHECK (factor > 0),
                source_note TEXT NOT NULL,
                verified_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                verified_at TEXT NOT NULL,
                active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
                CHECK (from_unit_id <> to_unit_id)
            );

            CREATE TABLE menus (
                id TEXT PRIMARY KEY,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE recipe_versions (
                id TEXT PRIMARY KEY,
                menu_id TEXT NOT NULL REFERENCES menus(id) ON DELETE RESTRICT,
                version_no INTEGER NOT NULL CHECK (version_no > 0),
                recipe_type TEXT NOT NULL CHECK (recipe_type IN ('PER_PORTION', 'PER_BATCH')),
                yield_quantity NUMERIC NOT NULL CHECK (yield_quantity > 0),
                yield_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                active INTEGER NOT NULL DEFAULT 0 CHECK (active IN (0, 1)),
                created_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                created_at TEXT NOT NULL,
                UNIQUE (menu_id, version_no)
            );

            CREATE TABLE recipe_items (
                id TEXT PRIMARY KEY,
                recipe_version_id TEXT NOT NULL REFERENCES recipe_versions(id) ON DELETE RESTRICT,
                ingredient_id TEXT NOT NULL REFERENCES ingredients(id) ON DELETE RESTRICT,
                quantity NUMERIC NOT NULL CHECK (quantity > 0),
                unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                UNIQUE (recipe_version_id, ingredient_id)
            );

            CREATE TABLE idempotency_keys (
                id TEXT PRIMARY KEY,
                user_id TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                scope TEXT NOT NULL,
                request_key TEXT NOT NULL,
                fingerprint TEXT NOT NULL CHECK (length(fingerprint) = 64),
                status TEXT NOT NULL CHECK (status IN ('PROCESSING', 'COMPLETED', 'FAILED')),
                response_code INTEGER,
                response_json TEXT CHECK (response_json IS NULL OR json_valid(response_json)),
                created_at TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                UNIQUE (user_id, scope, request_key)
            );

            CREATE TABLE orders (
                id TEXT PRIMARY KEY,
                order_number TEXT NOT NULL UNIQUE,
                order_type TEXT NOT NULL CHECK (order_type IN ('DIRECT', 'CATERING')),
                customer_name TEXT,
                target_at TEXT NOT NULL,
                order_status TEXT NOT NULL DEFAULT 'DRAFT' CHECK (
                    order_status IN (
                        'DRAFT', 'CONFIRMED', 'IN_PREPARATION', 'PARTIALLY_FULFILLED',
                        'READY', 'HANDED_OVER', 'CANCELLED'
                    )
                ),
                payment_status TEXT CHECK (payment_status IN ('UNPAID', 'PARTIAL', 'PAID', 'REFUNDED')),
                notes TEXT,
                created_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                confirmed_at TEXT,
                ready_at TEXT,
                handed_over_at TEXT,
                cancelled_at TEXT,
                cancelled_by TEXT REFERENCES users(id) ON DELETE RESTRICT,
                cancellation_reason TEXT,
                lock_version INTEGER NOT NULL DEFAULT 0 CHECK (lock_version >= 0),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE order_items (
                id TEXT PRIMARY KEY,
                order_id TEXT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
                menu_id TEXT NOT NULL REFERENCES menus(id) ON DELETE RESTRICT,
                menu_name_snapshot TEXT NOT NULL,
                quantity NUMERIC NOT NULL CHECK (quantity > 0),
                fulfilled_quantity NUMERIC NOT NULL DEFAULT 0 CHECK (
                    fulfilled_quantity >= 0 AND fulfilled_quantity <= quantity
                ),
                notes TEXT,
                unit_price NUMERIC CHECK (unit_price >= 0)
            );

            CREATE TABLE order_requirements (
                id TEXT PRIMARY KEY,
                order_item_id TEXT NOT NULL REFERENCES order_items(id) ON DELETE RESTRICT,
                ingredient_id TEXT NOT NULL REFERENCES ingredients(id) ON DELETE RESTRICT,
                recipe_version_id TEXT NOT NULL REFERENCES recipe_versions(id) ON DELETE RESTRICT,
                source_quantity NUMERIC NOT NULL CHECK (source_quantity > 0),
                source_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                conversion_factor NUMERIC NOT NULL CHECK (conversion_factor > 0),
                required_quantity NUMERIC NOT NULL CHECK (required_quantity > 0),
                rounding_mode TEXT NOT NULL CHECK (rounding_mode IN ('NONE', 'UP', 'NEAREST')),
                rounded_quantity NUMERIC NOT NULL CHECK (rounded_quantity > 0),
                base_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                shortage_quantity NUMERIC NOT NULL DEFAULT 0 CHECK (shortage_quantity >= 0),
                UNIQUE (order_item_id, ingredient_id)
            );

            CREATE TABLE material_allocations (
                id TEXT PRIMARY KEY,
                order_id TEXT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
                ingredient_id TEXT NOT NULL REFERENCES ingredients(id) ON DELETE RESTRICT,
                allocated_quantity NUMERIC NOT NULL CHECK (allocated_quantity > 0),
                consumed_quantity NUMERIC NOT NULL DEFAULT 0 CHECK (consumed_quantity >= 0),
                released_quantity NUMERIC NOT NULL DEFAULT 0 CHECK (released_quantity >= 0),
                status TEXT NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE', 'RELEASED', 'CLOSED')),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                CHECK (consumed_quantity + released_quantity <= allocated_quantity)
            );

            CREATE TABLE productions (
                id TEXT PRIMARY KEY,
                order_id TEXT NOT NULL UNIQUE REFERENCES orders(id) ON DELETE RESTRICT,
                production_status TEXT NOT NULL DEFAULT 'NOT_STARTED' CHECK (
                    production_status IN ('NOT_STARTED', 'IN_PROGRESS', 'PARTIAL', 'COMPLETED', 'CANCELLED')
                ),
                started_at TEXT,
                completed_at TEXT,
                notes TEXT,
                lock_version INTEGER NOT NULL DEFAULT 0 CHECK (lock_version >= 0),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE production_fulfillments (
                id TEXT PRIMARY KEY,
                production_id TEXT NOT NULL REFERENCES productions(id) ON DELETE RESTRICT,
                order_item_id TEXT NOT NULL REFERENCES order_items(id) ON DELETE RESTRICT,
                quantity NUMERIC NOT NULL CHECK (quantity > 0),
                recorded_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                recorded_at TEXT NOT NULL,
                reason TEXT
            );

            CREATE TABLE material_usages (
                id TEXT PRIMARY KEY,
                production_id TEXT NOT NULL REFERENCES productions(id) ON DELETE RESTRICT,
                ingredient_id TEXT NOT NULL REFERENCES ingredients(id) ON DELETE RESTRICT,
                source_quantity NUMERIC NOT NULL CHECK (source_quantity > 0),
                source_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                conversion_factor NUMERIC NOT NULL CHECK (conversion_factor > 0),
                quantity NUMERIC NOT NULL CHECK (quantity > 0),
                reason TEXT,
                created_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                idempotency_key_id TEXT NOT NULL UNIQUE REFERENCES idempotency_keys(id) ON DELETE RESTRICT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE stock_movements (
                id TEXT PRIMARY KEY,
                ingredient_id TEXT NOT NULL REFERENCES ingredients(id) ON DELETE RESTRICT,
                movement_type TEXT NOT NULL CHECK (movement_type IN ('RECEIPT', 'USAGE', 'WASTE', 'ADJUSTMENT')),
                direction TEXT NOT NULL CHECK (direction IN ('IN', 'OUT')),
                source_quantity NUMERIC NOT NULL CHECK (source_quantity > 0),
                source_unit_id TEXT NOT NULL REFERENCES units(id) ON DELETE RESTRICT,
                conversion_factor NUMERIC NOT NULL CHECK (conversion_factor > 0),
                quantity NUMERIC NOT NULL CHECK (quantity > 0),
                reason TEXT,
                reference_number TEXT,
                reference_type TEXT,
                reference_id TEXT,
                created_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                idempotency_key_id TEXT UNIQUE REFERENCES idempotency_keys(id) ON DELETE RESTRICT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE order_status_histories (
                id TEXT PRIMARY KEY,
                order_id TEXT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
                from_status TEXT CHECK (
                    from_status IS NULL OR from_status IN (
                        'DRAFT', 'CONFIRMED', 'IN_PREPARATION', 'PARTIALLY_FULFILLED',
                        'READY', 'HANDED_OVER', 'CANCELLED'
                    )
                ),
                to_status TEXT NOT NULL CHECK (
                    to_status IN (
                        'DRAFT', 'CONFIRMED', 'IN_PREPARATION', 'PARTIALLY_FULFILLED',
                        'READY', 'HANDED_OVER', 'CANCELLED'
                    )
                ),
                changed_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                changed_at TEXT NOT NULL,
                reason TEXT
            );

            CREATE TABLE production_status_histories (
                id TEXT PRIMARY KEY,
                production_id TEXT NOT NULL REFERENCES productions(id) ON DELETE RESTRICT,
                from_status TEXT CHECK (
                    from_status IS NULL OR from_status IN (
                        'NOT_STARTED', 'IN_PROGRESS', 'PARTIAL', 'COMPLETED', 'CANCELLED'
                    )
                ),
                to_status TEXT NOT NULL CHECK (
                    to_status IN ('NOT_STARTED', 'IN_PROGRESS', 'PARTIAL', 'COMPLETED', 'CANCELLED')
                ),
                changed_by TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                changed_at TEXT NOT NULL,
                reason TEXT
            );

            CREATE TABLE audit_logs (
                id TEXT PRIMARY KEY,
                actor_id TEXT REFERENCES users(id) ON DELETE SET NULL,
                request_id TEXT,
                action TEXT NOT NULL,
                entity_type TEXT NOT NULL,
                entity_id TEXT NOT NULL,
                before_json TEXT CHECK (before_json IS NULL OR json_valid(before_json)),
                after_json TEXT CHECK (after_json IS NULL OR json_valid(after_json)),
                created_at TEXT NOT NULL
            );

            CREATE UNIQUE INDEX recipe_versions_one_active_per_menu
                ON recipe_versions(menu_id) WHERE active = 1;
            CREATE UNIQUE INDEX unit_conversions_general_unique
                ON unit_conversions(from_unit_id, to_unit_id) WHERE ingredient_id IS NULL;
            CREATE UNIQUE INDEX unit_conversions_ingredient_unique
                ON unit_conversions(ingredient_id, from_unit_id, to_unit_id) WHERE ingredient_id IS NOT NULL;
            CREATE INDEX orders_target_status_index ON orders(target_at, order_status);
            CREATE INDEX orders_type_target_index ON orders(order_type, target_at);
            CREATE INDEX orders_created_at_index ON orders(created_at);
            CREATE INDEX order_requirements_ingredient_index ON order_requirements(ingredient_id);
            CREATE INDEX material_allocations_ingredient_status_index
                ON material_allocations(ingredient_id, status);
            CREATE INDEX stock_movements_ingredient_created_index
                ON stock_movements(ingredient_id, created_at);
            CREATE INDEX order_status_histories_order_changed_index
                ON order_status_histories(order_id, changed_at);
            CREATE INDEX production_status_histories_production_changed_index
                ON production_status_histories(production_id, changed_at);
            CREATE INDEX audit_logs_entity_created_index
                ON audit_logs(entity_type, entity_id, created_at);
            SQL);
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            foreach ([
                'audit_logs',
                'production_status_histories',
                'order_status_histories',
                'stock_movements',
                'material_usages',
                'production_fulfillments',
                'productions',
                'material_allocations',
                'order_requirements',
                'order_items',
                'orders',
                'idempotency_keys',
                'recipe_items',
                'recipe_versions',
                'menus',
                'unit_conversions',
                'ingredients',
                'user_roles',
                'units',
                'roles',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        });
    }
};
