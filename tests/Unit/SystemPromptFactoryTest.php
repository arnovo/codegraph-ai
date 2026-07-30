<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Agent\Services\AgentProfileCatalog;
use App\Domains\Agent\Services\ProjectSearchHintResolver;
use App\Domains\Agent\Services\SystemPromptFactory;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\DTO\ProjectSummaryData;
use Tests\TestCase;

final class SystemPromptFactoryTest extends TestCase
{
    public function test_it_includes_stack_hints_and_tool_budget_in_prompt(): void
    {
        $factory = $this->makeFactory();
        $prompt = $factory->build('demo-rn');

        $this->assertStringContainsString('Stack detectado: React Native', $prompt);
        $this->assertStringContainsString('navigation', $prompt);
        $this->assertStringContainsString('máximo 2 tools', $prompt);
        $this->assertStringContainsString('trace_path', $prompt);
        $this->assertStringContainsString('Perfil activo: Desarrollo', $prompt);
    }

    public function test_it_uses_support_profile_persona(): void
    {
        $factory = $this->makeFactory();
        $prompt = $factory->build('demo-rn', 'support');

        $this->assertStringContainsString('Perfil activo: Soporte', $prompt);
        $this->assertStringContainsString('agente de soporte', $prompt);
        $this->assertStringContainsString('qué puede o no hacer', $prompt);
        $this->assertStringContainsString('nunca menciona archivos', $prompt);
        $this->assertStringNotContainsString('citando archivo:línea', $prompt);
    }

    public function test_developer_profile_still_requires_file_citations(): void
    {
        $factory = $this->makeFactory();
        $prompt = $factory->build('demo-rn', 'developer');

        $this->assertStringContainsString('citando archivo:línea', $prompt);
    }

    private function makeFactory(): SystemPromptFactory
    {
        $catalog = new class implements ProjectCatalogInterface
        {
            public function list(): array
            {
                return [
                    new ProjectSummaryData(
                        name: 'demo-rn',
                        rootPath: '/tmp/demo-rn',
                        nodes: 1,
                        edges: 0,
                        sizeBytes: 0,
                        displayName: 'demo-rn',
                        primaryStack: 'React Native',
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
        };

        return new SystemPromptFactory(
            projects: $catalog,
            searchHints: new ProjectSearchHintResolver,
            profiles: new AgentProfileCatalog,
        );
    }
}
