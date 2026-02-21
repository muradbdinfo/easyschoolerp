<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merged migration — replaces 2026_02_17_094400_add_foreign_keys_to_users_table.php
 *
 * That migration tried to add FK constraints on `department_id` and `branch_id`
 * but those integer columns never existed (only string `department` and `branch`
 * were added earlier). This migration fixes the full picture:
 *
 *  1. Adds `phone`         (varchar 30, nullable)
 *  2. Adds `is_active`     (boolean, default true)
 *  3. Drops old string     `department` and `branch` columns
 *  4. Adds `department_id` (unsignedBigInt FK → departments.id)
 *  5. Adds `branch_id`     (unsignedBigInt FK → branches.id)
 *  6. Adds FK constraint   on `tenant_id` → tenants.id  (from the old migration)
 *
 * All steps are guarded with hasColumn() / hasForeignKey() so it's safe
 * whether the old migration ran or not.
 *
 * DEPLOY STEPS:
 *  1. DELETE database/migrations/2026_02_17_094400_add_foreign_keys_to_users_table.php
 *  2. Copy this file to database/migrations/
 *  3. php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // ── 1. Phone ────────────────────────────────────────────────
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }

            // ── 2. is_active ────────────────────────────────────────────
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }

            // ── 3. Drop old string columns ──────────────────────────────
            $toDrop = [];
            if (Schema::hasColumn('users', 'department')) {
                $toDrop[] = 'department';
            }
            if (Schema::hasColumn('users', 'branch')) {
                $toDrop[] = 'branch';
            }
            if (! empty($toDrop)) {
                $table->dropColumn($toDrop);
            }

            // ── 4. department_id (integer FK column + constraint) ───────
            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')
                      ->nullable()
                      ->after('role')
                      ->constrained('departments')
                      ->nullOnDelete();
            }

            // ── 5. branch_id (integer FK column + constraint) ───────────
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')
                      ->nullable()
                      ->after('department_id')
                      ->constrained('branches')
                      ->nullOnDelete();
            }

            // ── 6. tenant_id FK constraint ──────────────────────────────
            // Use Laravel 11 native method (Doctrine removed in L11)
            $existingFKNames = collect(Schema::getForeignKeys('users'))
                ->pluck('name');

            if (! $existingFKNames->contains('users_tenant_id_foreign')) {
                $table->foreign('tenant_id')
                      ->references('id')->on('tenants')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Drop branch_id FK + column
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }

            // Drop department_id FK + column
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }

            // Drop tenant_id FK constraint (column stays — owned by earlier migration)
            try {
                $table->dropForeign(['tenant_id']);
            } catch (\Throwable) {
                // already gone — ignore
            }

            // Restore old string columns
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable();
            }
            if (! Schema::hasColumn('users', 'branch')) {
                $table->string('branch')->nullable();
            }

            // Remove new columns
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};