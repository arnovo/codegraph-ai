<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

final class McpStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    /** @param  array<string, mixed>  $status */
    public function __construct(public array $status) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('mcp-status')];
    }

    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->status;
    }
}
