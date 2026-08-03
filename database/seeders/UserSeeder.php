<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed 3 akun contoh: 1 HR Admin, 1 Atasan, 1 Karyawan/Intern.
     * Password default untuk semua akun contoh: 12345678
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin HR',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '08123456789',
            'status' => 'aktif',
        ]);
        $admin->assignRole('hr-admin');

        $atasan = User::create([
            'name' => 'Budi Santoso',
            'email' => 'atasan@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '08123456780',
            'posisi' => 'Supervisor',
            'status' => 'aktif',
        ]);
        $atasan->assignRole('atasan');

        $karyawan = User::create([
            'name' => 'Dika',
            'email' => 'dika@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '08123456781',
            'posisi' => 'Intern',
            'atasan_id' => $atasan->id,
            'status' => 'aktif',
        ]);
        $karyawan->assignRole('karyawan');
    }
}
