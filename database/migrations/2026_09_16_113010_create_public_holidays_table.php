<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('label');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Cuma tanggal merah yang TETAP tiap tahun yang di-seed otomatis.
        // Tanggal merah yang geser tiap tahun (Lebaran, Nyepi, Waisak,
        // Isra Mikraj, Imlek, cuti bersama, dll) HARUS diinput manual oleh
        // admin lewat halaman Pengaturan Hari & Jam Kerja, karena tanggal
        // pastinya di tahun berjalan tidak bisa dipastikan otomatis di sini.
        $now = now();

        $fixedHolidays = [
            ['date' => '2026-01-01', 'label' => 'Tahun Baru Masehi'],
            ['date' => '2026-05-01', 'label' => 'Hari Buruh Internasional'],
            ['date' => '2026-08-17', 'label' => 'Hari Kemerdekaan RI'],
            ['date' => '2026-12-25', 'label' => 'Hari Natal'],
        ];

        foreach ($fixedHolidays as $holiday) {
            DB::table('public_holidays')->insert([
                'date' => $holiday['date'],
                'label' => $holiday['label'],
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};