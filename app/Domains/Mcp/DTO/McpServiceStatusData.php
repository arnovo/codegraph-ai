<?php

declare(strict_types=1);

namespace App\Domains\Mcp\DTO;

use DateTimeImmutable;

final readonly class McpServiceStatusData
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_STOPPED = 'stopped';

    public const STATUS_UNKNOWN = 'unknown';

    public const STATUS_UNHEALTHY = 'unhealthy';

    public function __construct(
        public string $status,
        public DateTimeImmutable $checkedAt,
        public string $uiUrl,
        public ?string $message = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'checked_at' => $this->checkedAt->format(DATE_ATOM),
            'ui_url' => $this->uiUrl,
            'message' => $this->message,
        ];
    }
}
