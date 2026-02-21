<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * MERGED — replaces both:
 *   2026_02_17_094400_add_foreign_keys_to_users_table.php
 *   2026_02_21_100000_fix_users_role_and_columns.php
 *
 * DELETE those two files, use only this one.
 *
 * Does:
 *  1. role ENUM → VARCHAR(50)
 *  2. Add phone, is_active
 *  3. Drop old string department/branch columns
 *  4. Add department_id FK
 *  5. Add branch_id FK
 *  6. Add tenant_id FK constraint
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. role: ENUM → VARCHAR (preserves existing data)
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'staff'");

        Schema::table('users', function (Blueprint $table) {

            // 2. phone
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }

            // 3. is_active
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }

            // 4. Drop old string columns
            $drop = array_filter(['department', 'branch'],
                fn($c) => Schema::hasColumn('users', $c));
            if ($drop) $table->dropColumn(array_values($drop));

            // 5. department_id FK
            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()
                      ->after('role')->constrained('departments')->nullOnDelete();
            }

            // 6. branch_id FK
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()
                      ->after('department_id')->constrained('branches')->nullOnDelete();
            }

            // 7. tenant_id FK constraint (column already exists)
            $fks = collect(Schema::getForeignKeys('users'))->pluck('name');
            if (! $fks->contains('users_tenant_id_foreign')) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['branch_id', 'department_id'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropForeign([$col]);
                    $table->dropColumn($col);
                }
            }
            try { $table->dropForeign(['tenant_id']); } catch (\Throwable) {}

            if (! Schema::hasColumn('users', 'department')) $table->string('department')->nullable();
            if (! Schema::hasColumn('users', 'branch'))     $table->string('branch')->nullable();
            if (Schema::hasColumn('users', 'is_active'))    $table->dropColumn('is_active');
            if (Schema::hasColumn('users', 'phone'))        $table->dropColumn('phone');
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','principal','vp','dept_head','teacher','staff') NOT NULL DEFAULT 'staff'");
    }
};