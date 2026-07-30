<?php

declare(strict_types=1);

namespace App\Domains\Mcp\DTO;

final readonly class McpProjectData
{
    public function __construct(
        public string $name,
        public string $rootPath,
        public int $nodes,
        public int $edges,
        public int $sizeBytes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            rootPath: (string) ($data['root_path'] ?? $data['rootPath'] ?? ''),
            nodes: (int) ($data['nodes'] ?? 0),
            edges: (int) ($data['edges'] ?? 0),
            sizeBytes: (int) ($data['size_bytes'] ?? $data['sizeBytes'] ?? 0),
        );
    }

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
        ];
    }
}
