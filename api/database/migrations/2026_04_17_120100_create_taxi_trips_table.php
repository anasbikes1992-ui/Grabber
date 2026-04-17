<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_trips', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('driver_id')->constrained('users');
            $table->foreignUuid('customer_id')->constrained('users');
            $table->foreignId('taxi_category_id')->constrained('taxi_categories');
            $table->string('status', 30)->default('searching')
                ->comment('scheduled|searching|accepted|driver_arrived|in_transit|completing|completed|cancelled|sos');
            $table->decimal('origin_lat', 10, 7);
            $table->decimal('origin_lng', 10, 7);
            $table->string('origin_address');
            $table->decimal('dest_lat', 10, 7)->nullable();
            $table->decimal('dest_lng', 10, 7)->nullable();
            $table->string('dest_address')->nullable();
            $table->jsonb('stops')->nullable(); // multi-stop array
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->unsignedSmallInteger('duration_min')->nullable();
            $table->decimal('estimated_fare', 10, 2)->nullable();
            $table->decimal('final_fare', 10, 2)->nullable();
            $table->decimal('surge_multiplier', 5, 2)->default(1.00);
            $table->string('payment_method', 20)->default('card')->comment('card|cash');
            $table->boolean('cash_paid')->default(false);
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->boolean('commission_invoiced')->default(false);
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('arrived_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestampsTz();

            $table->index('driver_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_trips');
    }
};
