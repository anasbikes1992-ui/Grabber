<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('users');
            $table->foreignUuid('provider_id')->constrained('users');
            $table->string('booking_type');
            $table->uuidMorphs('bookable'); // bookable_type + bookable_id
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('LKR');
            $table->string('payment_status')->default('unpaid');
            $table->text('customer_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users');
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_booking_type_chk CHECK (booking_type IN ('stay','vehicle','taxi','experience','event'))");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_status_chk CHECK (status IN ('pending','confirmed','in_progress','completed','cancelled','disputed'))");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_payment_status_chk CHECK (payment_status IN ('unpaid','paid','partially_refunded','refunded'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
