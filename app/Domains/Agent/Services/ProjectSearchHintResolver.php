<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

final class ProjectSearchHintResolver
{
    public function resolve(?string $primaryStack): string
    {
        $stack = strtolower(trim((string) $primaryStack));

        if ($stack === '' || $stack === '—') {
            return 'Adapta search_graph al stack del repo. Si total=0, reformula con sinónimos del dominio; no repitas la misma query.';
        }

        if (str_contains($stack, 'react native') || str_contains($stack, 'flutter')) {
            return 'Búsqueda móvil: screen, navigation, component, hook, stack — evita "controller" o patrones Laravel.';
        }

        if (str_contains($stack, 'laravel') || str_contains($stack, 'symfony') || $stack === 'php') {
            return 'Búsqueda backend PHP: Controller, Service, Repository, Action, Middleware, Request.';
        }

        if (
            str_contains($stack, 'vue')
            || str_contains($stack, 'react')
            || str_contains($stack, 'next')
            || str_contains($stack, 'node')
        ) {
            return 'Búsqueda frontend: component, page, composable, store, route, layout — no asumas controllers de servidor.';
        }

        if (str_contains($stack, 'python')) {
            return 'Búsqueda Python: module, service, router, handler, task, repository.';
        }

        return 'Adapta search_graph al stack ('.$primaryStack.'). Si total=0, reformula; no repitas la misma query.';
    }
}
