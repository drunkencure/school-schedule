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
        Schema::create('academies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaultAcademyId = DB::table('academies')->insertGetId([
            'name' => '기본 학원',
            'address' => null,
            'memo' => '기존 데이터 기본 학원',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::create('academy_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['academy_id', 'user_id']);
        });

        Schema::create('academy_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['academy_id', 'student_id']);
            $table->unique(['student_id']);
        });

        $instructorIds = DB::table('users')
            ->where('role', 'instructor')
            ->pluck('id');
        if ($instructorIds->isNotEmpty()) {
            $rows = $instructorIds->map(function ($id) use ($defaultAcademyId, $now) {
                return [
                    'academy_id' => $defaultAcademyId,
                    'user_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();
            DB::table('academy_user')->insert($rows);
        }

        $studentIds = DB::table('students')->pluck('id');
        if ($studentIds->isNotEmpty()) {
            $rows = $studentIds->map(function ($id) use ($defaultAcademyId, $now) {
                return [
                    'academy_id' => $defaultAcademyId,
                    'student_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();
            DB::table('academy_student')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_student');
        Schema::dropIfExists('academy_user');
        Schema::dropIfExists('academies');
    }
};
