<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('users')->where('login_id', 'admin')->exists()) {
            return;
        }

        $now = now();

        DB::table('users')->insert([
            'login_id' => 'admin',
            'name' => '전체 관리자',
            'email' => 'admin@local.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('login_id', 'admin')->delete();
    }
};
