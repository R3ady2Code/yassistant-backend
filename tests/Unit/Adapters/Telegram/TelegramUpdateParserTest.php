<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Telegram;

use App\Adapters\Telegram\TelegramUpdateParser;
use App\Domain\Conversation\Enums\MessageType;
use PHPUnit\Framework\TestCase;

class TelegramUpdateParserTest extends TestCase
{
    private TelegramUpdateParser $parser;

    private string $channelId = '550e8400-e29b-41d4-a716-446655440000';

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TelegramUpdateParser();
    }

    private function makeUpdate(array $partial): array
    {
        return array_merge(['update_id' => 1], $partial);
    }

    private function baseFrom(): array
    {
        return ['id' => 1, 'is_bot' => false, 'first_name' => 'John', 'last_name' => 'Doe', 'username' => 'johndoe'];
    }

    private function baseChat(): array
    {
        return ['id' => 123, 'type' => 'private'];
    }

    public function test_parses_text_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 1,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'text' => 'Hello world',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame($this->channelId, $result->channelId);
        $this->assertSame('123', $result->externalChatId);
        $this->assertSame(MessageType::Text, $result->type);
        $this->assertSame('Hello world', $result->text);
        $this->assertNull($result->attachments);
        $this->assertSame('John Doe', $result->senderName);
    }

    public function test_parses_photo_with_caption_takes_last_element(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 2,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'caption' => 'Nice photo',
                'photo' => [
                    ['file_id' => 'small', 'file_unique_id' => 's1', 'width' => 100, 'height' => 100],
                    ['file_id' => 'medium', 'file_unique_id' => 's2', 'width' => 320, 'height' => 320],
                    ['file_id' => 'large', 'file_unique_id' => 's3', 'width' => 800, 'height' => 800],
                ],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Photo, $result->type);
        $this->assertSame('Nice photo', $result->text);
        $this->assertSame('large', $result->attachments['file_id']);
        $this->assertSame(800, $result->attachments['width']);
        $this->assertSame(800, $result->attachments['height']);
    }

    public function test_parses_voice_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 3,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'voice' => ['file_id' => 'voice1', 'file_unique_id' => 'v1', 'duration' => 5],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Voice, $result->type);
        $this->assertNull($result->text);
        $this->assertSame('voice1', $result->attachments['file_id']);
        $this->assertSame(5, $result->attachments['duration']);
    }

    public function test_parses_video_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 4,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'caption' => 'Watch this',
                'video' => ['file_id' => 'vid1', 'file_unique_id' => 'v1', 'width' => 1920, 'height' => 1080, 'duration' => 30],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Video, $result->type);
        $this->assertSame('Watch this', $result->text);
        $this->assertSame('vid1', $result->attachments['file_id']);
        $this->assertSame(30, $result->attachments['duration']);
    }

    public function test_parses_document_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 5,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'document' => ['file_id' => 'doc1', 'file_unique_id' => 'd1', 'file_name' => 'report.pdf', 'mime_type' => 'application/pdf'],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Document, $result->type);
        $this->assertNull($result->text);
        $this->assertSame('doc1', $result->attachments['file_id']);
        $this->assertSame('report.pdf', $result->attachments['file_name']);
        $this->assertSame('application/pdf', $result->attachments['mime_type']);
    }

    public function test_parses_location_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 6,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'location' => ['latitude' => 55.751244, 'longitude' => 37.618423],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Location, $result->type);
        $this->assertNull($result->text);
        $this->assertEqualsWithDelta(55.751244, $result->attachments['latitude'], 0.000001);
        $this->assertEqualsWithDelta(37.618423, $result->attachments['longitude'], 0.000001);
    }

    public function test_parses_contact_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 7,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'contact' => ['phone_number' => '+79001234567', 'first_name' => 'Jane', 'last_name' => 'Smith'],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Contact, $result->type);
        $this->assertNull($result->text);
        $this->assertSame('+79001234567', $result->attachments['phone_number']);
        $this->assertSame('Jane', $result->attachments['first_name']);
        $this->assertSame('Smith', $result->attachments['last_name']);
    }

    public function test_parses_sticker_message_with_emoji(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 8,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'sticker' => [
                    'file_id' => 'sticker1',
                    'file_unique_id' => 'st1',
                    'width' => 512,
                    'height' => 512,
                    'is_animated' => false,
                    'is_video' => false,
                    'type' => 'regular',
                    'emoji' => "\u{1F600}",
                ],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Sticker, $result->type);
        $this->assertSame("\u{1F600}", $result->text);
        $this->assertSame('sticker1', $result->attachments['file_id']);
        $this->assertSame("\u{1F600}", $result->attachments['emoji']);
    }

    public function test_parses_callback_query(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'callback_query' => [
                'id' => 'cbq1',
                'from' => $this->baseFrom(),
                'message' => [
                    'message_id' => 9,
                    'chat' => $this->baseChat(),
                    'date' => 1000,
                ],
                'data' => 'action:confirm',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::CallbackQuery, $result->type);
        $this->assertSame('action:confirm', $result->text);
        $this->assertSame('action:confirm', $result->attachments['callback_data']);
        $this->assertSame('9', $result->attachments['message_id']);
        $this->assertSame('123', $result->externalChatId);
        $this->assertSame('John Doe', $result->senderName);
    }

    public function test_parses_edited_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'edited_message' => [
                'message_id' => 10,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'text' => 'Edited text',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Text, $result->type);
        $this->assertSame('Edited text', $result->text);
    }

    public function test_returns_null_for_update_with_no_message_or_callback_query(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([]));
        $this->assertNull($result);
    }

    public function test_returns_null_for_callback_query_without_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'callback_query' => [
                'id' => 'cbq2',
                'from' => $this->baseFrom(),
                'data' => 'orphan',
            ],
        ]));

        $this->assertNull($result);
    }

    public function test_returns_null_for_message_with_no_recognized_content(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 11,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
            ],
        ]));

        $this->assertNull($result);
    }

    public function test_handles_sender_with_only_first_name(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 12,
                'from' => ['id' => 2, 'is_bot' => false, 'first_name' => 'Alice'],
                'chat' => $this->baseChat(),
                'date' => 1000,
                'text' => 'hi',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame('Alice', $result->senderName);
    }

    public function test_handles_message_without_from_field(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 13,
                'chat' => $this->baseChat(),
                'date' => 1000,
                'text' => 'anonymous',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame('Unknown', $result->senderName);
    }

    public function test_photo_takes_priority_over_text(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 14,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'text' => 'should be ignored',
                'photo' => [['file_id' => 'p1', 'file_unique_id' => 'pu1', 'width' => 200, 'height' => 200]],
                'caption' => 'photo caption',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Photo, $result->type);
        $this->assertSame('photo caption', $result->text);
    }

    public function test_callback_query_takes_priority_over_message(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'callback_query' => [
                'id' => 'cbq3',
                'from' => $this->baseFrom(),
                'message' => ['message_id' => 15, 'chat' => $this->baseChat(), 'date' => 1000, 'text' => 'ignored'],
                'data' => 'cb_data',
            ],
            'message' => [
                'message_id' => 15,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'text' => 'should be ignored',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::CallbackQuery, $result->type);
        $this->assertSame('cb_data', $result->text);
        $this->assertSame('cb_data', $result->attachments['callback_data']);
        $this->assertSame('15', $result->attachments['message_id']);
    }

    public function test_handles_photo_with_empty_array_gracefully(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 16,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'photo' => [],
                'text' => 'fallback text',
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Text, $result->type);
        $this->assertSame('fallback text', $result->text);
    }

    public function test_document_without_file_name_and_mime_type(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 17,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'document' => ['file_id' => 'doc2', 'file_unique_id' => 'd2'],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(MessageType::Document, $result->type);
        $this->assertSame('doc2', $result->attachments['file_id']);
        $this->assertSame('unknown', $result->attachments['file_name']);
        $this->assertNull($result->attachments['mime_type']);
    }

    public function test_contact_without_last_name(): void
    {
        $result = $this->parser->parse($this->channelId, $this->makeUpdate([
            'message' => [
                'message_id' => 18,
                'from' => $this->baseFrom(),
                'chat' => $this->baseChat(),
                'date' => 1000,
                'contact' => ['phone_number' => '+1234', 'first_name' => 'Bob'],
            ],
        ]));

        $this->assertNotNull($result);
        $this->assertSame('+1234', $result->attachments['phone_number']);
        $this->assertSame('Bob', $result->attachments['first_name']);
        $this->assertNull($result->attachments['last_name']);
    }
}
