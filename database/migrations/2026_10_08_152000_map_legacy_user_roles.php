<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'user')->update(['role' => 'viewer']);
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'editor']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'viewer')->update(['role' => 'user']);
        DB::table('users')->where('role', 'editor')->update(['role' => 'supervisor']);
    }
};
