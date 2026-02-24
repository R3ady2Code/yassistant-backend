<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array OPERATIONS = ['create_booking', 'cancel_booking', 'edit_booking'];

    public function up(): void
    {
        Schema::create('bot_setting_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('bot_setting_id')->constrained('bot_settings')->cascadeOnDelete();
            $table->string('operation');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['bot_setting_id', 'operation']);
        });

        $botSettings = DB::table('bot_settings')->get(['id', 'allowed_operations']);

        foreach ($botSettings as $row) {
            $enabledOps = json_decode($row->allowed_operations ?? '[]', true) ?: [];

            foreach (self::OPERATIONS as $operation) {
                DB::table('bot_setting_operations')->insert([
                    'bot_setting_id' => $row->id,
                    'operation' => $operation,
                    'is_enabled' => in_array($operation, $enabledOps, true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn('allowed_operations');
        });
    }

    public function down(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->jsonb('allowed_operations')->default('[]');
        });

        $operations = DB::table('bot_setting_operations')
            ->where('is_enabled', true)
            ->get()
            ->groupBy('bot_setting_id');

        foreach ($operations as $botSettingId => $ops) {
            DB::table('bot_settings')
                ->where('id', $botSettingId)
                ->update([
                    'allowed_operations' => json_encode($ops->pluck('operation')->all()),
                ]);
        }

        Schema::dropIfExists('bot_setting_operations');
    }
};
