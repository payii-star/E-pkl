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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->integer('quantity_change'); // Positif untuk stok masuk, negatif untuk stok keluar
            $table->string('type'); // Contoh: 'sale', 'initial_stock', 'adjustment', 'return'
            $table->text('notes')->nullable(); // Contoh: "Transaksi INV-123" atau "Penyesuaian stok oleh admin"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
