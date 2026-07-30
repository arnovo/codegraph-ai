<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Agent\Services\AgentProfileCatalog;
use Tests\TestCase;

final class AgentProfileCatalogTest extends TestCase
{
    public function test_it_lists_profiles_for_frontend(): void
    {
        $profiles = (new AgentProfileCatalog)->listForFrontend();

        $this->assertNotEmpty($profiles);
        $this->assertSame('developer', $profiles[0]['slug']);
        $this->assertTrue($profiles[0]['is_default']);
    }

    public function test_it_falls_back_to_default_profile_for_unknown_slug(): void
    {
        $profile = (new AgentProfileCatalog)->resolve('unknown-profile');

        $this->assertSame('developer', $profile['slug']);
        $this->assertSame('Desarrollo', $profile['label']);
    }
}
