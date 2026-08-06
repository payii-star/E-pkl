<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intern_id')->constrained('interns')->cascadeOnDelete();
            $table->json('descriptor');          // array 128 nilai dari face-api.js
            $table->string('photo')->nullable(); // foto referensi (opsional)
            $table->timestamps();

            $table->unique('intern_id'); // 1 intern = 1 face profile
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_profiles');
    }
};