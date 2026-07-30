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
        Schema::table('product_variants', function (Blueprint $table) {
            // Tambahkan kolom baru setelah kolom 'stock'
            $table->unsignedInteger('reserved_stock')->default(0)->after('stock');
        });
    }
    
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('reserved_stock');
        });
    }
};
