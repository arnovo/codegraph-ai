<?php

declare(strict_types=1);

namespace App\Domains\Chat\Contracts;

use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;

interface MessageRepositoryInterface
{
    /**
     * @return list<Message>
     */
    public function forConversation(Conversation $conversation): array;

    public function append(
        Conversation $conversation,
        MessageRole $role,
        string $content,
        array $metadata = [],
    ): Message;

    public function countForSummary(Conversation $conversation): int;
}
