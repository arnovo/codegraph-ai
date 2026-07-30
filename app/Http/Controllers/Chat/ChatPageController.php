<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Agent\Services\AgentProfileCatalog;
use App\Domains\Agent\Services\LlmModelCatalogService;
use App\Domains\Chat\Services\ListConversationsService;
use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Domains\Mcp\Exceptions\McpUnavailableException;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class ChatPageController extends Controller
{
    public function __invoke(
        ListConversationsService $conversations,
        ProjectCatalogInterface $projects,
        McpProcessManagerInterface $mcpProcess,
        LlmModelCatalogService $llmModels,
        AgentProfileCatalog $agentProfiles,
    ): Response {
        try {
            $projectRows = array_map(
                fn ($p) => $p->toArray(),
                $projects->list(),
            );
        } catch (McpUnavailableException) {
            $projectRows = [];
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations->execute(),
            'projects' => $projectRows,
            'activeConversationId' => null,
            'activeProjectName' => null,
            'mcpStatus' => $mcpProcess->status()->toArray(),
            'llmConfigured' => filled(config('llm.api_key')) || config('llm.driver') === 'custom',
            'llmModels' => array_map(fn ($profile) => $profile->toArray(), $llmModels->listAll()),
            'llmEnv' => $llmModels->envSnapshot(),
            'agentProfiles' => $agentProfiles->listForFrontend(),
        ]);
    }
}
