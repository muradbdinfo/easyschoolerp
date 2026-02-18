<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 20)->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();

            // From
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('from_location')->nullable();
            $table->foreignId('from_custodian_id')->nullable()->constrained('users')->nullOnDelete();

            // To
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('to_location')->nullable();
            $table->foreignId('to_custodian_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('transfer_date');
            $table->text('reason')->nullable();
            $table->enum('condition_before', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->enum('condition_after', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_id');
            $table->index('status');
            $table->index('transfer_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
    }
};