<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // PO Identification
            $table->string('po_number')->unique(); // PO-2026-0001
            $table->date('po_date');

            // Source
            $table->foreignId('purchase_requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();

            // Delivery
            $table->date('expected_delivery_date')->nullable();
            $table->text('delivery_address')->nullable();

            // Financial
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('freight_charges', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            // Payment
            $table->string('payment_terms')->nullable(); // Net 30, COD, etc.
            $table->integer('payment_terms_days')->default(30);

            // Status
            $table->enum('status', [
                'draft',
                'sent',          // Sent to vendor
                'acknowledged',  // Vendor confirmed
                'partial',       // Partially received
                'received',      // Fully received
                'closed',
                'cancelled',
            ])->default('draft');

            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();

            // Metadata
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('purchase_requisition_item_id')->nullable()->constrained()->nullOnDelete();

            $table->string('item_name'); // snapshot
            $table->string('unit');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2)->storedAs('quantity * unit_price');
            $table->decimal('received_quantity', 10, 2)->default(0);
            $table->text('specifications')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};