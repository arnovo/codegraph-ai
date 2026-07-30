<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Agent\Contracts\AgentEngineInterface;
use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\Infrastructure\Llm\FallbackLlmClient;
use App\Domains\Agent\Infrastructure\Llm\LlmClientFactory;
use App\Domains\Agent\Infrastructure\Llm\LoggingLlmClient;
use App\Domains\Agent\Services\AgentProfileCatalog;
use App\Domains\Agent\Services\AskCodebaseAgentService;
use App\Domains\Agent\Services\InternalLlmAgentEngine;
use App\Domains\Agent\Services\LlmModelCatalogService;
use App\Domains\Agent\Services\LlmTrafficLogger;
use App\Domains\Agent\Services\ProjectSearchHintResolver;
use App\Domains\Agent\Services\SyncLlmModelProfilesService;
use App\Domains\Agent\Services\SystemPromptFactory;
use App\Domains\Agent\Services\ToolExecutionService;
use App\Domains\Chat\Contracts\ChatInsightsRepositoryInterface;
use App\Domains\Chat\Contracts\ConversationRepositoryInterface;
use App\Domains\Chat\Contracts\MessageRepositoryInterface;
use App\Domains\Chat\Services\AppendMessageService;
use App\Domains\Chat\Services\BuildChatInsightsService;
use App\Domains\Chat\Services\CreateConversationService;
use App\Domains\Chat\Services\DeleteConversationService;
use App\Domains\Chat\Services\GenerateConversationSummaryService;
use App\Domains\Chat\Services\ListConversationsService;
use App\Domains\Chat\Services\RenameConversationService;
use App\Domains\Internal\Services\BuildProjectStatusService;
use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Domains\Mcp\Services\DockerComposeMcpProcessManager;
use App\Domains\Mcp\Services\HostMcpProcessManager;
use App\Domains\Mcp\Services\McpCliFallbackClient;
use App\Domains\Mcp\Services\McpRpcClient;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\Services\BitbucketRepositoryUrlParser;
use App\Domains\Projects\Services\CachedProjectCatalogService;
use App\Domains\Projects\Services\CloneAndIndexRepositoryService;
use App\Domains\Projects\Services\CloneRepositoryService;
use App\Domains\Projects\Services\DeleteIndexedProjectService;
use App\Domains\Projects\Services\GitRepositoriesSyncService;
use App\Domains\Projects\Services\IndexRepositoryService;
use App\Domains\Projects\Services\ListIndexedProjectsService;
use App\Domains\Projects\Services\PrinexProjectOriginMatcher;
use App\Domains\Projects\Services\ProjectCatalogService;
use App\Domains\Projects\Services\ProjectGitOriginReader;
use App\Domains\Projects\Services\ProjectStackResolver;
use App\Domains\Projects\Services\RepositoryPathResolver;
use App\Infrastructure\Persistence\EloquentChatInsightsRepository;
use App\Infrastructure\Persistence\EloquentConversationRepository;
use App\Infrastructure\Persistence\EloquentMessageRepository;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BuildProjectStatusService::class, fn () => new BuildProjectStatusService(
            basePath: base_path(),
        ));

        $this->app->singleton(McpCliFallbackClient::class, fn () => new McpCliFallbackClient(
            binary: config('mcp.binary'),
        ));

        $this->app->singleton(McpClientInterface::class, function ($app) {
            $fallback = config('mcp.cli_fallback')
                ? $app->make(McpCliFallbackClient::class)
                : null;

            return new McpRpcClient(
                httpClient: new Client,
                rpcUrl: config('mcp.rpc_url'),
                healthTimeout: config('mcp.health_timeout'),
                fallback: $fallback,
            );
        });

        $this->app->singleton(McpProcessManagerInterface::class, function ($app) {
            if (config('mcp.on_host')) {
                return new HostMcpProcessManager(
                    mcpClient: $app->make(McpClientInterface::class),
                    uiUrl: config('mcp.ui_url'),
                );
            }

            return new DockerComposeMcpProcessManager(
                mcpClient: $app->make(McpClientInterface::class),
                workingDirectory: (string) config('mcp.docker.working_directory'),
                composeFile: (string) config('mcp.docker.compose_file'),
                serviceName: (string) config('mcp.docker.service_name'),
                uiUrl: config('mcp.ui_url'),
            );
        });

        $this->app->bind(ConversationRepositoryInterface::class, EloquentConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, EloquentMessageRepository::class);
        $this->app->bind(ChatInsightsRepositoryInterface::class, EloquentChatInsightsRepository::class);

        $this->app->singleton(ListIndexedProjectsService::class);
        $this->app->singleton(RepositoryPathResolver::class);
        $this->app->singleton(ProjectGitOriginReader::class);
        $this->app->singleton(PrinexProjectOriginMatcher::class);
        $this->app->singleton(ProjectStackResolver::class);
        $this->app->singleton(IndexRepositoryService::class, fn () => new IndexRepositoryService(
            mcpClient: $this->app->make(McpClientInterface::class),
            reposContainerPath: config('mcp.repos.host_path', config('mcp.repos.container_path')),
            stackResolver: $this->app->make(ProjectStackResolver::class),
        ));
        $this->app->singleton(CloneRepositoryService::class, fn () => new CloneRepositoryService(
            reposBasePath: config('mcp.repos.host_path', config('mcp.repos.container_path')),
        ));
        $this->app->singleton(CloneAndIndexRepositoryService::class);
        $this->app->singleton(GitRepositoriesSyncService::class, fn () => new GitRepositoriesSyncService(
            urlParser: $this->app->make(BitbucketRepositoryUrlParser::class),
            reposBasePath: config('mcp.repos.host_path', config('mcp.repos.container_path')),
            username: config('projects.git.username'),
            token: config('projects.git.token'),
            cloneTimeoutSeconds: (int) config('projects.git.clone_timeout_seconds', 600),
        ));
        $this->app->singleton(DeleteIndexedProjectService::class);

        $this->app->singleton(ProjectCatalogService::class);
        $this->app->bind(ProjectCatalogInterface::class, CachedProjectCatalogService::class);

        $this->app->singleton(LlmTrafficLogger::class);
        $this->app->singleton(LlmModelCatalogService::class);
        $this->app->singleton(SyncLlmModelProfilesService::class);

        $this->app->bind(LlmClientInterface::class, function ($app) {
            $config = config('llm');
            $driver = (string) ($config['driver'] ?? 'openai');

            if (in_array($driver, ['openai', 'custom'], true)) {
                return new FallbackLlmClient(
                    catalog: $app->make(LlmModelCatalogService::class),
                    logger: $app->make(LlmTrafficLogger::class),
                    config: $config,
                );
            }

            $inner = LlmClientFactory::make($config);

            return new LoggingLlmClient(
                inner: $inner,
                logger: $app->make(LlmTrafficLogger::class),
                model: (string) ($config['model'] ?? ''),
                provider: $driver,
            );
        });

        $this->app->singleton(ToolExecutionService::class);
        $this->app->singleton(ProjectSearchHintResolver::class);
        $this->app->singleton(AgentProfileCatalog::class);
        $this->app->singleton(SystemPromptFactory::class);
        $this->app->singleton(InternalLlmAgentEngine::class, fn () => new InternalLlmAgentEngine(
            llm: $this->app->make(LlmClientInterface::class),
            tools: $this->app->make(ToolExecutionService::class),
            prompts: $this->app->make(SystemPromptFactory::class),
            conversations: $this->app->make(ConversationRepositoryInterface::class),
            messages: $this->app->make(MessageRepositoryInterface::class),
            modelCatalog: $this->app->make(LlmModelCatalogService::class),
            maxIterations: (int) config('llm.max_tool_iterations', 3),
        ));
        $this->app->bind(AgentEngineInterface::class, InternalLlmAgentEngine::class);

        $this->app->singleton(AskCodebaseAgentService::class);

        $this->app->singleton(CreateConversationService::class);
        $this->app->singleton(BuildChatInsightsService::class);
        $this->app->singleton(ListConversationsService::class);
        $this->app->singleton(AppendMessageService::class);
        $this->app->singleton(RenameConversationService::class);
        $this->app->singleton(GenerateConversationSummaryService::class);
        $this->app->singleton(DeleteConversationService::class);
    }

    public function boot(): void
    {
        //
    }
}
