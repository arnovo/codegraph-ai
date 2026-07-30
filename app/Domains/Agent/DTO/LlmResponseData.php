<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTO;

final readonly class LlmResponseData
{
    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  array<string, mixed>  $usage
     */
    public function __construct(
        public ?string $content,
        public array $toolCalls = [],
        public string $model = '',
        public string $provider = '',
        public array $usage = [],
        public ?string $finishReason = null,
    ) {}

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
