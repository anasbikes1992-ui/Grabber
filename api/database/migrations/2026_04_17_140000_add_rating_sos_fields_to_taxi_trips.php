<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxi_trips', function (Blueprint $table) {
            $table->smallInteger('driver_rating')->nullable()->after('cancel_reason');
            $table->smallInteger('customer_rating')->nullable()->after('driver_rating');
            $table->decimal('tip_amount', 8, 2)->default(0)->after('customer_rating');
            $table->string('tip_payment_ref')->nullable()->after('tip_amount');
            $table->timestampTz('sos_triggered_at')->nullable()->after('tip_payment_ref');
            $table->uuid('corporate_account_id')->nullable()->after('sos_triggered_at');
            $table->boolean('is_scheduled')->default(false)->after('corporate_account_id');
            $table->timestampTz('scheduled_at')->nullable()->after('is_scheduled');
            $table->boolean('split_fare')->default(false)->after('scheduled_at');
            $table->jsonb('split_with')->nullable()->after('split_fare');
        });
    }

    public function down(): void
    {
        Schema::table('taxi_trips', function (Blueprint $table) {
            $table->dropColumn([
                'driver_rating', 'customer_rating', 'tip_amount', 'tip_payment_ref',
                'sos_triggered_at', 'corporate_account_id', 'is_scheduled',
                'scheduled_at', 'split_fare', 'split_with',
            ]);
        });
    }
};
