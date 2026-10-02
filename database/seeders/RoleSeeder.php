<?php

namespace Database\Seeders;

use App\Models\Role;
use App\RoleCode;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleNames = [
            RoleCode::OwnerAdmin->value => 'Owner / Admin',
            RoleCode::OrderClerk->value => 'Penerima Pesanan',
            RoleCode::Kitchen->value => 'Dapur',
            RoleCode::Inventory->value => 'Persediaan',
        ];

        foreach ($roleNames as $code => $name) {
            Role::query()->updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
