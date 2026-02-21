<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('channel_id')->constrained()->cascadeOnDelete();
            $table->string('external_chat_id');
            $table->string('client_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('mode')->default('ai');
            $table->json('scenario_state')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'external_chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
