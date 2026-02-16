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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Auto-generated: ITM-00001');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Category and Classification
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->enum('type', ['consumable', 'asset', 'both'])->default('consumable');
            
            // Unit of Measurement
            $table->string('unit', 20)->comment('pcs, kg, liter, box, etc');
            $table->string('unit_secondary', 20)->nullable()->comment('For dual unit items');
            $table->decimal('conversion_factor', 10, 4)->nullable()->comment('Secondary to primary conversion');
            
            // Pricing
            $table->decimal('current_price', 12, 2)->default(0);
            $table->decimal('last_purchase_price', 12, 2)->nullable();
            $table->date('last_purchase_date')->nullable();
            $table->decimal('avg_price', 12, 2)->nullable()->comment('Average purchase price');
            
            // Stock Management
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('min_stock_level', 12, 2)->default(0);
            $table->decimal('max_stock_level', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->integer('lead_time_days')->default(7)->comment('Days to deliver after order');
            
            // Asset Specific (if type is asset or both)
            $table->boolean('is_consumable')->default(true);
            $table->boolean('is_asset')->default(false);
            $table->decimal('asset_threshold_amount', 12, 2)->nullable()->comment('If purchase > this, create asset');
            
            // Product Details
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('specifications')->nullable();
            $table->string('photo')->nullable();
            $table->json('additional_photos')->nullable();
            
            // Barcode/SKU
            $table->string('barcode')->nullable()->unique();
            $table->string('sku')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->json('attributes')->nullable()->comment('Custom attributes JSON');
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('name');
            $table->index('category_id');
            $table->index('type');
            $table->index('status');
            $table->index('barcode');
            $table->fullText(['name', 'description']); // For search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};