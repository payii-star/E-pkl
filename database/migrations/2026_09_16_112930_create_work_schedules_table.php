<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('day')->unique(); // monday, tuesday, ..., sunday
            $table->boolean('is_working_day')->default(true);
            $table->time('start_time')->default('08:00:00');   // Jam Masuk
            $table->time('end_time')->default('16:00:00');     // Jam Pulang
            $table->time('min_check_in_time')->default('00:00:00'); // Minimal Jam Masuk
            $table->time('max_check_out_time')->default('23:59:00'); // Maksimal Jam Pulang
            $table->timestamps();
        });

        // Default: Senin-Jumat kerja 08:00-16:00, Sabtu-Minggu libur.
        // Bisa diedit admin lewat halaman Pengaturan Hari & Jam Kerja.
        $workdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $weekend = ['saturday', 'sunday'];

        $now = now();

        foreach ($workdays as $day) {
            DB::table('work_schedules')->insert([
                'day' => $day,
                'is_working_day' => true,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'min_check_in_time' => '00:00:00',
                'max_check_out_time' => '23:59:00',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($weekend as $day) {
            DB::table('work_schedules')->insert([
                'day' => $day,
                'is_working_day' => false,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'min_check_in_time' => '00:00:00',
                'max_check_out_time' => '23:59:00',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};