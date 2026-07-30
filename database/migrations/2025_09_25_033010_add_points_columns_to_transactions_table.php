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
        Schema::table('transactions', function (Blueprint $table) {
            // Kolom untuk mencatat jumlah poin yang ditukar.
            $table->unsignedInteger('points_redeemed')->default(0)->after('discount_amount');

            // Kolom untuk mencatat nilai Rupiah dari poin yang ditukar.
            $table->unsignedBigInteger('point_discount_amount')->default(0)->after('points_redeemed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['points_redeemed', 'point_discount_amount']);
        });
    }
};