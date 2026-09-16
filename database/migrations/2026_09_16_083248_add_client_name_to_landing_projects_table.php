<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_projects', function (Blueprint $table) {
            // Nama client/instansi pemilik project.
            // Ditampilkan singkat di kartu list /projects.
            // Deskripsi lengkap tetap di kolom "description",
            // hanya muncul di halaman detail.
            $table->string('client_name')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('landing_projects', function (Blueprint $table) {
            $table->dropColumn('client_name');
        });
    }
};