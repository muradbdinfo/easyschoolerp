<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix existing NULL rows left by seeders/manual inserts
        DB::table('vendors')->whereNull('type')->update(['type' => 'supplier']);
        DB::table('vendors')->whereNull('status')->update(['status' => 'active']);
    }

    public function down(): void {}
};