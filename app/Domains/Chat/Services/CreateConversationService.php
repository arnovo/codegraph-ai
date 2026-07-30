<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\ConversationRepositoryInterface;
use App\Domains\Chat\Models\Conversation;

final class CreateConversationService
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
    ) {}

    public function execute(?string $title = null, ?string $primaryProjectName = null): Conversation
    {
        $resolvedTitle = $title !== null && trim($title) !== ''
            ? trim($title)
            : 'Nueva conversación';

        return $this->conversations->create($resolvedTitle, $primaryProjectName);
    }
}
