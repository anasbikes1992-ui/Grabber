<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiences_listings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('provider_id')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 50); // day_tour|adventure|cultural|wellness|wildlife etc.
            $table->string('city');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('price_per_person', 10, 2);
            $table->decimal('price_per_group', 10, 2)->nullable();
            $table->decimal('child_price', 10, 2)->nullable();
            $table->unsignedTinyInteger('min_group')->default(1);
            $table->unsignedTinyInteger('max_group')->default(20);
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->boolean('weather_dependent')->default(false);
            $table->boolean('wheelchair_accessible')->default(false);
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->jsonb('availability_months')->nullable();
            $table->jsonb('images')->nullable();
            $table->string('status', 20)->default('pending_approval')
                ->comment('pending_approval|active|inactive|suspended');
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('provider_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences_listings');
    }
};
