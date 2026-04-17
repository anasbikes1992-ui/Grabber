<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->nullable();
            $table->text('icon')->nullable();
            $table->text('description')->nullable();
            $table->decimal('base_fare', 8, 2);
            $table->decimal('per_km_rate', 6, 2);
            $table->decimal('per_min_rate', 6, 2);
            $table->decimal('minimum_fare', 8, 2);
            $table->decimal('max_surge_multiplier', 4, 2)->default(2.5);
            $table->unsignedInteger('capacity')->default(4);
            $table->boolean('surge_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_categories');
    }
};
