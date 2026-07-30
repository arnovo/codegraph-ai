<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTO;

final readonly class LlmModelProfileData
{
    public function __construct(
        public string $id,
        public string $model,
        public ?string $label,
        public int $sortOrder,
        public bool $enabled,
        public bool $useEnvCredentials = false,
        public ?string $baseUrl = null,
        public ?string $apiKeyPreview = null,
        public bool $apiKeySet = false,
    ) {}

    /** @param  array<string, mixed>  $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            model: (string) $row['model'],
            label: isset($row['label']) ? (string) $row['label'] : null,
            sortOrder: (int) ($row['sort_order'] ?? $row['sortOrder'] ?? 0),
            enabled: (bool) ($row['enabled'] ?? true),
            useEnvCredentials: (bool) ($row['use_env_credentials'] ?? $row['useEnvCredentials'] ?? false),
            baseUrl: isset($row['base_url']) ? (string) $row['base_url'] : null,
            apiKeyPreview: isset($row['api_key_preview']) ? (string) $row['api_key_preview'] : null,
            apiKeySet: (bool) ($row['api_key_set'] ?? $row['apiKeySet'] ?? false),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model' => $this->model,
            'label' => $this->label,
            'sort_order' => $this->sortOrder,
            'enabled' => $this->enabled,
            'use_env_credentials' => $this->useEnvCredentials,
            'base_url' => $this->baseUrl,
            'api_key_preview' => $this->apiKeyPreview,
            'api_key_set' => $this->apiKeySet,
        ];
    }
}
