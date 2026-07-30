<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat atau perbarui role 'admin' dengan full_name
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin', 'guard_name' => 'api'],
            ['full_name' => 'Administrator']
        );
        
        $allPermissions = Permission::pluck('id')->all();
        $adminRole->syncPermissions($allPermissions);

        // Buat atau perbarui role 'kasir' dengan full_name
        $cashierRole = Role::updateOrCreate(
            ['name' => 'kasir', 'guard_name' => 'api'],
            ['full_name' => 'Kasir']
        );
        
        $cashierPermissions = [
            'dashboard',
            'apps-pos-cashier'
        ];
        $cashierRole->syncPermissions($cashierPermissions);

        // Berikan role 'admin' ke user pertama (atau user dengan email tertentu)
        $adminUser = User::where('email', 'admin@example.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }
    }
}