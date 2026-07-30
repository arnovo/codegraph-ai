<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\DTO\ProjectSummaryData;
use Tests\TestCase;

final class CloneProjectTest extends TestCase
{
    public function test_it_clones_and_indexes_bitbucket_repository(): void
    {
        $this->app->instance(ProjectCatalogInterface::class, new class implements ProjectCatalogInterface
        {
            public function list(): array
            {
                return [];
            }

            public function index(string $repoPath): ProjectSummaryData
            {
                return $this->summary();
            }

            public function cloneFromBitbucket(
                string $repositoryUrl,
                string $username,
                string $apiToken,
            ): ProjectSummaryData {
                return new ProjectSummaryData(
                    name: 'demo-repo',
                    rootPath: '/repos/demo-repo',
                    nodes: 10,
                    edges: 4,
                    sizeBytes: 1000,
                    displayName: 'Demo Repo',
                    primaryStack: 'Laravel',
                );
            }

            public function delete(string $name): void {}

            private function summary(): ProjectSummaryData
            {
                return new ProjectSummaryData(
                    name: 'demo-repo',
                    rootPath: '/repos/demo-repo',
                    nodes: 10,
                    edges: 4,
                    sizeBytes: 1000,
                    displayName: 'Demo Repo',
                    primaryStack: 'Laravel',
                );
            }
        });

        $response = $this->postJson('/projects/clone', [
            'repository_url' => 'https://bitbucket.org/prinex/demo-repo',
            'username' => 'dev.user',
            'api_token' => 'app-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'demo-repo')
            ->assertJsonPath('nodes', 10);
    }

    public function test_it_validates_clone_payload(): void
    {
        $response = $this->postJson('/projects/clone', [
            'repository_url' => '',
            'username' => '',
            'api_token' => '',
        ]);

        $response->assertUnprocessable();
    }
}
