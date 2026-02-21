<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Webhook;

use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Channel\Enums\ChannelStatus;
use App\Domain\Channel\Models\Channel;
use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private Channel $channel;

    private string $webhookSecret = 'test-webhook-secret-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = Channel::factory()->telegram()->create([
            'status' => ChannelStatus::Active,
            'webhook_secret' => $this->webhookSecret,
            'bot_token_vault_path' => 'tenants/test/channels/test/bot_token',
        ]);
    }

    private function makeTextUpdate(string $text = 'Hello'): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 1, 'is_bot' => false, 'first_name' => 'John'],
                'chat' => ['id' => 123, 'type' => 'private'],
                'date' => 1000,
                'text' => $text,
            ],
        ];
    }

    public function test_valid_text_message_returns_200(): void
    {
        $vault = Mockery::mock(VaultContract::class);
        $vault->shouldReceive('get')->andReturn('fake-bot-token');
        $this->app->instance(VaultContract::class, $vault);

        $telegram = Mockery::mock(TelegramContract::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->with('fake-bot-token', '123', 'Echo: Hello');
        $this->app->instance(TelegramContract::class, $telegram);

        $response = $this->postJson(
            "/api/webhook/telegram/{$this->channel->id}",
            $this->makeTextUpdate(),
            ['X-Telegram-Bot-Api-Secret-Token' => $this->webhookSecret]
        );

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    public function test_missing_secret_header_returns_401(): void
    {
        $response = $this->postJson(
            "/api/webhook/telegram/{$this->channel->id}",
            $this->makeTextUpdate(),
        );

        $response->assertStatus(401);
    }

    public function test_wrong_secret_header_returns_401(): void
    {
        $response = $this->postJson(
            "/api/webhook/telegram/{$this->channel->id}",
            $this->makeTextUpdate(),
            ['X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret']
        );

        $response->assertStatus(401);
    }

    public function test_inactive_channel_returns_404(): void
    {
        $this->channel->update(['status' => ChannelStatus::Inactive]);

        $response = $this->postJson(
            "/api/webhook/telegram/{$this->channel->id}",
            $this->makeTextUpdate(),
            ['X-Telegram-Bot-Api-Secret-Token' => $this->webhookSecret]
        );

        $response->assertNotFound();
    }

    public function test_nonexistent_channel_returns_404(): void
    {
        $response = $this->postJson(
            '/api/webhook/telegram/00000000-0000-0000-0000-000000000000',
            $this->makeTextUpdate(),
            ['X-Telegram-Bot-Api-Secret-Token' => $this->webhookSecret]
        );

        $response->assertNotFound();
    }

    public function test_unsupported_update_returns_200(): void
    {
        $response = $this->postJson(
            "/api/webhook/telegram/{$this->channel->id}",
            ['update_id' => 1],
            ['X-Telegram-Bot-Api-Secret-Token' => $this->webhookSecret]
        );

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    public function test_callback_query_returns_200(): void
    {
        $vault = Mockery::mock(VaultContract::class);
        $vault->shouldNotReceive('get');
        $this->app->instance(VaultContract::class, $vault);

        $response = $this->postJson(
            "/api/webhook/telegram/{$this->channel->id}",
            [
                'update_id' => 1,
                'callback_query' => [
                    'id' => 'cbq1',
                    'from' => ['id' => 1, 'is_bot' => false, 'first_name' => 'John'],
                    'message' => [
                        'message_id' => 9,
                        'chat' => ['id' => 123, 'type' => 'private'],
                        'date' => 1000,
                    ],
                    'data' => 'action:confirm',
                ],
            ],
            ['X-Telegram-Bot-Api-Secret-Token' => $this->webhookSecret]
        );

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }
}
