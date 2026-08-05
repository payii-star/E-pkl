<?php

namespace Database\Seeders;

use App\Models\Intern;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed 3 akun contoh: 1 HR Admin, 1 Atasan, 1 Karyawan/Intern.
     * Data khusus peserta magang (institusi, tanggal mulai/selesai, pembimbing)
     * disimpan di tabel `interns`, bukan lagi di tabel `users`.
     * Password default untuk semua akun contoh: 12345678
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin HR',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '08123456789',
        ]);
        $admin->assignRole('hr-admin');

        $atasan = User::create([
            'name' => 'Budi Santoso',
            'email' => 'atasan@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '08123456780',
        ]);
        $atasan->assignRole('atasan');

        $karyawan = User::create([
            'name' => 'Dika',
            'email' => 'dika@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '08123456781',
        ]);
        $karyawan->assignRole('karyawan');

        Intern::create([
            'user_id' => $karyawan->id,
            'institusi_asal' => 'SMK Contoh 1',
            'pembimbing_id' => $atasan->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(2),
        ]);
    }
}
