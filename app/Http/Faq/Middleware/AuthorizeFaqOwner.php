<?php

declare(strict_types=1);

namespace App\Http\Faq\Middleware;

use App\Domain\AI\Models\FaqEntry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeFaqOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var FaqEntry|null $faqEntry */
        $faqEntry = $request->route('faqEntry');

        if (! $faqEntry instanceof FaqEntry) {
            abort(404);
        }

        abort_unless($faqEntry->tenant_id === $request->user()->tenant_id, 403);

        return $next($request);
    }
}
