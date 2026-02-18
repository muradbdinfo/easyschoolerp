<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // All three referenced tables now exist — safe to add constraints
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->nullOnDelete();

            $table->foreign('department_id')
                  ->references('id')->on('departments')
                  ->nullOnDelete();

            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['branch_id']);
        });
    }
};