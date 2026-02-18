<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('asset_tag', 30)->unique()->comment('AS-BRN-CAT-00001');
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();

            // Acquisition
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 12, 2)->default(0);
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->string('po_number')->nullable();
            $table->string('grn_number')->nullable();

            // Product Details
            $table->string('brand')->nullable();
            $table->string('model_number')->nullable();
            $table->string('serial_number')->nullable()->unique()->nullable();
            $table->string('color')->nullable();
            $table->text('specifications')->nullable();
            $table->text('description')->nullable();

            // Location
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->string('room')->nullable();
            $table->string('location_details')->nullable();

            // Custodian
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('custodian_assigned_date')->nullable();

            // Depreciation
            $table->enum('depreciation_method', ['slm', 'wdv', 'none'])->default('slm');
            $table->decimal('depreciation_rate', 5, 2)->default(0);
            $table->integer('useful_life_years')->default(5);
            $table->decimal('residual_value_percent', 5, 2)->default(10);
            $table->date('depreciation_start_date')->nullable();
            $table->decimal('accumulated_depreciation', 12, 2)->default(0);
            $table->decimal('net_book_value', 12, 2)->default(0);

            // Warranty & Insurance
            $table->integer('warranty_months')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->string('warranty_provider')->nullable();
            $table->string('insurance_company')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->decimal('insured_value', 12, 2)->nullable();
            $table->date('insurance_expiry_date')->nullable();

            // Status
            $table->enum('status', ['active', 'under_maintenance', 'disposed', 'lost', 'damaged', 'written_off'])
                  ->default('active');
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor'])->default('good');

            // Photos & Documents
            $table->string('primary_photo')->nullable();
            $table->json('photos')->nullable();
            $table->json('documents')->nullable();

            // QR Code
            $table->string('qr_code_path')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_tag');
            $table->index('category_id');
            $table->index('branch_id');
            $table->index('custodian_id');
            $table->index('status');
            $table->index('acquisition_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};