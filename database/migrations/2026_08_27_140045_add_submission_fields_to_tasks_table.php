<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Sebelumnya ENUM('belum','sedang','selesai') — cuma nerima 3 nilai itu.
            // Diubah jadi string biasa biar bisa nampung status baru
            // ('submitted', 'revisi', 'ditolak') tanpa perlu migration lagi
            // tiap kali nambah status baru ke depannya.
            $table->string('status', 20)->default('belum')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('status', ['belum', 'sedang', 'selesai'])->default('belum')->change();
        });
    }
};