<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_projects', function (Blueprint $table) {
            // Menyimpan array path foto tambahan (maks 6, divalidasi di controller)
            $table->json('gallery')->nullable()->after('thumbnail');
        });
    }

    public function down(): void
    {
        Schema::table('landing_projects', function (Blueprint $table) {
            $table->dropColumn('gallery');
        });
    }
};