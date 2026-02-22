<?php

declare(strict_types=1);

namespace App\Http\Conversations\Middleware;

use App\Domain\Conversation\Models\Conversation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeConversationOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Conversation|null $conversation */
        $conversation = $request->route('conversation');

        if (! $conversation instanceof Conversation) {
            abort(404);
        }

        abort_unless($conversation->tenant_id === $request->user()->tenant_id, 403);

        return $next($request);
    }
}
