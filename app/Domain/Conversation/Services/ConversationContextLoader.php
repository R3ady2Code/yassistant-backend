<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Services;

use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Models\Conversation;

final class ConversationContextLoader
{
    private const int MAX_TOKENS = 6000;

    private const int CHARS_PER_TOKEN = 4;

    public function __construct(
        private readonly MessageTextPreparer $textPreparer,
    ) {}

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function load(Conversation $conversation, int $limit = 20): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Domain\Conversation\Models\Message> $messages */
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $result = [];
        $totalChars = 0;
        $maxChars = self::MAX_TOKENS * self::CHARS_PER_TOKEN;

        foreach ($messages as $message) {
            $content = $this->textPreparer->prepare($message);

            if ($content === '') {
                continue;
            }

            $role = $message->direction === MessageDirection::Incoming ? 'user' : 'assistant';

            $totalChars += mb_strlen($content);
            $result[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        if ($totalChars > $maxChars) {
            return $this->trimOldest($result, $maxChars);
        }

        return $result;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array{role: string, content: string}>
     */
    private function trimOldest(array $messages, int $maxChars): array
    {
        $totalChars = 0;
        foreach ($messages as $msg) {
            $totalChars += mb_strlen($msg['content']);
        }

        while ($totalChars > $maxChars && count($messages) > 1) {
            $removed = array_shift($messages);
            $totalChars -= mb_strlen($removed['content']);
        }

        return array_values($messages);
    }
}
