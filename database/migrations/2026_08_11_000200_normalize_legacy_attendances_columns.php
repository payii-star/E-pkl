<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        $this->renameColumnIfNeeded('attendances', 'tanggal', 'date');
        $this->renameColumnIfNeeded('attendances', 'jam_masuk', 'check_in_time');
        $this->renameColumnIfNeeded('attendances', 'jam_keluar', 'check_out_time');
        $this->renameColumnIfNeeded('attendances', 'foto_masuk', 'check_in_photo');
        $this->renameColumnIfNeeded('attendances', 'foto_keluar', 'check_out_photo');
        $this->renameColumnIfNeeded('attendances', 'lokasi_masuk', 'location');

        if (!Schema::hasColumn('attendances', 'location')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('location')->nullable()->after('check_out_photo');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        $this->renameColumnIfNeeded('attendances', 'date', 'tanggal');
        $this->renameColumnIfNeeded('attendances', 'check_in_time', 'jam_masuk');
        $this->renameColumnIfNeeded('attendances', 'check_out_time', 'jam_keluar');
        $this->renameColumnIfNeeded('attendances', 'check_in_photo', 'foto_masuk');
        $this->renameColumnIfNeeded('attendances', 'check_out_photo', 'foto_keluar');
        $this->renameColumnIfNeeded('attendances', 'location', 'lokasi_masuk');
    }

    private function renameColumnIfNeeded(string $table, string $from, string $to): void
    {
        if (Schema::hasColumn($table, $from) && !Schema::hasColumn($table, $to)) {
            Schema::table($table, function (Blueprint $table) use ($from, $to) {
                $table->renameColumn($from, $to);
            });
        }
    }
};
