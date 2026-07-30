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
            // Ganti nama kolom 'total_amount' menjadi 'final_amount'
            $table->renameColumn('total_amount', 'final_amount');

            // Tambahkan kolom untuk harga asli dan diskon
            $table->decimal('original_amount', 15, 2)->after('user_id');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('original_amount');

            // Tambahkan kolom untuk detail promo
            $table->string('promo_code')->nullable()->after('change_amount');
            $table->foreignId('promo_id')->nullable()->constrained('promos')->after('promo_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Mengembalikan perubahan jika di-rollback
            $table->renameColumn('final_amount', 'total_amount');
            $table->dropColumn(['original_amount', 'discount_amount', 'promo_code']);
            $table->dropForeign(['promo_id']);
            $table->dropColumn('promo_id');
        });
    }
};