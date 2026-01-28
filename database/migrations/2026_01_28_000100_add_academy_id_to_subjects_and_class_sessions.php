<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $academyId = DB::table('academies')->value('id');
        $hasIndex = function (string $table, string $index): bool {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('database()'))
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        };

        if (! Schema::hasColumn('subjects', 'academy_id')) {
            Schema::table('subjects', function (Blueprint $table) use ($academyId) {
                $table->foreignId('academy_id')
                    ->default($academyId)
                    ->constrained('academies')
                    ->cascadeOnDelete()
                    ->after('id');
            });
        }

        if ($hasIndex('subjects', 'subjects_name_unique')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropUnique('subjects_name_unique');
            });
        }

        if (! $hasIndex('subjects', 'subjects_academy_id_name_unique')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique(['academy_id', 'name']);
            });
        }

        if (! Schema::hasColumn('class_sessions', 'academy_id')) {
            Schema::table('class_sessions', function (Blueprint $table) use ($academyId) {
                $table->foreignId('academy_id')
                    ->default($academyId)
                    ->constrained('academies')
                    ->cascadeOnDelete()
                    ->after('id');
            });
        }

        $hasInstructorIndex = $hasIndex('class_sessions', 'class_sessions_instructor_id_index');

        if (! $hasInstructorIndex) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->index('instructor_id');
            });
        }

        if ($hasIndex('class_sessions', 'class_sessions_instructor_id_weekday_start_time_unique')) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->dropUnique('class_sessions_instructor_id_weekday_start_time_unique');
            });
        }

        $academyInstructorIndex = 'cls_acad_instr_week_start_unique';
        if (! $hasIndex('class_sessions', $academyInstructorIndex)) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->unique(['academy_id', 'instructor_id', 'weekday', 'start_time'], 'cls_acad_instr_week_start_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropUnique('cls_acad_instr_week_start_unique');
            $table->unique(['instructor_id', 'weekday', 'start_time']);
            $table->dropConstrainedForeignId('academy_id');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['academy_id', 'name']);
            $table->unique('name');
            $table->dropConstrainedForeignId('academy_id');
        });
    }
};
