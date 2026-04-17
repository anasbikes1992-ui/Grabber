<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('provider_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 14, 2)->default(0.00);
            $table->decimal('on_hold', 14, 2)->default(0.00);
            $table->decimal('lifetime_earnings', 14, 2)->default(0.00);
            $table->decimal('lifetime_payouts', 14, 2)->default(0.00);
            $table->decimal('cash_commission_outstanding', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('LKR');
            $table->boolean('is_frozen')->default(false);
            $table->unsignedInteger('payout_hold_days')->default(3);
            $table->timestampTz('updated_at')->useCurrent();

            $table->check('balance >= 0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_wallets');
    }
};
