<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\MessageRepositoryInterface;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;

final class AppendMessageService
{
    public function __construct(
        private readonly MessageRepositoryInterface $messages,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        Conversation $conversation,
        MessageRole $role,
        string $content,
        array $metadata = [],
    ): Message {
        return $this->messages->append($conversation, $role, $content, $metadata);
    }
}
