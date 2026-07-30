<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Infrastructure\Persistence\EloquentMessageRepository;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class EloquentMessageRepositoryTest extends TestCase
{
    public function test_it_orders_user_before_assistant_when_created_at_matches(): void
    {
        $conversation = Conversation::query()->create([
            'title' => 'Order test',
            'primary_project_name' => null,
        ]);

        $timestamp = Carbon::parse('2026-07-30 12:00:00');

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant,
            'content' => 'Answer',
            'metadata' => [],
            'created_at' => $timestamp,
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User,
            'content' => 'Question',
            'metadata' => [],
            'created_at' => $timestamp,
        ]);

        $messages = (new EloquentMessageRepository)->forConversation($conversation);

        $this->assertCount(2, $messages);
        $this->assertSame(MessageRole::User, $messages[0]->role);
        $this->assertSame(MessageRole::Assistant, $messages[1]->role);
    }
}
