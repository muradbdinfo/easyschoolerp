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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            
            $table->string('code')->unique(); // DEPT-001
            $table->string('name'); // English, Science, Admin, Finance
            $table->text('description')->nullable();
            
            // Department Head
            $table->foreignId('head_id')->nullable()->constrained('users');
            
            // Budget allocation (if budgets module enabled)
            $table->decimal('annual_budget', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            
            // Approval threshold (PRs above this need higher approval)
            $table->decimal('approval_threshold', 15, 2)->default(50000); // BDT
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('location')->nullable();
            
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
        Schema::dropIfExists('departments');
    }
};