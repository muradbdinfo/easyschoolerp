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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Auto-generated: VEN-00001');
            $table->string('name');
            $table->enum('type', ['supplier', 'contractor', 'service_provider'])->default('supplier');
            
            // Contact Information
            $table->string('contact_person')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            
            // Business Details
            $table->string('tax_id', 50)->nullable()->comment('TIN/VAT number');
            $table->string('business_registration', 100)->nullable();
            $table->text('bank_details')->nullable();
            
            // Rating and Status
            $table->decimal('rating', 2, 1)->default(0)->comment('0.0 to 5.0');
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('blacklist_reason')->nullable();
            $table->date('blacklisted_at')->nullable();
            
            // Payment Terms
            $table->integer('payment_terms_days')->default(30)->comment('Payment due in days');
            $table->decimal('credit_limit', 12, 2)->default(0)->comment('Maximum credit allowed');
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->json('documents')->nullable()->comment('Uploaded documents array');
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('name');
            $table->index('type');
            $table->index('status');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};