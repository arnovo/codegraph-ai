<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\DTO\ProjectSummaryData;
use Tests\TestCase;

final class ChatInsightsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ProjectCatalogInterface::class, new class implements ProjectCatalogInterface
        {
            public function list(): array
            {
                return [
                    new ProjectSummaryData(
                        name: 'demo-project',
                        rootPath: '/tmp/demo',
                        nodes: 1,
                        edges: 0,
                        sizeBytes: 0,
                        displayName: 'Demo Project',
                        primaryStack: 'Laravel',
                    ),
                ];
            }

            public function index(string $repoPath): ProjectSummaryData
            {
                return $this->list()[0];
            }

            public function cloneFromBitbucket(
                string $repositoryUrl,
                string $username,
                string $apiToken,
            ): ProjectSummaryData {
                return $this->list()[0];
            }

            public function delete(string $name): void {}
        });
    }

    public function test_it_returns_chat_insights_summary(): void
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
            'metadata' => [
                'tools' => [
                    ['name' => 'search_graph', 'arguments' => ['query' => 'auth middleware']],
                ],
                'citations' => [
                    ['file' => 'app/Http/Middleware/Auth.php', 'line' => 12],
                ],
                'model' => 'gpt-4o-mini',
            ],
            'created_at' => now(),
        ]);

        $response = $this->getJson('/chat/insights?project=demo-project');

        $response
            ->assertOk()
            ->assertJsonPath('activity.total_user_questions', 1)
            ->assertJsonPath('activity.project_user_questions', 1)
            ->assertJsonPath('projects.top_by_questions.0.display_name', 'Demo Project')
            ->assertJsonPath('frequent_questions.0.text', 'how does auth work?')
            ->assertJsonPath('tools.by_name.0.name', 'search_graph')
            ->assertJsonPath('citations.top_files.0.file', 'app/Http/Middleware/Auth.php')
            ->assertJsonPath('models.top_by_usage.0.model', 'gpt-4o-mini');
    }
}
