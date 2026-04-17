<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 255)->nullable()->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('password', 255);
            $table->string('role', 40)->default('customer');
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('phone_verified_at')->nullable();
            $table->string('two_factor_secret', 255)->nullable();
            $table->timestampTz('two_factor_confirmed_at')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();

            $table->check("role in ('customer','provider_stays','provider_vehicles','provider_events','provider_experiences','provider_properties','property_broker','provider_sme','driver','cash_agent','admin','super_admin')");
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
