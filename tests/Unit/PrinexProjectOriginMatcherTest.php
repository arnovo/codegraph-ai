<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Projects\Services\PrinexProjectOriginMatcher;
use App\Domains\Projects\Services\ProjectGitOriginReader;
use App\Domains\Projects\Services\RepositoryPathResolver;
use Tests\TestCase;

final class PrinexProjectOriginMatcherTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir().'/prinex-origin-'.uniqid('', true);
        mkdir($this->tempRoot, 0777, true);

        config([
            'mcp.projects_filter.prinex_only' => true,
            'mcp.projects_filter.origin_markers' => [
                'bitbucket.org/prinex/',
                'bitbucket.org:prinex/',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempRoot);

        parent::tearDown();
    }

    public function test_it_accepts_bitbucket_ssh_prinex_origin(): void
    {
        $repoPath = $this->createRepoWithOrigin('git@bitbucket.org:prinex/admin_backend.git');
        $matcher = new PrinexProjectOriginMatcher(new ProjectGitOriginReader(new RepositoryPathResolver));

        $this->assertTrue($matcher->isPrinexProject($repoPath));
    }

    public function test_it_accepts_bitbucket_https_prinex_origin(): void
    {
        $repoPath = $this->createRepoWithOrigin('https://bitbucket.org/prinex/prinex-design-system.git');
        $matcher = new PrinexProjectOriginMatcher(new ProjectGitOriginReader(new RepositoryPathResolver));

        $this->assertTrue($matcher->isPrinexProject($repoPath));
    }

    public function test_it_rejects_non_prinex_origin(): void
    {
        $repoPath = $this->createRepoWithOrigin('git@github.com:acme/demo.git');
        $matcher = new PrinexProjectOriginMatcher(new ProjectGitOriginReader(new RepositoryPathResolver));

        $this->assertFalse($matcher->isPrinexProject($repoPath));
    }

    public function test_it_rejects_repo_without_git_origin(): void
    {
        $repoPath = $this->tempRoot.'/no-git';
        mkdir($repoPath);

        $matcher = new PrinexProjectOriginMatcher(new ProjectGitOriginReader(new RepositoryPathResolver));

        $this->assertFalse($matcher->isPrinexProject($repoPath));
    }

    public function test_it_allows_all_projects_when_filter_disabled(): void
    {
        config(['mcp.projects_filter.prinex_only' => false]);

        $repoPath = $this->createRepoWithOrigin('git@github.com:acme/demo.git');
        $matcher = new PrinexProjectOriginMatcher(new ProjectGitOriginReader(new RepositoryPathResolver));

        $this->assertTrue($matcher->isPrinexProject($repoPath));
    }

    private function createRepoWithOrigin(string $originUrl): string
    {
        $repoPath = $this->tempRoot.'/'.md5($originUrl);
        $gitDir = $repoPath.'/.git';
        mkdir($gitDir, 0777, true);

        file_put_contents($gitDir.'/config', <<<GIT
[core]
	repositoryformatversion = 0
[remote "origin"]
	url = {$originUrl}
	fetch = +refs/heads/*:refs/remotes/origin/*
GIT);

        return $repoPath;
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
