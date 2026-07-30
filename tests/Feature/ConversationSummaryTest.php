<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\DTO\LlmResponseData;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use Tests\TestCase;

final class ConversationSummaryTest extends TestCase
{
    public function test_it_generates_summary_via_api(): void
    {
        $conversation = Conversation::query()->create([
            'title' => 'Payments',
            'primary_project_name' => 'demo-project',
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User,
            'content' => 'Where is payment captured?',
            'metadata' => [],
            'created_at' => now(),
        ]);

        $this->app->instance(LlmClientInterface::class, new class implements LlmClientInterface
        {
            public function chat(array $messages, array $tools = []): LlmResponseData
            {
                return new LlmResponseData(content: 'Resumen de pagos generado.');
            }

            public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData
            {
                return $this->chat($messages, $tools);
            }
        });

        $response = $this->postJson("/conversations/{$conversation->id}/summary");

        $response
            ->assertOk()
            ->assertJsonPath('summary', 'Resumen de pagos generado.')
            ->assertJsonPath('summary_message_count', 1)
            ->assertJsonPath('messages_count', 1);
    }

    public function test_it_lists_summary_fields_on_conversations_index(): void
    {
        $conversation = Conversation::query()->create([
            'title' => 'Cached summary',
            'primary_project_name' => 'demo-project',
            'summary' => 'Resumen previo.',
            'summary_message_count' => 2,
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User,
            'content' => 'Question one',
            'metadata' => [],
            'created_at' => now(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant,
            'content' => 'Answer one',
            'metadata' => [],
            'created_at' => now(),
        ]);

        $response = $this->getJson('/conversations');

        $response
            ->assertOk()
            ->assertJsonPath('0.summary', 'Resumen previo.')
            ->assertJsonPath('0.summary_message_count', 2)
            ->assertJsonPath('0.messages_count', 2);
    }
}
