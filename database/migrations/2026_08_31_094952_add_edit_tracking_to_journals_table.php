<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (! Schema::hasColumn('journals', 'last_edited_at')) {
                $table->timestamp('last_edited_at')->nullable()->after('catatan_approval');
            }
            if (! Schema::hasColumn('journals', 'edit_count')) {
                $table->unsignedInteger('edit_count')->default(0)->after('last_edited_at');
            }
        });

        // Unique constraint dibuat terpisah supaya migration tidak gagal
        // kalau kebetulan sudah ada data duplikat lama.
        Schema::table('journals', function (Blueprint $table) {
            $table->unique(['user_id', 'date'], 'journals_user_id_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique('journals_user_id_date_unique');
            $table->dropColumn(['last_edited_at', 'edit_count']);
        });
    }
};