<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nim_nis')->nullable()->after('phone');
            $table->string('asal_instansi')->nullable()->after('nim_nis');
            $table->string('posisi')->nullable()->after('asal_instansi');
            $table->date('tanggal_mulai')->nullable()->after('posisi');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->foreignId('atasan_id')->nullable()->after('tanggal_selesai')
                ->constrained('users')->nullOnDelete();
            $table->enum('status', ['aktif', 'selesai', 'nonaktif'])->default('aktif')->after('atasan_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atasan_id');
            $table->dropColumn([
                'nim_nis',
                'asal_instansi',
                'posisi',
                'tanggal_mulai',
                'tanggal_selesai',
                'status',
            ]);
        });
    }
};
