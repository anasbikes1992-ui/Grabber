<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('booking_id')->constrained('bookings');
            $table->foreignUuid('reviewer_id')->constrained('users');
            $table->foreignUuid('reviewee_id')->constrained('users');
            $table->uuidMorphs('reviewable'); // reviewable_type + reviewable_id (listing)
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->jsonb('category_ratings')->nullable(); // cleanliness, accuracy, value, etc.
            $table->boolean('is_public')->default(true);
            $table->string('direction')
                ->check("direction IN ('customer_to_provider','provider_to_customer')");
            $table->timestampsTz();

            $table->unique(['booking_id', 'reviewer_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
