<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // head_id → users (users table already exists) ✓
            $table->foreignId('head_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('annual_budget', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('approval_threshold', 15, 2)->default(50000);

            $table->boolean('is_active')->default(true);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('location')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};