<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Projects\Exceptions\RepositoryCloneException;
use App\Domains\Projects\Services\CloneRepositoryService;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Tests\TestCase;

final class CloneRepositoryServiceTest extends TestCase
{
    public function test_it_runs_git_clone_into_repos_base(): void
    {
        $base = sys_get_temp_dir().'/clone-service-'.uniqid('', true);
        mkdir($base, 0755, true);

        Process::fake([
            '*' => Process::result(exitCode: 0),
        ]);

        $service = new CloneRepositoryService(reposBasePath: $base);
        $path = $service->execute(
            authenticatedCloneUrl: 'https://user:token@bitbucket.org/prinex/demo.git',
            directoryName: 'demo',
        );

        $this->assertSame($base.'/demo', $path);

        Process::assertRan(function ($process) {
            return in_array('clone', $process->command, true)
                && in_array('--depth', $process->command, true)
                && str_contains(implode(' ', $process->command), 'bitbucket.org/prinex/demo.git');
        });

        @rmdir($base);
    }

    public function test_it_rejects_existing_directory(): void
    {
        $base = sys_get_temp_dir().'/clone-service-'.uniqid('', true);
        mkdir($base.'/demo', 0755, true);

        $service = new CloneRepositoryService(reposBasePath: $base);

        $this->expectException(InvalidArgumentException::class);
        $service->execute(
            authenticatedCloneUrl: 'https://user:token@bitbucket.org/prinex/demo.git',
            directoryName: 'demo',
        );

        @rmdir($base.'/demo');
        @rmdir($base);
    }

    public function test_it_raises_repository_clone_exception_on_git_failure(): void
    {
        $base = sys_get_temp_dir().'/clone-service-'.uniqid('', true);
        mkdir($base, 0755, true);

        Process::fake([
            '*' => Process::result(
                errorOutput: 'fatal: Authentication failed for https://user:secret@bitbucket.org/prinex/demo.git',
                exitCode: 128,
            ),
        ]);

        $service = new CloneRepositoryService(reposBasePath: $base);

        try {
            $service->execute(
                authenticatedCloneUrl: 'https://user:secret@bitbucket.org/prinex/demo.git',
                directoryName: 'demo',
            );
        } catch (RepositoryCloneException $exception) {
            $this->assertStringContainsString('Autenticación fallida', $exception->getMessage());
            $this->assertStringNotContainsString('secret', $exception->getMessage());
            @rmdir($base);

            return;
        }

        $this->fail('Expected RepositoryCloneException');
    }
}
