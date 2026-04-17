<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_surge_zones', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 100);
            $table->string('city', 100)->default('Colombo');
            $table->decimal('center_lat', 10, 7);
            $table->decimal('center_lng', 10, 7);
            $table->decimal('radius_km', 5, 2)->default(10.00);
            $table->decimal('base_surge', 4, 2)->default(1.00);
            $table->decimal('max_surge', 4, 2)->default(3.00);
            $table->boolean('auto_surge_enabled')->default(true);
            $table->decimal('manual_override_multiplier', 4, 2)->nullable();
            $table->timestamp('manual_override_until')->nullable();
            $table->jsonb('peak_hour_bonuses')->nullable()
                ->comment('{"07:00-09:00": 0.2, "17:00-20:00": 0.3}');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('taxi_driver_scores', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('driver_id')->constrained('users')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('completed_rides')->default(0);
            $table->integer('accepted_rides')->default(0);
            $table->integer('received_requests')->default(0);
            $table->decimal('avg_rating', 4, 2)->default(0);
            $table->decimal('avg_response_seconds', 8, 2)->default(0);
            $table->integer('total_online_minutes')->default(0);
            $table->decimal('acceptance_rate', 5, 2)->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('rating_score', 5, 2)->default(0);
            $table->decimal('acceptance_score', 5, 2)->default(0);
            $table->decimal('completion_score', 5, 2)->default(0);
            $table->decimal('response_score', 5, 2)->default(0);
            $table->decimal('hours_score', 5, 2)->default(0);
            $table->decimal('total_score', 5, 2)->default(0);
            $table->string('tier', 20)->default('bronze')
                ->comment('bronze|silver|gold|diamond');
            $table->decimal('total_km', 10, 2)->default(0);
            $table->decimal('bonus_earned', 10, 2)->default(0);
            $table->boolean('bonus_credited')->default(false);
            $table->timestampsTz();

            $table->unique(['driver_id', 'period_start']);
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_scores');
        Schema::dropIfExists('taxi_surge_zones');
    }
};
