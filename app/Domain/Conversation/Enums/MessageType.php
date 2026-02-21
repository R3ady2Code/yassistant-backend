<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Photo = 'photo';
    case Voice = 'voice';
    case Video = 'video';
    case Document = 'document';
    case Location = 'location';
    case Contact = 'contact';
    case Sticker = 'sticker';
    case CallbackQuery = 'callback_query';
}
