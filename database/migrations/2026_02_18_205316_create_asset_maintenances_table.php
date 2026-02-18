<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('maintenance_number', 20)->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->enum('type', ['routine', 'repair', 'servicing', 'calibration', 'upgrade'])->default('routine');
            $table->enum('frequency', ['one_time', 'monthly', 'quarterly', 'yearly'])->default('one_time');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('description')->nullable();
            $table->text('work_performed')->nullable();
            $table->text('parts_replaced')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('condition_after', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->date('next_due_date')->nullable();
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_id');
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};