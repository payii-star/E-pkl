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
        if (!Schema::hasTable('intern_assessments')) {
            return;
        }

        Schema::table('intern_assessments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('intern_assessments', function (Blueprint $table) {
            $table->dropUnique('intern_assessments_user_id_period_start_period_end_unique');
        });

        Schema::table('intern_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('intern_assessments', 'period_start')) {
                $table->dropColumn('period_start');
            }

            if (Schema::hasColumn('intern_assessments', 'period_end')) {
                $table->dropColumn('period_end');
            }
        });

        Schema::table('intern_assessments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intern_assessments', function (Blueprint $table) {
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
        });
    }
};
