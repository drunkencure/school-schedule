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
        Schema::table('academy_user', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('user_id');
        });

        DB::statement("UPDATE academy_user au JOIN users u ON au.user_id = u.id SET au.status = u.status WHERE au.status = 'approved'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_user', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
