<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pearl_points_balances', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->integer('balance')->default(0);
            $table->integer('lifetime_earned')->default(0);
            $table->integer('lifetime_spent')->default(0);
            $table->string('tier', 20)->default('standard');
            $table->timestampTz('updated_at')->useCurrent();

            $table->check('balance >= 0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pearl_points_balances');
    }
};
