<?php

declare(strict_types=1);

namespace App\Http\BookingFlows\Middleware;

use App\Domain\Booking\Models\BookingFlow;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeBookingFlowOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var BookingFlow|null $bookingFlow */
        $bookingFlow = $request->route('bookingFlow');

        if (! $bookingFlow instanceof BookingFlow) {
            abort(404);
        }

        abort_unless($bookingFlow->tenant_id === $request->user()->tenant_id, 403);

        return $next($request);
    }
}
