<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTO;

final readonly class AgentStreamChunkData
{
    public function __construct(
        public string $type,
        public ?string $content = null,
        /** @var array<string, mixed>|null */
        public ?array $meta = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'content' => $this->content,
            'meta' => $this->meta,
        ], fn ($v) => $v !== null);
    }
}
