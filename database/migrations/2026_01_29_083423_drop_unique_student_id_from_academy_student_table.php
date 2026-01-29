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
        Schema::table('academy_student', function (Blueprint $table) {
            $table->index('student_id', 'academy_student_student_id_index');
            $table->dropUnique('academy_student_student_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_student', function (Blueprint $table) {
            $table->dropIndex('academy_student_student_id_index');
            $table->unique(['student_id']);
        });
    }
};
