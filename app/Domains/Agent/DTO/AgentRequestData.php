<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTO;

final readonly class AgentRequestData
{
    public function __construct(
        public string $conversationId,
        public string $userMessage,
        public ?string $activeProjectName = null,
        public ?string $agentProfileSlug = null,
    ) {}
}
