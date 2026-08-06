<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'kegiatan')) {
                $table->dropColumn('kegiatan');
            }
            if (Schema::hasColumn('journals', 'kendala')) {
                $table->dropColumn('kendala');
            }
            if (Schema::hasColumn('journals', 'solusi')) {
                $table->dropColumn('solusi');
            }
        });

        Schema::create('journal_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->text('kegiatan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_activities');

        Schema::table('journals', function (Blueprint $table) {
            $table->text('kegiatan')->nullable();
            $table->text('kendala')->nullable();
            $table->text('solusi')->nullable();
        });
    }
};