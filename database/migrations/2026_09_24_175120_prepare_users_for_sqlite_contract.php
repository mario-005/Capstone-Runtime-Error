<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $hasLegacyUsers = Schema::hasTable('users_legacy');

        if (! $hasLegacyUsers && Schema::hasColumn('users', 'password_hash')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::statement('CREATE TEMPORARY TABLE user_id_mappings (old_id TEXT PRIMARY KEY, new_id TEXT NOT NULL UNIQUE)');

            if ($hasLegacyUsers) {
                Schema::dropIfExists('users');
            } else {
                Schema::rename('users', 'users_legacy');
            }

            DB::statement('DROP INDEX IF EXISTS users_email_unique');

            DB::statement(<<<'SQL'
                INSERT INTO user_id_mappings (old_id, new_id)
                SELECT CAST(id AS TEXT),
                    lower(
                        hex(randomblob(4)) || '-' || hex(randomblob(2)) || '-4' ||
                        substr(hex(randomblob(2)), 2) || '-' ||
                        substr('89ab', abs(random()) % 4 + 1, 1) ||
                        substr(hex(randomblob(2)), 2) || '-' || hex(randomblob(6))
                    )
                FROM users_legacy
                SQL);

            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 120);
                $table->string('email')->unique();
                $table->string('password_hash');
                $table->boolean('active')->default(true);
                $table->timestamp('created_at');
                $table->timestamp('updated_at');
            });

            DB::statement(<<<'SQL'
                INSERT INTO users (id, name, email, password_hash, active, created_at, updated_at)
                SELECT user_id_mappings.new_id, users_legacy.name, users_legacy.email,
                    users_legacy.password, 1, users_legacy.created_at, users_legacy.updated_at
                FROM users_legacy
                INNER JOIN user_id_mappings ON user_id_mappings.old_id = CAST(users_legacy.id AS TEXT)
                SQL);

            DB::statement(<<<'SQL'
                UPDATE sessions
                SET user_id = (
                    SELECT new_id
                    FROM user_id_mappings
                    WHERE old_id = CAST(sessions.user_id AS TEXT)
                )
                WHERE user_id IS NOT NULL
                SQL);

            Schema::drop('users_legacy');
            DB::statement('DROP TABLE user_id_mappings');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void {}
};
