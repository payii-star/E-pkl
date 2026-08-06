<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'tanggal') && !Schema::hasColumn('journals', 'date')) {
                $table->renameColumn('tanggal', 'date');
            }

            if (Schema::hasColumn('journals', 'aktivitas')) {
                $table->dropColumn('aktivitas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'date') && !Schema::hasColumn('journals', 'tanggal')) {
                $table->renameColumn('date', 'tanggal');
            }

            if (!Schema::hasColumn('journals', 'aktivitas')) {
                $table->text('aktivitas')->nullable();
            }
        });
    }
};