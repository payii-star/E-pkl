<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Buat role dasar untuk sistem HR.
     * Sinkronisasi permission dilakukan terpisah di PermissionSeeder
     * (dijalankan setelah seeder ini di DatabaseSeeder).
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::updateOrCreate(
            ['name' => 'hr-admin', 'guard_name' => 'api'],
            ['full_name' => 'HR Admin']
        );

        Role::updateOrCreate(
            ['name' => 'atasan', 'guard_name' => 'api'],
            ['full_name' => 'Atasan / Supervisor']
        );

        Role::updateOrCreate(
            ['name' => 'karyawan', 'guard_name' => 'api'],
            ['full_name' => 'Karyawan / Intern']
        );

        Role::updateOrCreate(
            ['name' => 'admin-landing', 'guard_name' => 'api'],
            ['full_name' => 'Admin Landing Page']
        );
    }
}