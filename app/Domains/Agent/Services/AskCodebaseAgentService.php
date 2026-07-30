<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Agent\Contracts\AgentEngineInterface;
use App\Domains\Agent\DTO\AgentRequestData;
use App\Domains\Agent\DTO\AgentStreamChunkData;

final class AskCodebaseAgentService
{
    public function __construct(
        private readonly AgentEngineInterface $engine,
    ) {}

    /**
     * @param  callable(AgentStreamChunkData): void  $emit
     */
    public function execute(AgentRequestData $request, callable $emit): void
    {
        $this->engine->execute($request, $emit);
    }
}
