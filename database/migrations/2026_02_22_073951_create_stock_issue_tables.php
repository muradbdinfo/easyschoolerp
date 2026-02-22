<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_issue_requests', function (Blueprint $table) {
            $table->id();
            $table->string('sir_number')->unique();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->date('request_date');
            $table->date('required_by_date')->nullable();
            $table->date('issued_date')->nullable();
            $table->enum('status', ['draft','submitted','partially_issued','issued','cancelled'])->default('draft');
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_issue_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->decimal('quantity_requested', 12, 2);
            $table->decimal('quantity_issued', 12, 2)->default(0);
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained();
            $table->enum('type', ['grn_receipt','issue','return','adjustment_add','adjustment_sub','disposal','opening']);
            $table->enum('direction', ['in','out']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('stock_issue_items');
        Schema::dropIfExists('stock_issue_requests');
    }
};