<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique(); // GRN-2026-02-0001
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->date('receipt_date');
            $table->foreignId('received_by')->constrained('users');

            // Supplier documents
            $table->string('supplier_invoice_no')->nullable();
            $table->string('supplier_delivery_note')->nullable();
            $table->string('vehicle_number')->nullable();

            // Quality
            $table->enum('overall_status', ['passed', 'failed', 'partial'])->default('passed');
            $table->foreignId('quality_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('quality_remarks')->nullable();

            // Files
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained();

            $table->string('item_name');       // snapshot
            $table->string('unit');
            $table->decimal('quantity_ordered',   10, 2);
            $table->decimal('quantity_received',  10, 2)->default(0);
            $table->decimal('quantity_accepted',  10, 2)->default(0);
            $table->decimal('quantity_rejected',  10, 2)->default(0);
            $table->text('rejection_reason')->nullable();

            // Unit price snapshot for asset value check
            $table->decimal('unit_price', 15, 2)->default(0);

            // If accepted items auto-created as assets
            $table->boolean('assets_created')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_receipt_notes');
    }
};