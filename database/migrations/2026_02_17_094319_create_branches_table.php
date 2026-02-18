<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // head_id → users (users table already exists) ✓
            $table->foreignId('head_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Bangladesh');

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('fax')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->date('established_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_main_branch')->default(false);

            $table->unsignedInteger('student_capacity')->nullable();
            $table->unsignedInteger('staff_count')->nullable();
            $table->decimal('annual_budget', 15, 2)->default(0);
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};