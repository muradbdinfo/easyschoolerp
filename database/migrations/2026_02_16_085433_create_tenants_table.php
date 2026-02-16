<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // School name
            $table->string('subdomain')->unique();
            $table->string('database_name');
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled'])->default('trial');
            $table->string('plan')->default('basic'); // basic, professional, enterprise
            $table->json('active_modules')->nullable(); // ['procurement', 'assets']
            $table->decimal('mrr', 10, 2)->default(0); // Monthly Recurring Revenue
            $table->date('trial_ends_at')->nullable();
            $table->date('subscription_ends_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            
            // Contact Information
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            
            // Settings
            $table->string('logo')->nullable();
            $table->string('primary_color')->default('#3b82f6');
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};