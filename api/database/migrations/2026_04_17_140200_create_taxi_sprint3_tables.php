<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_corporate_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('company_name', 255);
            $table->string('company_reg_no', 100)->nullable();
            $table->string('contact_name', 255);
            $table->string('contact_email', 255);
            $table->string('contact_phone', 30)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_cycle', 20)->default('monthly')
                ->comment('monthly|weekly');
            $table->decimal('credit_limit', 12, 2)->default(50000.00);
            $table->decimal('current_usage', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('taxi_corporate_employees', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('corporate_account_id')
                ->constrained('taxi_corporate_accounts')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('monthly_limit', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['corporate_account_id', 'user_id']);
        });

        Schema::create('taxi_cash_commission_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('driver_id')->constrained('users')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_cash_rides')->default(0);
            $table->decimal('total_cash_fares', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 4)->default(0.1500);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('unpaid')
                ->comment('unpaid|paid|overdue|waived');
            $table->string('payment_ref', 255)->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->boolean('suspension_triggered')->default(false);
            $table->timestampsTz();

            $table->unique(['driver_id', 'period_start']);
            $table->index(['driver_id', 'status']);
        });

        Schema::create('taxi_quests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('type', 20)->default('daily')
                ->comment('daily|weekly');
            $table->string('metric', 50)->default('completed_rides')
                ->comment('completed_rides|online_minutes|km_driven|rating');
            $table->decimal('target_value', 10, 2);
            $table->decimal('reward_amount', 10, 2);
            $table->string('reward_type', 20)->default('cash')
                ->comment('cash|bonus_multiplier');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('is_active')->default(true);
            $table->integer('max_completions')->nullable();
            $table->timestampsTz();
        });

        Schema::create('taxi_driver_quest_progress', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('quest_id')
                ->constrained('taxi_quests')->onDelete('cascade');
            $table->decimal('current_value', 10, 2)->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestampTz('completed_at')->nullable();
            $table->boolean('reward_credited')->default(false);
            $table->timestampsTz();

            $table->unique(['driver_id', 'quest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_quest_progress');
        Schema::dropIfExists('taxi_quests');
        Schema::dropIfExists('taxi_cash_commission_invoices');
        Schema::dropIfExists('taxi_corporate_employees');
        Schema::dropIfExists('taxi_corporate_accounts');
    }
};
