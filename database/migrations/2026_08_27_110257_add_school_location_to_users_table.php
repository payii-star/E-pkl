<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'asal_instansi_address')) {
                $table->string('asal_instansi_address')->nullable()->after('asal_instansi');
            }

            if (!Schema::hasColumn('users', 'asal_instansi_latitude')) {
                $table->decimal('asal_instansi_latitude', 10, 7)->nullable()->after('asal_instansi_address');
            }

            if (!Schema::hasColumn('users', 'asal_instansi_longitude')) {
                $table->decimal('asal_instansi_longitude', 10, 7)->nullable()->after('asal_instansi_latitude');
            }

            if (!Schema::hasColumn('users', 'asal_instansi_place_id')) {
                $table->string('asal_instansi_place_id')->nullable()->after('asal_instansi_longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'asal_instansi_address',
                'asal_instansi_latitude',
                'asal_instansi_longitude',
                'asal_instansi_place_id',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
