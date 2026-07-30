<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Agent\DTO\AgentRequestData;
use App\Domains\Agent\Services\AskCodebaseAgentService;
use App\Domains\Chat\Models\Conversation;
use App\Http\Requests\SendChatMessageRequest;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AgentStreamController
{
    public function __invoke(
        SendChatMessageRequest $request,
        Conversation $conversation,
        AskCodebaseAgentService $agent,
    ): StreamedResponse {
        $payload = $request->validated();

        return response()->stream(function () use ($agent, $conversation, $payload) {
            $agent->execute(
                new AgentRequestData(
                    conversationId: $conversation->id,
                    userMessage: $payload['message'],
                    activeProjectName: $payload['active_project_name'] ?? $conversation->primary_project_name,
                    agentProfileSlug: $payload['agent_profile'] ?? null,
                ),
                function ($chunk) {
                    echo 'data: '.json_encode($chunk->toArray(), JSON_UNESCAPED_UNICODE)."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                },
            );
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
