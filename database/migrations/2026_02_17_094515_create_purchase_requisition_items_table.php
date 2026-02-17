<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            
            // Parent PR
            $table->foreignId('purchase_requisition_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            // Item Reference
            $table->foreignId('item_id')->constrained();
            
            // Item Details (snapshot at time of requisition)
            $table->string('item_code');
            $table->string('item_name');
            $table->string('item_description')->nullable();
            $table->string('unit');
            
            // Quantity
            $table->decimal('quantity', 10, 2);
            $table->decimal('quantity_approved', 10, 2)->nullable(); // May be less than requested
            
            // Pricing
            $table->decimal('estimated_unit_price', 15, 2)->default(0);
            $table->decimal('estimated_total', 15, 2)->default(0); // qty × unit_price
            
            // Actual prices (filled when PO created)
            $table->decimal('actual_unit_price', 15, 2)->nullable();
            $table->decimal('actual_total', 15, 2)->nullable();
            
            // Additional Info
            $table->text('specifications')->nullable(); // Specific requirements
            $table->text('notes')->nullable();
            
            // Budget tracking (if budgets module enabled)
            // NOTE: No ->constrained() — budget_lines table created in a later module (Week 8+)
            $table->unsignedBigInteger('budget_line_id')->nullable();
            
            // PO Item reference (when PO created)
            // NOTE: No ->constrained() — purchase_order_items table created in Week 7
            $table->unsignedBigInteger('purchase_order_item_id')->nullable();
            
            // Line item status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'ordered', // PO created
                'received', // GRN done
                'cancelled'
            ])->default('pending');
            
            // Sorting
            $table->unsignedInteger('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('purchase_requisition_id');
            $table->index('item_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_items');
    }
};