<?php

declare(strict_types=1);

namespace App\Domains\Projects\DTO;

final readonly class ProjectSummaryData
{
    public function __construct(
        public string $name,
        public string $rootPath,
        public int $nodes,
        public int $edges,
        public int $sizeBytes,
        public string $displayName = '',
        public string $primaryStack = '—',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'root_path' => $this->rootPath,
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'size_bytes' => $this->sizeBytes,
            'display_name' => $this->displayName !== '' ? $this->displayName : $this->name,
            'primary_stack' => $this->primaryStack,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $name = (string) ($row['name'] ?? '');
        $rootPath = (string) ($row['root_path'] ?? $row['rootPath'] ?? '');

        return new self(
            name: $name,
            rootPath: $rootPath,
            nodes: (int) ($row['nodes'] ?? 0),
            edges: (int) ($row['edges'] ?? 0),
            sizeBytes: (int) ($row['size_bytes'] ?? $row['sizeBytes'] ?? 0),
            displayName: (string) ($row['display_name'] ?? $row['displayName'] ?? basename(rtrim($rootPath, '/')) ?: $name),
            primaryStack: (string) ($row['primary_stack'] ?? $row['primaryStack'] ?? '—'),
        );
    }

    public function withStack(string $primaryStack): self
    {
        return new self(
            name: $this->name,
            rootPath: $this->rootPath,
            nodes: $this->nodes,
            edges: $this->edges,
            sizeBytes: $this->sizeBytes,
            displayName: $this->displayName,
            primaryStack: $primaryStack,
        );
    }
}
