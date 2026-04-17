<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('event_id')->constrained('events_listings')->cascadeOnDelete();
            $table->foreignUuid('ticket_type_id')->constrained('event_ticket_types')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('ticket_code', 32)->unique();
            $table->string('status', 20)->default('active')->comment('active|used|cancelled');
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->timestampTz('purchased_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();

            $table->index(['event_id', 'status']);
            $table->index(['customer_id', 'purchased_at']);
        });

        Schema::create('ticket_scans', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('ticket_id')->constrained('event_tickets')->cascadeOnDelete();
            $table->foreignUuid('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scan_type', 20)->default('entry')->comment('entry|exit|manual');
            $table->string('result', 20)->default('valid')->comment('valid|already_used|invalid');
            $table->timestampTz('scanned_at');
            $table->timestampsTz();

            $table->index(['ticket_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_scans');
        Schema::dropIfExists('event_tickets');
    }
};
