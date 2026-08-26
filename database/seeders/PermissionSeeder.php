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
        // Ditambahkan 'admin-face-management' — sebelumnya permission ini
        // dipakai di MainMenuConfig.ts tapi TIDAK PERNAH dibuat/di-assign
        // ke role manapun, jadi menu "Face Management" sebelumnya tidak
        // pernah muncul untuk siapapun.
        $menuAdmin = ['admin-dashboard', 'admin-attendance-recap', 'admin-face-management'];
        $menuAttendance = ['attendance-check', 'attendance-history'];
        $menuJournal = ['journal-my', 'journal-history'];
        $menuJournalApproval = ['journal-approval'];
        $menuMaster = ['master-user', 'master-role'];
        $menuAccount = ['dashboard-profile', 'setting'];
        $menuLanding = ['landing-management'];
        $menuTask = ['task-management'];

        $permissionsByRole = [
            // HR Admin ("admin biasa"): HANYA 4 menu admin-level + Akun.
            // TIDAK lagi dapat: Dashboard pribadi, Absensi pribadi,
            // Jurnal pribadi, Master (User & Role), Kelola Landing.
            'hr-admin' => array_merge(
                $menuAdmin,
                $menuJournalApproval,
                $menuTask,
                $menuAccount
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
            // Admin Landing: CUMA bisa kelola konten landing page + akun sendiri
            'admin-landing' => array_merge(
                $menuLanding,
                $menuAccount
            ),
        ];

        // CATATAN PENTING: karena 'hr-admin' tidak lagi memuat $menuMaster,
        // dan tidak ada role lain yang memuatnya, permission 'master-user'
        // dan 'master-role' TIDAK AKAN dibuat sama sekali (karena
        // $allPermissions di bawah hanya union dari array di atas).
        // Efeknya: section "Master" (User & Role) di MainMenuConfig.ts
        // otomatis tidak akan tampil untuk SIAPAPUN, termasuk hr-admin.
        // Kalau ini TIDAK diinginkan, beri tahu saya — tinggal balikin
        // $menuMaster ke salah satu role (biasanya hr-admin).

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