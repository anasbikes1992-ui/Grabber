<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_listings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('color')->nullable();
            $table->string('license_plate')->unique();
            $table->string('vehicle_type')
                ->check("vehicle_type IN ('car','van','suv','bus','truck','motorbike','tuk_tuk')");
            $table->string('transmission')->default('automatic')
                ->check("transmission IN ('automatic','manual')");
            $table->unsignedTinyInteger('seats');
            $table->decimal('price_per_day', 12, 2);
            $table->string('currency', 3)->default('LKR');
            $table->string('pickup_city');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->jsonb('features')->nullable(); // AC, GPS, child_seat, etc.
            $table->jsonb('images')->nullable();
            $table->string('status')->default('draft')
                ->check("status IN ('draft','pending_review','active','paused','rejected')");
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('driver_available')->default(false);
            $table->decimal('driver_extra_per_day', 12, 2)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_listings');
    }
};
