<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // booking_confirmed, payment_received, etc.
            $table->string('channel')->default('push')
                ->check("channel IN ('push','email','sms','in_app')");
            $table->string('title');
            $table->text('body');
            $table->jsonb('data')->nullable(); // deep-link payload
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
