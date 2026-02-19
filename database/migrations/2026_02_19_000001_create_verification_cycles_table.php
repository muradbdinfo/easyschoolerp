<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->year('cycle_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('scope', ['all', 'branch', 'category'])->default('all');
            $table->json('scope_ids')->nullable(); // branch/category IDs
            $table->integer('total_assets')->default(0);
            $table->integer('verified_count')->default(0);
            $table->integer('discrepancy_count')->default(0);
            $table->enum('status', ['planning', 'in_progress', 'completed', 'cancelled'])->default('planning');
            $table->json('team_members')->nullable(); // user IDs
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->string('certificate_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'cycle_year']);
        });

        Schema::create('verification_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('verification_cycles')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->boolean('is_present')->nullable();
            $table->boolean('location_correct')->nullable();
            $table->boolean('custodian_correct')->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->string('actual_location')->nullable();
            $table->string('actual_custodian')->nullable();
            $table->text('discrepancy_details')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->nullable();
            $table->enum('resolution_status', ['reported', 'investigating', 'resolved'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('photos')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'asset_id']);
            $table->index(['cycle_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_items');
        Schema::dropIfExists('verification_cycles');
    }
};