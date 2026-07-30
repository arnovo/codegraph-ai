<?php

declare(strict_types=1);

namespace App\Domains\Chat\Contracts;

use App\Domains\Chat\Models\Conversation;

interface ConversationRepositoryInterface
{
    /**
     * @return list<Conversation>
     */
    public function allOrderedByUpdatedAt(): array;

    public function find(string $id): ?Conversation;

    public function create(string $title, ?string $primaryProjectName = null): Conversation;

    public function updateTitle(Conversation $conversation, string $title): Conversation;

    public function updateSummary(
        Conversation $conversation,
        string $summary,
        int $messageCount,
    ): Conversation;

    public function delete(Conversation $conversation): void;

    public function touchUpdatedAt(Conversation $conversation): void;
}
