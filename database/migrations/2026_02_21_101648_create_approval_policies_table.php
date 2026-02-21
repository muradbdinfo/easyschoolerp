<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PERMANENT SOLUTION — replaces these previous migrations:
 *
 *   2026_02_21_000003_approval_policies_and_role_varchar.php
 *   2026_02_21_000004_expand_pr_approval_to_6_levels.php
 *   2026_02_21_000005_policy_multi_role_drop_level6.php
 *
 * DELETE those three files. Keep only this one.
 *
 * What this does (safe to run on fresh DB or existing DB):
 *
 *  1. users.role            → VARCHAR(50)   (was ENUM)
 *  2. approval_policies     → created fresh with role_name as JSON
 *  3. purchase_requisitions → levels 4 & 5 added (levels 1–3 already exist)
 *                          → status ENUM expanded to pending_level_4/5
 */
return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        // ── 1. users.role: ENUM → VARCHAR ─────────────────────────────────
        // Safe: MySQL preserves existing values when widening to VARCHAR
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'staff'
        ");

        // ── 2. approval_policies table ────────────────────────────────────
        // role_name is JSON from the start — no conversion headaches
        if (! Schema::hasTable('approval_policies')) {
            Schema::create('approval_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')
                      ->nullable()
                      ->constrained('tenants')
                      ->cascadeOnDelete();
                $table->string('name');
                $table->unsignedTinyInteger('level');
                $table->decimal('min_amount', 15, 2)->default(0);
                $table->decimal('max_amount', 15, 2)->nullable(); // null = no limit
                $table->json('role_name');                         // e.g. ["PO_DMD","PO_MD"]
                $table->boolean('is_active')->default(true);
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['tenant_id', 'level', 'is_active']);
                $table->index(['min_amount', 'max_amount']);
            });
        }

        // ── 3. purchase_requisitions: add levels 4 & 5 ───────────────────
        Schema::table('purchase_requisitions', function (Blueprint $table) {

            // Level 4
            if (! Schema::hasColumn('purchase_requisitions', 'level_4_approver_id')) {
                $table->foreignId('level_4_approver_id')
                      ->nullable()->after('level_3_comments')
                      ->constrained('users');
                $table->timestamp('level_4_approved_at')->nullable()->after('level_4_approver_id');
                $table->text('level_4_comments')->nullable()->after('level_4_approved_at');
                $table->enum('level_4_status', ['pending','approved','rejected'])
                      ->nullable()->after('level_4_comments');
            }

            // Level 5
            if (! Schema::hasColumn('purchase_requisitions', 'level_5_approver_id')) {
                $table->foreignId('level_5_approver_id')
                      ->nullable()->after('level_4_status')
                      ->constrained('users');
                $table->timestamp('level_5_approved_at')->nullable()->after('level_5_approver_id');
                $table->text('level_5_comments')->nullable()->after('level_5_approved_at');
                $table->enum('level_5_status', ['pending','approved','rejected'])
                      ->nullable()->after('level_5_comments');
            }
        });

        // ── 4. Expand status ENUM → include pending_level_4 / 5 ──────────
        // Only if not already expanded (idempotent)
        $currentDef = $this->getEnumValues('purchase_requisitions', 'status');

        if (! in_array('pending_level_4', $currentDef)) {
            DB::statement("
                ALTER TABLE purchase_requisitions
                MODIFY COLUMN status ENUM(
                    'draft','submitted',
                    'pending_level_1','pending_level_2','pending_level_3',
                    'pending_level_4','pending_level_5',
                    'approved','rejected','cancelled','closed'
                ) NOT NULL DEFAULT 'draft'
            ");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        // Remove level 4/5 columns
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            foreach (['level_4_approver_id','level_5_approver_id'] as $fk) {
                if (Schema::hasColumn('purchase_requisitions', $fk)) {
                    $table->dropForeign([$fk]);
                }
            }
            $cols = [
                'level_4_approver_id','level_4_approved_at','level_4_comments','level_4_status',
                'level_5_approver_id','level_5_approved_at','level_5_comments','level_5_status',
            ];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('purchase_requisitions', $c));
            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });

        // Shrink status enum back
        DB::statement("
            ALTER TABLE purchase_requisitions
            MODIFY COLUMN status ENUM(
                'draft','submitted',
                'pending_level_1','pending_level_2','pending_level_3',
                'approved','rejected','cancelled','closed'
            ) NOT NULL DEFAULT 'draft'
        ");

        Schema::dropIfExists('approval_policies');

        // Restore role enum
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('admin','principal','vp','dept_head','teacher','staff')
            NOT NULL DEFAULT 'staff'
        ");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: read current ENUM values from information_schema
    // Used to make the status ENUM modification idempotent
    // ─────────────────────────────────────────────────────────────────────────
    private function getEnumValues(string $table, string $column): array
    {
        $row = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
        ", [$table, $column]);

        if (! $row) return [];

        // COLUMN_TYPE looks like: enum('draft','submitted',...)
        preg_match_all("/'([^']+)'/", $row->COLUMN_TYPE, $matches);

        return $matches[1] ?? [];
    }
};