<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->nullable();
            $table->uuid('property_sale_id')->nullable();
            $table->uuid('subscription_id')->nullable();
            $table->uuid('flash_deal_id')->nullable();
            $table->foreignUuid('payer_id')->constrained('users');
            $table->string('payment_method', 20);
            $table->string('gateway', 20)->default('webxpay');
            $table->string('gateway_ref', 255)->nullable()->unique();
            $table->jsonb('gateway_payload')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('handling_fee', 10, 2)->default(0);
            $table->decimal('handling_fee_rate', 5, 4)->default(0);
            $table->string('currency', 3)->default('LKR');
            $table->string('type', 30)->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->string('bank_transfer_ref', 100)->nullable();
            $table->timestampTz('bank_transfer_deadline')->nullable();
            $table->foreignUuid('cash_agent_id')->nullable()->constrained('users');
            $table->string('cash_receipt_number', 50)->nullable();
            $table->timestampTz('cash_deadline')->nullable();
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['status', 'bank_transfer_deadline']);
        });

        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_chk CHECK (payment_method IN ('card','bank_transfer','cash_agent','cash_provider'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_type_chk CHECK (type IN ('booking','deposit','property_commission','subscription','flash_deal_listing','cash_security_deposit'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_chk CHECK (status IN ('pending','awaiting_bank_transfer','awaiting_cash','completed','failed','refunded','partially_refunded'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
