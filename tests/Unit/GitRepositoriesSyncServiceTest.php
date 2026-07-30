<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Projects\Services\BitbucketRepositoryUrlParser;
use App\Domains\Projects\Services\GitRepositoriesSyncService;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

final class GitRepositoriesSyncServiceTest extends TestCase
{
    public function test_it_clones_new_repository(): void
    {
        $base = sys_get_temp_dir().'/git-sync-'.uniqid();
        mkdir($base, 0755, true);

        Process::fake([
            '*' => Process::result(exitCode: 0),
        ]);

        $service = new GitRepositoriesSyncService(
            urlParser: new BitbucketRepositoryUrlParser,
            reposBasePath: $base,
            username: 'dev',
            token: 'secret',
            cloneTimeoutSeconds: 60,
        );

        $results = $service->sync(['https://bitbucket.org/prinex/demo-repo']);

        $this->assertCount(1, $results);
        $this->assertSame('clone', $results[0]['action']);
        $this->assertStringEndsWith('/demo-repo', $results[0]['path']);

        Process::assertRan(function ($process) {
            return ($process->command[0] ?? '') === 'git'
                && ($process->command[1] ?? '') === 'clone';
        });

        $this->removeDirectory($base);
    }

    public function test_it_pulls_existing_repository(): void
    {
        $base = sys_get_temp_dir().'/git-sync-'.uniqid();
        $repo = $base.'/demo-repo';
        mkdir($repo.'/.git', 0755, true);

        Process::fake([
            '*' => Process::result(exitCode: 0),
        ]);

        $service = new GitRepositoriesSyncService(
            urlParser: new BitbucketRepositoryUrlParser,
            reposBasePath: $base,
            username: 'dev',
            token: 'secret',
            cloneTimeoutSeconds: 60,
        );

        $results = $service->sync(['https://bitbucket.org/prinex/demo-repo']);

        $this->assertCount(1, $results);
        $this->assertSame('pull', $results[0]['action']);

        Process::assertRan(function ($process) {
            return ($process->command[0] ?? '') === 'git'
                && ($process->command[1] ?? '') === 'pull';
        });

        $this->removeDirectory($base);
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
