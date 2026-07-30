<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Definisikan semua grup izin
        $menuDashboard = ['dashboard'];
        $menuMaster = ['master', 'master-user', 'master-role', 'master-products', 'master-category', 'master-variant', 'master-promo', 'master-members', 'master-point'];
        $menuWebsite = ['website', 'setting'];
        $menuApps = ['apps-pos-cashier']; 
        $menuInventory = ['inventory-stock'];
        $menuTransactions = ['transaction-history'];
        $menuReports = ['view-reports']; 
        $menuHIstory = ['stock-history'];

        // Gabungkan semua izin untuk role 'admin'
        $permissionsByRole = [
            'admin' => array_merge($menuDashboard, $menuMaster, $menuWebsite, $menuApps, $menuInventory, $menuTransactions, $menuReports, $menuHIstory),
            'user' => $menuDashboard,
        ];

        // Buat permissions
        foreach ($permissionsByRole as $role => $permissions) {
            foreach ($permissions as $name) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'api'
                ]);
            }
        }
        
        // Berikan permissions ke roles
        foreach ($permissionsByRole as $roleName => $permissionNames) {
            $role = Role::whereName($roleName)->first();
            if ($role) {
                $role->syncPermissions($permissionNames);
            }
        }
    }
}