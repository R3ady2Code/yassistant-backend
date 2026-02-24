<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('scenarios');

        Schema::create('booking_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('yclients_service_id');
            $table->string('yclients_service_name');
            $table->integer('yclients_branch_id');
            $table->boolean('ask_staff')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'yclients_service_id']);
        });

        Schema::create('booking_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('booking_flows')->cascadeOnDelete();
            $table->string('question_text', 500);
            $table->string('answer_type', 20);
            $table->boolean('is_required')->default(true);
            $table->json('config')->default('{}');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['flow_id', 'sort_order']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('booking_flow_id')
                ->nullable()
                ->after('client_id')
                ->constrained('booking_flows')
                ->nullOnDelete();

            $table->renameColumn('scenario_state', 'pipeline_state');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->renameColumn('pipeline_state', 'scenario_state');
            $table->dropConstrainedForeignId('booking_flow_id');
        });

        Schema::dropIfExists('booking_flow_steps');
        Schema::dropIfExists('booking_flows');
    }
};
