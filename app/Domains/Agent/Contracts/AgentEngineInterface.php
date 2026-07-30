<?php

declare(strict_types=1);

namespace App\Domains\Agent\Contracts;

use App\Domains\Agent\DTO\AgentRequestData;
use App\Domains\Agent\DTO\AgentStreamChunkData;

interface AgentEngineInterface
{
    /**
     * @param  callable(AgentStreamChunkData): void  $emit
     */
    public function execute(AgentRequestData $request, callable $emit): void;
}
