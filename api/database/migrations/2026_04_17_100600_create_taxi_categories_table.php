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
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 100);
            $table->text('icon_url')->nullable();
            $table->decimal('base_fare', 8, 2);
            $table->decimal('per_km_rate', 6, 2);
            $table->decimal('per_min_rate', 6, 2);
            $table->decimal('min_fare', 8, 2);
            $table->unsignedInteger('max_capacity')->default(4);
            $table->boolean('surge_enabled')->default(true);
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_categories');
    }
};
