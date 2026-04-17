<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_payout_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('payout_fee', 10, 2)->default(50);
            $table->decimal('net_amount', 12, 2);
            $table->string('bank_name', 100);
            $table->string('account_name', 120);
            $table->string('account_no', 30);
            $table->string('branch', 100)->nullable();
            $table->string('status', 20)->default('pending')
                ->comment('pending|processing|paid|rejected');
            $table->text('admin_note')->nullable();
            $table->foreignUuid('processed_by')->nullable()->constrained('users');
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_payout_requests');
    }
};
