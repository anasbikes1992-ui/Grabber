<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identifier');          // phone or email
            $table->string('identifier_type', 10); // 'phone' | 'email'
            $table->string('code', 6);
            $table->string('purpose', 30)->default('auth'); // auth | password_reset | phone_verify
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestampsTz();

            $table->index(['identifier', 'identifier_type', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
