<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Projects\Contracts\ProjectCatalogInterface;

final class SystemPromptFactory
{
    public function __construct(
        private readonly ProjectCatalogInterface $projects,
        private readonly ProjectSearchHintResolver $searchHints,
        private readonly AgentProfileCatalog $profiles,
    ) {}

    public function build(?string $activeProjectName = null, ?string $profileSlug = null): string
    {
        $profile = $this->profiles->resolve($profileSlug);

        if ($activeProjectName === null || trim($activeProjectName) === '') {
            return $this->buildWithoutProject($profile);
        }

        $primaryStack = $this->resolvePrimaryStack($activeProjectName);
        $searchHint = $this->searchHints->resolve($primaryStack);

        $responseRules = $this->buildResponseRules($profile['slug']);

        return <<<PROMPT
{$profile['persona']}
Responde en español salvo que el usuario pida otro idioma.
Perfil activo: {$profile['label']}.
Proyecto activo: {$activeProjectName}.
Stack detectado: {$primaryStack}.

{$searchHint}

Estilo de respuesta:
{$profile['style']}

Flujo (máximo 2 tools; una 3.ª solo si la pregunta exige trace_path):
1. search_graph — una query concreta (si total=0, reformula una vez con otros términos)
2. get_code_snippet — qualified_name exacto del mejor resultado
3. trace_path — solo para flujos, callers o cadena de llamadas; después responde sin más tools

Reglas:
- La lista de proyectos indexados está en el panel izquierdo; no la listes tú
- No repitas la misma tool con los mismos argumentos
- Tras 2 tools, responde con lo recuperado salvo que falte trace_path para contestar
- No encadenes varios search_graph si ya obtuviste hits útiles
{$responseRules}
- No inventes comportamiento no respaldado por snippets recuperados
- Si MCP no responde, indica que hay que levantar codebase-memory-mcp en el host
PROMPT;
    }

    /**
     * @param  array{slug: string, label: string, description: string, persona: string, style: string}  $profile
     */
    private function buildWithoutProject(array $profile): string
    {
        return <<<PROMPT
{$profile['persona']}
Responde en español salvo que el usuario pida otro idioma.
Perfil activo: {$profile['label']}.

Estilo de respuesta:
{$profile['style']}

No hay proyecto activo. No tienes tools disponibles.
Indica al usuario que seleccione un proyecto en el panel izquierdo de la app (lista cargada sin LLM).
Si pregunta qué repos hay indexados, remite al panel Proyectos — no intentes listarlos tú.
PROMPT;
    }

    private function buildResponseRules(string $profileSlug): string
    {
        if ($profileSlug === 'support') {
            return <<<'RULES'
- Tras obtener snippets (y trace_path si aplica), traduce el hallazgo a lenguaje de usuario final
- La respuesta visible nunca menciona archivos, rutas, líneas, símbolos ni código
- Centra la respuesta en qué puede o no hacer el usuario y qué pasos seguir
RULES;
        }

        return '- Tras obtener snippets (y trace_path si aplica), responde en texto natural citando archivo:línea';
    }

    private function resolvePrimaryStack(string $projectName): string
    {
        foreach ($this->projects->list() as $project) {
            if ($project->name !== $projectName) {
                continue;
            }

            $stack = trim($project->primaryStack);

            return $stack !== '' ? $stack : '—';
        }

        return '—';
    }
}
