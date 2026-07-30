<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\ConversationRepositoryInterface;
use App\Domains\Chat\Models\Conversation;

final class RenameConversationService
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
    ) {}

    public function execute(Conversation $conversation, string $title): Conversation
    {
        return $this->conversations->updateTitle($conversation, trim($title));
    }
}
