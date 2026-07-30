<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Chat\Contracts\ChatInsightsRepositoryInterface;
use App\Domains\Chat\Services\BuildChatInsightsService;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\DTO\ProjectSummaryData;
use Tests\TestCase;

final class BuildChatInsightsServiceTest extends TestCase
{
    public function test_it_builds_empty_insights_when_no_messages_exist(): void
    {
        $repository = new class implements ChatInsightsRepositoryInterface
        {
            public function countUserQuestions(?string $projectName = null): int
            {
                return 0;
            }

            public function countUserQuestionsSince(int $days, ?string $projectName = null): int
            {
                return 0;
            }

            public function countConversationsSince(int $days): int
            {
                return 0;
            }

            public function userMessagesByDay(int $days, ?string $projectName = null): array
            {
                return [];
            }

            public function topProjectsByQuestionCount(int $limit = 5): array
            {
                return [];
            }

            public function userMessageContents(?string $projectName = null): array
            {
                return [];
            }

            public function assistantMessageMetadata(?string $projectName = null): array
            {
                return [];
            }
        };

        $catalog = new class implements ProjectCatalogInterface
        {
            public function list(): array
            {
                return [];
            }

            public function index(string $repoPath): ProjectSummaryData
            {
                return new ProjectSummaryData('demo', '/tmp', 0, 0, 0);
            }

            public function cloneFromBitbucket(
                string $repositoryUrl,
                string $username,
                string $apiToken,
            ): ProjectSummaryData {
                return new ProjectSummaryData('demo', '/tmp', 0, 0, 0);
            }

            public function delete(string $name): void {}
        };

        $service = new BuildChatInsightsService($repository, $catalog);
        $insights = $service->execute('demo-project');

        $this->assertSame(0, $insights->totalUserQuestions);
        $this->assertSame(0, $insights->projectUserQuestions);
        $this->assertNull($insights->activeProjectSharePercent);
        $this->assertSame([], $insights->frequentQuestions);
    }
}
