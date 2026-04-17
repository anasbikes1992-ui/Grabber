<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_config', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('category', 50);
            $table->string('key', 100);
            $table->text('value');
            $table->string('type', 20)->default('string');
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->foreignUuid('updated_by')->nullable()->constrained('users');
            $table->timestampTz('updated_at')->useCurrent();
            $table->unique(['category', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_config');
    }
};
