<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nim_nis')) {
                $table->string('nim_nis')->nullable()->after('photo');
            }

            if (!Schema::hasColumn('users', 'asal_instansi')) {
                $table->string('asal_instansi')->nullable()->after('nim_nis');
            }

            if (!Schema::hasColumn('users', 'posisi')) {
                $table->string('posisi')->nullable()->after('asal_instansi');
            }

            if (!Schema::hasColumn('users', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable()->after('posisi');
            }

            if (!Schema::hasColumn('users', 'tanggal_selesai')) {
                $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            }

            if (!Schema::hasColumn('users', 'atasan_id')) {
                $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete()->after('tanggal_selesai');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['aktif', 'selesai', 'nonaktif'])->default('aktif')->after('atasan_id');
            }
        });

        if (!Schema::hasTable('face_profiles')) {
            Schema::create('face_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->json('descriptor');
                $table->string('photo')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('interns') && Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'user_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('intern_id');
            });

            DB::table('attendances')
                ->join('interns', 'attendances.intern_id', '=', 'interns.id')
                ->update(['attendances.user_id' => DB::raw('interns.user_id')]);
        }

        if (Schema::hasTable('interns') && Schema::hasTable('journals') && !Schema::hasColumn('journals', 'user_id')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('intern_id');
            });

            DB::table('journals')
                ->join('interns', 'journals.intern_id', '=', 'interns.id')
                ->update(['journals.user_id' => DB::raw('interns.user_id')]);
        }

        if (Schema::hasTable('interns') && Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'user_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('intern_id');
            });

            DB::table('tasks')
                ->join('interns', 'tasks.intern_id', '=', 'interns.id')
                ->update(['tasks.user_id' => DB::raw('interns.user_id')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('face_profiles')) {
            Schema::dropIfExists('face_profiles');
        }

        foreach (['attendances', 'journals', 'tasks'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropColumn('user_id');
                });
            }
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'atasan_id')) {
                $table->dropConstrainedForeignId('atasan_id');
            }

            foreach (['nim_nis', 'asal_instansi', 'posisi', 'tanggal_mulai', 'tanggal_selesai', 'status'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};