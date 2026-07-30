<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Projects\Services\BitbucketRepositoryUrlParser;
use InvalidArgumentException;
use Tests\TestCase;

final class BitbucketRepositoryUrlParserTest extends TestCase
{
    private BitbucketRepositoryUrlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BitbucketRepositoryUrlParser;
    }

    public function test_it_normalizes_https_bitbucket_url(): void
    {
        $normalized = $this->parser->normalizeHttpsUrl(
            'https://bitbucket.org/prinex/demo-repo.git',
        );

        $this->assertSame('https://bitbucket.org/prinex/demo-repo.git', $normalized);
    }

    public function test_it_builds_authenticated_clone_url(): void
    {
        $url = $this->parser->buildAuthenticatedCloneUrl(
            normalizedGitUrl: 'https://bitbucket.org/prinex/demo-repo.git',
            username: 'dev.user',
            apiToken: 'secret/token',
        );

        $this->assertSame(
            'https://dev.user:secret%2Ftoken@bitbucket.org/prinex/demo-repo.git',
            $url,
        );
    }

    public function test_it_rejects_ssh_urls(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->normalizeHttpsUrl('git@bitbucket.org:prinex/demo-repo.git');
    }

    public function test_it_rejects_non_bitbucket_hosts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->normalizeHttpsUrl('https://github.com/prinex/demo-repo.git');
    }
}
