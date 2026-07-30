<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Domains\Mcp\Exceptions\McpUnavailableException;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\Exceptions\RepositoryCloneException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloneRepositoryRequest;
use App\Http\Requests\IndexRepositoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use InvalidArgumentException;

final class ProjectController extends Controller
{
    public function index(ProjectCatalogInterface $catalog): JsonResponse
    {
        try {
            return response()->json(array_map(fn ($p) => $p->toArray(), $catalog->list()));
        } catch (McpUnavailableException $e) {
            return response()->json(['message' => $e->getMessage(), 'projects' => []], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    public function indexRepository(IndexRepositoryRequest $request, ProjectCatalogInterface $catalog): JsonResponse
    {
        $project = $catalog->index($request->validated('repo_path'));

        return response()->json($project->toArray(), Response::HTTP_CREATED);
    }

    public function cloneRepository(CloneRepositoryRequest $request, ProjectCatalogInterface $catalog): JsonResponse
    {
        try {
            $project = $catalog->cloneFromBitbucket(
                repositoryUrl: $request->validated('repository_url'),
                username: $request->validated('username'),
                apiToken: $request->validated('api_token'),
            );
        } catch (InvalidArgumentException|RepositoryCloneException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (McpUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json($project->toArray(), Response::HTTP_CREATED);
    }

    public function destroy(string $name, ProjectCatalogInterface $catalog): Response
    {
        $catalog->delete($name);

        return response()->noContent();
    }
}
