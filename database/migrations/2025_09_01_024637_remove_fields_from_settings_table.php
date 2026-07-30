<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Kolom yang ingin dihapus
            $table->dropColumn(['pemerintah', 'dinas', 'banner']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Jika butuh rollback, buat kembali kolomnya
            $table->string('pemerintah')->nullable();
            $table->string('dinas')->nullable();
            $table->string('banner')->nullable();
        });
    }
};