<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Policies;

use App\Domain\Conversation\Models\Conversation;
use App\Domain\Identity\Models\User;

final class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->tenant_id === $conversation->tenant_id;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->tenant_id === $conversation->tenant_id;
    }
}
