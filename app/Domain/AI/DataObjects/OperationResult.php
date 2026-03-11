<?php

declare(strict_types=1);

namespace App\Domain\AI\DataObjects;

use App\Domain\AI\Enums\RouterTool;
use App\Domain\Conversation\Enums\ConversationMode;

final readonly class OperationResult
{
    public function __construct(
        public ConversationMode $mode,
        public string $responseText,
        public ?RouterTool $routedOperation = null,
    ) {}
}
