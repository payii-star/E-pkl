<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed 3 akun contoh: 1 HR Admin, 1 Atasan, 1 Karyawan/Intern.
     * Data khusus peserta magang (institusi, tanggal mulai/selesai, atasan)
     * disimpan langsung di tabel `users`.
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
            'status' => 'aktif',
        ]);
        $atasan->assignRole('atasan');

        $karyawan = User::create([
            'name' => 'Dika',
            'email' => 'dika@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '08123456781',
            'asal_instansi' => 'SMK Contoh 1',
            'posisi' => 'Intern',
            'atasan_id' => $atasan->id,
            'tanggal_mulai' => now()->subMonth(),
            'tanggal_selesai' => now()->addMonths(2),
            'status' => 'aktif',
        ]);
        $karyawan->assignRole('karyawan');
    }
}
