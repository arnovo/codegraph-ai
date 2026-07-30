<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Chat\Contracts\ConversationRepositoryInterface;
use App\Domains\Chat\Contracts\MessageRepositoryInterface;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use DomainException;
use RuntimeException;

final class GenerateConversationSummaryService
{
    private const int MAX_TRANSCRIPT_CHARS = 12000;

    public function __construct(
        private readonly LlmClientInterface $llm,
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MessageRepositoryInterface $messages,
    ) {}

    /**
     * @return array{
     *     summary: string,
     *     summary_message_count: int,
     *     messages_count: int,
     * }
     */
    public function execute(Conversation $conversation): array
    {
        $transcript = $this->buildTranscript($this->messages->forConversation($conversation));

        if ($transcript === '') {
            throw new DomainException('La conversación no tiene mensajes para resumir.');
        }

        $response = $this->llm->chat([
            [
                'role' => 'system',
                'content' => 'Eres un asistente que resume conversaciones técnicas sobre código. '
                    .'Responde en español, en prosa clara y concisa (máximo 8 frases). '
                    .'Incluye temas tratados, conclusiones y pendientes.',
            ],
            [
                'role' => 'user',
                'content' => "Resume esta conversación:\n\n{$transcript}",
            ],
        ]);

        $summary = trim((string) ($response->content ?? ''));

        if ($summary === '') {
            throw new RuntimeException('El modelo no devolvió un resumen.');
        }

        $messageCount = $this->messages->countForSummary($conversation);
        $updated = $this->conversations->updateSummary(
            conversation: $conversation,
            summary: $summary,
            messageCount: $messageCount,
        );

        return [
            'summary' => (string) $updated->summary,
            'summary_message_count' => (int) $updated->summary_message_count,
            'messages_count' => $messageCount,
        ];
    }

    /**
     * @param  list<Message>  $messages
     */
    private function buildTranscript(array $messages): string
    {
        $lines = [];

        foreach ($messages as $message) {
            if (! in_array($message->role, [MessageRole::User, MessageRole::Assistant], true)) {
                continue;
            }

            $content = trim($message->content);

            if ($content === '') {
                continue;
            }

            $label = $message->role === MessageRole::User ? 'Usuario' : 'Asistente';
            $lines[] = "{$label}: {$content}";
        }

        $transcript = implode("\n\n", $lines);

        if (strlen($transcript) <= self::MAX_TRANSCRIPT_CHARS) {
            return $transcript;
        }

        return substr($transcript, -self::MAX_TRANSCRIPT_CHARS);
    }
}
