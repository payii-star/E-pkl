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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama promo, misal: "Diskon Kemerdekaan"
            $table->string('code')->unique(); // Kode unik, misal: "MERDEKA17"
            $table->enum('type', ['percentage', 'fixed_amount']); // Jenis diskon
            $table->decimal('value', 15, 2); // Nilai diskon (misal: 15 untuk 15% atau 10000 untuk Rp 10.000)
            $table->decimal('min_purchase', 15, 2)->default(0); // Syarat minimum pembelian
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
