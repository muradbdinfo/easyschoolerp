<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_depreciation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->year('year');
            $table->tinyInteger('month'); // 1-12
            $table->decimal('opening_value', 12, 2);
            $table->decimal('depreciation_amount', 12, 2);
            $table->decimal('closing_value', 12, 2);
            $table->date('processed_date');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_id', 'year', 'month']);
            $table->index('asset_id');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_schedules');
    }
};