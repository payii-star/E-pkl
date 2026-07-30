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
        Schema::table('members', function (Blueprint $table) {
            // Kolom untuk ID unik kartu member virtual.
            // Menggunakan UUID lebih disarankan daripada ID biasa.
            // 'after('email')' hanya untuk merapikan urutan kolom.
            $table->uuid('member_id')->unique()->after('email')->nullable();

            // Kolom untuk menyimpan poin member.
            // 'unsignedInteger' berarti hanya angka positif.
            // 'default(0)' agar member baru otomatis punya 0 poin.
            $table->unsignedInteger('points')->default(0)->after('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('member_id');
            $table->dropColumn('points');
        });
    }
};