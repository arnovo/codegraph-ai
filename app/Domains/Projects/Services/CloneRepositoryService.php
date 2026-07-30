<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Exceptions\RepositoryCloneException;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;

final class CloneRepositoryService
{
    public function __construct(
        private readonly string $reposBasePath,
    ) {}

    public function execute(string $authenticatedCloneUrl, string $directoryName): string
    {
        $safeName = $this->sanitizeDirectoryName($directoryName);
        $base = rtrim(str_replace('\\', '/', $this->reposBasePath), '/');
        $target = $base.'/'.$safeName;

        if (file_exists($target)) {
            throw new InvalidArgumentException(
                'Ya existe un directorio con ese nombre en el área de repos.',
            );
        }

        $parent = dirname($target);
        if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
            throw new RepositoryCloneException('No se pudo crear el directorio de repos.');
        }

        $timeout = (int) config('projects.git.clone_timeout_seconds', 600);

        $result = Process::timeout($timeout)->run([
            'git',
            'clone',
            '--depth',
            '1',
            $authenticatedCloneUrl,
            $target,
        ]);

        if (! $result->successful()) {
            if (is_dir($target)) {
                $this->removeDirectory($target);
            }

            throw new RepositoryCloneException(
                $this->sanitizeGitError($result->errorOutput() ?: $result->output()),
            );
        }

        return $target;
    }

    /**
     * @return non-empty-string
     */
    private function sanitizeDirectoryName(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '' || str_contains($trimmed, '..') || str_contains($trimmed, '/')) {
            throw new InvalidArgumentException('Nombre de repositorio inválido.');
        }

        return $trimmed;
    }

    private function sanitizeGitError(string $output): string
    {
        $redacted = preg_replace('#https://[^:]+:[^@]+@#', 'https://***:***@', $output) ?? $output;
        $message = trim($redacted);

        if ($message === '') {
            return 'No se pudo clonar el repositorio. Comprueba la URL y las credenciales.';
        }

        if (str_contains(strtolower($message), 'authentication failed')) {
            return 'Autenticación fallida en Bitbucket. Comprueba usuario y token.';
        }

        return mb_substr($message, 0, 500);
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path.'/'.$item;
            if (is_dir($full)) {
                $this->removeDirectory($full);

                continue;
            }

            unlink($full);
        }

        rmdir($path);
    }
}
