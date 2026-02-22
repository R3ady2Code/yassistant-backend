<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}', fn (User $user, string $tenantId) => $user->tenant_id === $tenantId);
