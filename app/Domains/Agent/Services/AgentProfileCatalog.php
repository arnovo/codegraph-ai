<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

final class AgentProfileCatalog
{
    /**
     * @return list<array{slug: string, label: string, description: string, is_default: bool}>
     */
    public function listForFrontend(): array
    {
        $defaultSlug = $this->defaultSlug();
        $profiles = [];

        foreach ($this->profiles() as $slug => $profile) {
            $profiles[] = [
                'slug' => $slug,
                'label' => (string) $profile['label'],
                'description' => (string) $profile['description'],
                'is_default' => $slug === $defaultSlug,
            ];
        }

        return $profiles;
    }

    /**
     * @return array{slug: string, label: string, description: string, persona: string, style: string}
     */
    public function resolve(?string $slug): array
    {
        $profiles = $this->profiles();
        $resolvedSlug = $slug !== null && isset($profiles[$slug]) ? $slug : $this->defaultSlug();

        return [
            'slug' => $resolvedSlug,
            'label' => (string) $profiles[$resolvedSlug]['label'],
            'description' => (string) $profiles[$resolvedSlug]['description'],
            'persona' => (string) $profiles[$resolvedSlug]['persona'],
            'style' => (string) $profiles[$resolvedSlug]['style'],
        ];
    }

    public function defaultSlug(): string
    {
        $default = (string) config('agent_profiles.default', 'developer');
        $profiles = $this->profiles();

        if (isset($profiles[$default])) {
            return $default;
        }

        $first = array_key_first($profiles);

        return is_string($first) ? $first : 'developer';
    }

    /** @return array<string, array<string, string>> */
    private function profiles(): array
    {
        /** @var array<string, array<string, string>> $profiles */
        $profiles = config('agent_profiles.profiles', []);

        return $profiles;
    }
}
