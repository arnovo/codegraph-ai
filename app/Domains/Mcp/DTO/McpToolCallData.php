<?php

declare(strict_types=1);

namespace App\Domains\Mcp\DTO;

final readonly class McpToolCallData
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(
        public string $name,
        public array $arguments,
        public mixed $result = null,
        public ?string $resultSummary = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'arguments' => $this->arguments,
            'result_summary' => $this->resultSummary,
        ], fn ($value) => $value !== null);
    }
}
