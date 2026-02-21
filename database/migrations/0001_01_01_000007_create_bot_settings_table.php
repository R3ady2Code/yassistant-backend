<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('system_prompt')->nullable();
            $table->string('ai_model')->default('gpt-4o');
            $table->jsonb('allowed_operations')->default('[]');
            $table->unsignedSmallInteger('max_function_calls')->default(5);
            $table->text('greeting_message')->nullable();
            $table->text('escalation_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
