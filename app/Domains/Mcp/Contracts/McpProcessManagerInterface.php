<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Contracts;

use App\Domains\Mcp\DTO\McpServiceStatusData;

interface McpProcessManagerInterface
{
    public function start(): McpServiceStatusData;

    public function stop(): McpServiceStatusData;

    public function status(): McpServiceStatusData;
}
