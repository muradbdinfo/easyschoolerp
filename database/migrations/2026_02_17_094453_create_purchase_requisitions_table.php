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
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            
            // PR Identification
            $table->string('pr_number')->unique(); // PR-2024-0001
            $table->date('pr_date');
            
            // Requester Information
            $table->foreignId('user_id')->constrained()->comment('Requester');
            $table->foreignId('department_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            
            // Requirements
            $table->date('required_by_date');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Justification
            $table->text('purpose'); // Why these items are needed
            $table->text('justification')->nullable(); // Additional details
            
            // Financial
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('estimated_amount', 15, 2)->default(0);
            
            // Status & Workflow
            $table->enum('status', [
                'draft',
                'submitted',
                'pending_level_1', // Department Head
                'pending_level_2', // VP/Principal
                'pending_level_3', // Board (if very high value)
                'approved',
                'rejected',
                'cancelled',
                'closed' // After PO created
            ])->default('draft');
            
            // Approval Tracking
            $table->unsignedTinyInteger('current_approval_level')->default(0);
            $table->unsignedTinyInteger('required_approval_levels')->default(1);
            
            // Approval History (JSON for flexibility)
            $table->json('approval_history')->nullable(); // Stores all approvals/rejections
            
            // Level 1 Approval (Department Head)
            $table->foreignId('level_1_approver_id')->nullable()->constrained('users');
            $table->timestamp('level_1_approved_at')->nullable();
            $table->text('level_1_comments')->nullable();
            $table->enum('level_1_status', ['pending', 'approved', 'rejected'])->nullable();
            
            // Level 2 Approval (VP/Principal)
            $table->foreignId('level_2_approver_id')->nullable()->constrained('users');
            $table->timestamp('level_2_approved_at')->nullable();
            $table->text('level_2_comments')->nullable();
            $table->enum('level_2_status', ['pending', 'approved', 'rejected'])->nullable();
            
            // Level 3 Approval (Board - for very high amounts)
            $table->foreignId('level_3_approver_id')->nullable()->constrained('users');
            $table->timestamp('level_3_approved_at')->nullable();
            $table->text('level_3_comments')->nullable();
            $table->enum('level_3_status', ['pending', 'approved', 'rejected'])->nullable();
            
            // Final Approval
            $table->timestamp('final_approved_at')->nullable();
            $table->foreignId('final_approved_by')->nullable()->constrained('users');
            
            // Rejection
            $table->text('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            
            // PO Reference (when PO is created) - NO FOREIGN KEY YET
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            
            // Attachments
            $table->json('attachments')->nullable(); // Array of file paths
            
            // Metadata
            $table->boolean('is_urgent')->default(false);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('pr_number');
            $table->index('status');
            $table->index('user_id');
            $table->index('department_id');
            $table->index('branch_id');
            $table->index('pr_date');
            $table->index('purchase_order_id'); // Add index for the column
            $table->index(['status', 'current_approval_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};