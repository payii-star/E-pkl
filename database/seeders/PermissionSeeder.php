<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Definisikan semua grup izin untuk sistem HR
        $menuDashboard = ['dashboard'];
        $menuAdmin = ['admin-dashboard', 'admin-attendance-recap'];
        $menuAttendance = ['attendance-check', 'attendance-history'];
        $menuJournal = ['journal-my', 'journal-history'];
        $menuJournalApproval = ['journal-approval'];
        $menuMaster = ['master-user', 'master-role'];
        $menuAccount = ['dashboard-profile', 'setting'];
        $menuLanding = ['landing-management'];

        $permissionsByRole = [
            // HR Admin: akses penuh, termasuk manajemen user & role & landing
            'hr-admin' => array_merge(
                $menuDashboard,
                $menuAdmin,
                $menuAttendance,
                $menuJournal,
                $menuJournalApproval,
                $menuMaster,
                $menuAccount,
                $menuLanding
            ),
            // Atasan/Supervisor: absen + jurnal + approval, tanpa manajemen user
            'atasan' => array_merge(
                $menuDashboard,
                $menuAttendance,
                $menuJournal,
                $menuJournalApproval,
                $menuAccount
            ),
            // Karyawan/Intern: absen + jurnal (tanpa approval, tanpa master)
            'karyawan' => array_merge(
                $menuDashboard,
                $menuAttendance,
                $menuJournal,
                $menuAccount
            ),
            // Admin Landing: CUMA bisa kelola konten landing page + akun sendiri,
            // nggak bisa lihat data absensi/jurnal/intern sama sekali
            'admin-landing' => array_merge(
                $menuLanding,
                $menuAccount
            ),
        ];

        // Buat semua permission (union dari semua role, tanpa duplikat)
        $allPermissions = collect($permissionsByRole)->flatten()->unique();
        foreach ($allPermissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'api',
            ]);
        }

        // Sinkronkan permission ke masing-masing role
        foreach ($permissionsByRole as $roleName => $permissionNames) {
            $role = Role::whereName($roleName)->where('guard_name', 'api')->first();
            if ($role) {
                $role->syncPermissions($permissionNames);
            }
        }
    }
}