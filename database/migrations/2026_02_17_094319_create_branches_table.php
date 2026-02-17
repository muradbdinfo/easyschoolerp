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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            
            $table->string('code')->unique(); // BR-JR, BR-SR
            $table->string('name'); // Junior Branch, Senior Branch
            $table->text('description')->nullable();
            
            // Branch Head
            $table->foreignId('head_id')->nullable()->constrained('users');
            
            // Location
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Bangladesh');
            
            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('fax')->nullable();
            
            // GPS Coordinates (for future mapping)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Operational
            $table->date('established_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_main_branch')->default(false);
            
            // Capacity
            $table->unsignedInteger('student_capacity')->nullable();
            $table->unsignedInteger('staff_count')->nullable();
            
            // Financial
            $table->decimal('annual_budget', 15, 2)->default(0);
            
            // Settings
            $table->json('settings')->nullable(); // Branch-specific settings
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};