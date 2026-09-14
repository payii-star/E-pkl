<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discipline_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date');

            // telat, pulang_cepat, tanpa_keterangan, tidak_kerjakan_tugas, bonus, lainnya
            $table->string('type');

            // Bisa negatif (pelanggaran) atau positif (bonus)
            $table->integer('points');

            $table->text('note')->nullable();

            // Kalau catatan ini terkait tugas tertentu (misal tipe tidak_kerjakan_tugas)
            $table->foreignId('task_id')
                ->nullable()
                ->constrained('tasks')
                ->nullOnDelete();

            // Admin yang membuat catatan ini
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipline_records');
    }
};