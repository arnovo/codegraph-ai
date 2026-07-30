<?php

declare(strict_types=1);

namespace App\Domains\Agent\Contracts;

use App\Domains\Agent\DTO\LlmResponseData;

interface LlmClientInterface
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     */
    public function chat(array $messages, array $tools = []): LlmResponseData;

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @param  callable(string): void  $onChunk
     */
    public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData;
}
