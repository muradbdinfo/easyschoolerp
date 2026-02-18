<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix existing NULL rows left by seeders/manual inserts
        DB::table('items')->whereNull('type')->update(['type' => 'consumable']);
        DB::table('items')->whereNull('status')->update(['status' => 'active']);
        DB::table('items')->whereNull('unit')->update(['unit' => 'pcs']);
    }

    public function down(): void {}
};