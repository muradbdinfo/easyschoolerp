<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->enum('depreciation_method', ['slm', 'wdv', 'none'])->default('slm')
                  ->comment('SLM=Straight Line, WDV=Written Down Value');
            $table->decimal('depreciation_rate', 5, 2)->default(0)->comment('Annual % rate');
            $table->integer('useful_life_years')->default(5);
            $table->decimal('residual_value_percent', 5, 2)->default(10)
                  ->comment('Salvage value % of cost');
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('parent_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};