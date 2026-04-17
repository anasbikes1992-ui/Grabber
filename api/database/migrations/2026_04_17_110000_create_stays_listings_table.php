<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stays_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('host_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('property_type'); // villa, apartment, hotel_room, etc.
            $table->string('address');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('city');
            $table->decimal('base_price_per_night', 12, 2);
            $table->string('currency', 3)->default('LKR');
            $table->unsignedTinyInteger('max_guests')->default(2);
            $table->unsignedTinyInteger('bedrooms')->default(1);
            $table->unsignedTinyInteger('bathrooms')->default(1);
            $table->jsonb('amenities')->nullable();
            $table->jsonb('images')->nullable();
            $table->string('status')->default('draft')
                ->check("status IN ('draft','pending_review','active','paused','rejected','archived')");
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('instant_book')->default(false);
            $table->unsignedSmallInteger('min_nights')->default(1);
            $table->unsignedSmallInteger('max_nights')->default(30);
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stays_listings');
    }
};
