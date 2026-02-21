<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('channel_id')->constrained()->cascadeOnDelete();
            $table->string('external_user_id');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'external_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
