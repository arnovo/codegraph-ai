<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\DTO\LlmResponseData;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Chat\Services\GenerateConversationSummaryService;
use DomainException;
use Tests\TestCase;

final class GenerateConversationSummaryServiceTest extends TestCase
{
    public function test_it_generates_and_persists_conversation_summary(): void
    {
        $conversation = Conversation::query()->create([
            'title' => 'Auth flow',
            'primary_project_name' => 'demo-project',
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User,
            'content' => 'How does auth work?',
            'metadata' => [],
            'created_at' => now(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant,
            'content' => 'Auth middleware handles it.',
            'metadata' => [],
            'created_at' => now(),
        ]);

        $this->app->instance(LlmClientInterface::class, new class implements LlmClientInterface
        {
            public function chat(array $messages, array $tools = []): LlmResponseData
            {
                return new LlmResponseData(
                    content: 'Resumen: se explicó el flujo de autenticación.',
                    model: 'test-model',
                    provider: 'test',
                );
            }

            public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData
            {
                return $this->chat($messages, $tools);
            }
        });

        $result = $this->app->make(GenerateConversationSummaryService::class)->execute($conversation);

        $this->assertSame('Resumen: se explicó el flujo de autenticación.', $result['summary']);
        $this->assertSame(2, $result['summary_message_count']);
        $this->assertSame(2, $result['messages_count']);

        $conversation->refresh();
        $this->assertSame('Resumen: se explicó el flujo de autenticación.', $conversation->summary);
        $this->assertSame(2, $conversation->summary_message_count);
    }

    public function test_it_fails_when_conversation_has_no_messages(): void
    {
        $conversation = Conversation::query()->create([
            'title' => 'Empty',
            'primary_project_name' => null,
        ]);

        $this->app->instance(LlmClientInterface::class, new class implements LlmClientInterface
        {
            public function chat(array $messages, array $tools = []): LlmResponseData
            {
                return new LlmResponseData(content: 'unused');
            }

            public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData
            {
                return $this->chat($messages, $tools);
            }
        });

        $this->expectException(DomainException::class);

        $this->app->make(GenerateConversationSummaryService::class)->execute($conversation);
    }
}
