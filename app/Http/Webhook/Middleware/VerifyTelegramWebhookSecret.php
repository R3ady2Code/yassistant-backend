<?php

declare(strict_types=1);

namespace App\Http\Webhook\Middleware;

use App\Domain\Channel\Enums\ChannelStatus;
use App\Domain\Channel\Models\Channel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Channel|null $channel */
        $channel = $request->route('channel');

        if (! $channel instanceof Channel || $channel->status !== ChannelStatus::Active) {
            abort(404);
        }

        $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        $channelSecret = $channel->webhook_secret ?? '';

        if ($channelSecret === '' || ! hash_equals($channelSecret, $headerSecret)) {
            abort(401, 'Invalid webhook secret');
        }

        return $next($request);
    }
}
