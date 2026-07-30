<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domains\Chat\Contracts\MessageRepositoryInterface;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;

final class EloquentMessageRepository implements MessageRepositoryInterface
{
    /**
     * @return list<Message>
     */
    public function forConversation(Conversation $conversation): array
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->orderByRaw(
                "CASE role WHEN 'user' THEN 0 WHEN 'tool' THEN 1 WHEN 'assistant' THEN 2 ELSE 3 END",
            )
            ->get()
            ->all();
    }

    public function append(
        Conversation $conversation,
        MessageRole $role,
        string $content,
        array $metadata = [],
    ): Message {
        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $conversation->touch();

        return $message;
    }

    public function countForSummary(Conversation $conversation): int
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('role', [MessageRole::User, MessageRole::Assistant])
            ->count();
    }
}
