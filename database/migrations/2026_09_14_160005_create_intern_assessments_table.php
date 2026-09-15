<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GANTI isi file migration create_intern_assessments_table yang sebelumnya
 * kamu punya dengan versi ini. Nilai akhir sekarang cuma 1 per siswa
 * (bukan per periode/minggu lagi), jadi period_start/period_end dihapus.
 *
 * Kalau migration versi lama SUDAH pernah dijalankan (`php artisan migrate`),
 * jalankan dulu:
 *   php artisan migrate:rollback --step=1
 * baru migrate lagi dengan isi file yang baru ini. Kalau belum pernah
 * dijalankan sama sekali, tinggal replace isinya langsung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intern_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // Nilai akhir, diinput manual oleh admin setelah melihat rekap otomatis
            $table->unsignedTinyInteger('score');

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_assessments');
    }
};