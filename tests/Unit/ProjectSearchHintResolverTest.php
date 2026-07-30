<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Agent\Services\ProjectSearchHintResolver;
use Tests\TestCase;

final class ProjectSearchHintResolverTest extends TestCase
{
    public function test_it_suggests_mobile_terms_for_react_native(): void
    {
        $resolver = new ProjectSearchHintResolver;

        $hint = $resolver->resolve('React Native');

        $this->assertStringContainsString('navigation', $hint);
        $this->assertStringContainsString('controller', $hint);
    }

    public function test_it_suggests_php_backend_terms_for_laravel(): void
    {
        $resolver = new ProjectSearchHintResolver;

        $hint = $resolver->resolve('Laravel');

        $this->assertStringContainsString('Controller', $hint);
    }

    public function test_it_suggests_reformulation_when_stack_unknown(): void
    {
        $resolver = new ProjectSearchHintResolver;

        $hint = $resolver->resolve('—');

        $this->assertStringContainsString('total=0', $hint);
    }
}
