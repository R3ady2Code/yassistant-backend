<?php

declare(strict_types=1);

namespace App\Http\Channels\Middleware;

use App\Domain\Channel\Models\Channel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeChannelOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Channel|null $channel */
        $channel = $request->route('channel');

        if (! $channel instanceof Channel) {
            abort(404);
        }

        abort_unless($channel->tenant_id === $request->user()->tenant_id, 403);

        return $next($request);
    }
}
