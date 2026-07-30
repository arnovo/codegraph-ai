<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\ConversationRepositoryInterface;

final class ListConversationsService
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(): array
    {
        return array_map(
            static fn ($conversation) => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'primary_project_name' => $conversation->primary_project_name,
                'summary' => $conversation->summary,
                'summary_message_count' => $conversation->summary_message_count,
                'messages_count' => (int) ($conversation->messages_count ?? 0),
                'updated_at' => $conversation->updated_at?->toIso8601String(),
            ],
            $this->conversations->allOrderedByUpdatedAt(),
        );
    }
}
