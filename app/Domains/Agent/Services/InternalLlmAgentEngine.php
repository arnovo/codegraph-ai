<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Agent\Contracts\AgentEngineInterface;
use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\DTO\AgentRequestData;
use App\Domains\Agent\DTO\AgentStreamChunkData;
use App\Domains\Chat\Contracts\ConversationRepositoryInterface;
use App\Domains\Chat\Contracts\MessageRepositoryInterface;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Support\LlmErrorFormatter;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

final class InternalLlmAgentEngine implements AgentEngineInterface
{
    public function __construct(
        private readonly LlmClientInterface $llm,
        private readonly ToolExecutionService $tools,
        private readonly SystemPromptFactory $prompts,
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MessageRepositoryInterface $messages,
        private readonly LlmModelCatalogService $modelCatalog,
        private readonly int $maxIterations,
    ) {}

    /**
     * @param  callable(AgentStreamChunkData): void  $emit
     */
    public function execute(AgentRequestData $request, callable $emit): void
    {
        try {
            $this->run($request, $emit);
        } catch (Throwable $e) {
            $raw = $e instanceof RuntimeException ? $e->getMessage() : 'Error inesperado del agente.';
            $emit(new AgentStreamChunkData('error', LlmErrorFormatter::forUser($raw, $this->llmContext())));
        }
    }

    /** @return array<string, mixed> */
    private function llmContext(): array
    {
        return [
            'driver' => config('llm.driver'),
            'model' => config('llm.model'),
            'base_url' => config('llm.base_url'),
            'api_key' => config('llm.api_key'),
            'max_tool_iterations' => config('llm.max_tool_iterations'),
        ];
    }

    /**
     * @param  callable(AgentStreamChunkData): void  $emit
     */
    private function run(AgentRequestData $request, callable $emit): void
    {
        $conversation = $this->conversations->find($request->conversationId);
        if ($conversation === null) {
            $emit(new AgentStreamChunkData('error', 'Conversación no encontrada.'));

            return;
        }

        $this->messages->append($conversation, MessageRole::User, $request->userMessage);
        $activeProject = $request->activeProjectName ?? $conversation->primary_project_name;
        $history = $this->buildMessages($conversation, $activeProject, $request->agentProfileSlug);
        $toolDefs = $this->tools->openAiToolDefinitions($activeProject);
        $executedTools = [];

        for ($i = 0; $i < $this->maxIterations; $i++) {
            $response = $this->llm->chat($history, $toolDefs);

            if ($response->hasToolCalls()) {
                $toolCalls = $this->normalizeToolCalls($response->toolCalls);

                $history[] = [
                    'role' => 'assistant',
                    'content' => $response->content ?? '',
                    'tool_calls' => $toolCalls,
                ];

                foreach ($toolCalls as $toolCall) {
                    $name = $toolCall['function']['name'] ?? '';
                    try {
                        $args = json_decode($toolCall['function']['arguments'] ?? '{}', true, 512, JSON_THROW_ON_ERROR) ?: [];
                    } catch (JsonException) {
                        $args = [];
                    }

                    if ($activeProject && ! isset($args['project'])) {
                        $args['project'] = $activeProject;
                    }

                    $executed = $this->tools->execute($name, $args, $activeProject);
                    $executedTools[] = ['name' => $name, 'arguments' => $args, 'result_summary' => $executed['summary']];
                    $emit(new AgentStreamChunkData('tool', null, ['name' => $name, 'arguments' => $args]));

                    $history[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($executed['result'], JSON_UNESCAPED_UNICODE) ?: '',
                    ];
                }

                continue;
            }

            $content = trim((string) ($response->content ?? ''));
            if ($content !== '') {
                $this->emitTokens($content, $emit);
            }

            $metadata = [
                'tools' => $executedTools,
                'model' => $response->model,
                'provider' => $response->provider,
                'label' => $this->modelCatalog->labelForModel($response->model),
                'engine' => 'internal',
            ];

            $this->messages->append(
                $conversation,
                MessageRole::Assistant,
                $content !== '' ? $content : '(Sin respuesta de texto del modelo.)',
                $metadata,
            );
            $this->conversations->touchUpdatedAt($conversation);

            $emit(new AgentStreamChunkData('done', null, $metadata));

            return;
        }

        $emit(new AgentStreamChunkData('error', 'Límite de iteraciones de tools alcanzado.'));
    }

    /**
     * @param  callable(AgentStreamChunkData): void  $emit
     */
    private function emitTokens(string $content, callable $emit): void
    {
        foreach (str_split($content, 24) as $chunk) {
            $emit(new AgentStreamChunkData('token', $chunk));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @return list<array<string, mixed>>
     */
    private function normalizeToolCalls(array $toolCalls): array
    {
        return array_map(function (array $toolCall): array {
            if (($toolCall['id'] ?? '') === '') {
                $toolCall['id'] = 'call_'.str_replace('-', '', (string) Str::uuid());
            }

            return $toolCall;
        }, $toolCalls);
    }

    /** @return list<array<string, mixed>> */
    private function buildMessages(Conversation $conversation, ?string $activeProject, ?string $profileSlug): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->prompts->build($activeProject ?? $conversation->primary_project_name, $profileSlug)],
        ];

        foreach ($this->messages->forConversation($conversation) as $msg) {
            $messages[] = [
                'role' => $msg->role->value,
                'content' => $msg->content,
            ];
        }

        return $messages;
    }
}
