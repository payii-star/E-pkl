<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_modify_products_table_for_variants.php
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Kita buat kolom ini boleh null karena nilainya akan pindah ke product_variants
            $table->string('sku')->nullable()->change();
            $table->decimal('price', 15, 2)->nullable()->change();
            $table->integer('stock')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Logika untuk mengembalikan jika di-rollback
            $table->string('sku')->nullable(false)->change();
            $table->decimal('price', 15, 2)->nullable(false)->change();
            $table->integer('stock')->nullable(false)->change();
        });
    }
};
