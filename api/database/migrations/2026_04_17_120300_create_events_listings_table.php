<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_listings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('organiser_id')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('venue_name')->nullable();
            $table->string('city')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('event_type', 20)->default('in_person')
                ->comment('in_person|virtual|hybrid');
            $table->string('stream_url')->nullable();
            $table->boolean('qr_scanner_enabled')->default(false);
            $table->integer('total_tickets')->nullable();
            $table->integer('sold_tickets')->default(0);
            $table->jsonb('images')->nullable();
            $table->string('status', 20)->default('draft')
                ->comment('draft|published|cancelled|completed');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern', 20)->nullable(); // weekly|monthly
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('organiser_id');
            $table->index('starts_at');
        });

        Schema::create('event_ticket_types', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('event_id')->constrained('events_listings')->cascadeOnDelete();
            $table->string('name'); // General, VIP, Early Bird...
            $table->string('type', 30)->default('general')
                ->comment('general|reserved|vip|early_bird|group|free|donation');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('quantity')->nullable();
            $table->integer('sold')->default(0);
            $table->timestampTz('sale_starts_at')->nullable();
            $table->timestampTz('sale_ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_ticket_types');
        Schema::dropIfExists('events_listings');
    }
};
