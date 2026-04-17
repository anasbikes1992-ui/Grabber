<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('full_name', 255)->nullable();
            $table->text('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->string('nic_number', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('country', 100)->default('LK');
            $table->string('preferred_lang', 5)->default('en');
            $table->string('preferred_currency', 3)->default('LKR');
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 255)->nullable();
            $table->string('bank_branch_code', 20)->nullable();
            $table->string('mobile_money_number', 20)->nullable();
            $table->string('provider_tier', 20)->default('standard');
            $table->boolean('is_online')->default(false);
            $table->double('last_lat')->nullable();
            $table->double('last_lng')->nullable();
            $table->timestampTz('last_seen_at')->nullable();
            $table->string('account_status', 20)->default('active');
            $table->string('social_tier', 20)->default('standard');
            $table->string('referral_code', 20)->nullable()->unique();
            $table->boolean('accepts_cash')->default(false);
            $table->boolean('cash_security_deposit_paid')->default(false);
            $table->timestampsTz();

            $table->check("provider_tier in ('standard','verified','pro','elite')");
            $table->check("account_status in ('active','suspended','banned')");
            $table->check("social_tier in ('standard','premium')");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
