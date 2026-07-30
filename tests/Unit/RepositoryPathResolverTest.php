<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Projects\Services\RepositoryPathResolver;
use Tests\TestCase;

final class RepositoryPathResolverTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir().'/repo-path-'.uniqid('', true);
        mkdir($this->tempRoot.'/container/demo/.git', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempRoot);

        parent::tearDown();
    }

    public function test_it_maps_host_repo_path_to_container_mount(): void
    {
        config([
            'mcp.repos.host_path' => $this->tempRoot.'/host',
            'mcp.repos.container_path' => $this->tempRoot.'/container',
        ]);

        $resolver = new RepositoryPathResolver;

        $resolved = $resolver->resolveAccessiblePath($this->tempRoot.'/host/demo');

        $this->assertSame($this->tempRoot.'/container/demo', $resolved);
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

            $fullPath = $path.'/'.$item;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);

                continue;
            }

            unlink($fullPath);
        }

        rmdir($path);
    }
}
