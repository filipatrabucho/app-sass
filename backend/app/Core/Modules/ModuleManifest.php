<?php

namespace App\Core\Modules;

/**
 * Representação de um module.json.
 */
final readonly class ModuleManifest
{
    /**
     * @param  list<string>  $requires
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $version,
        public string $path,
        public ?string $provider = null,
        public ?string $seeder = null,
        public ?string $description = null,
        public array $requires = [],
    ) {}

    public static function fromFile(string $file): self
    {
        $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

        return new self(
            key: $data['key'],
            name: $data['name'],
            version: $data['version'],
            path: dirname($file),
            provider: $data['provider'] ?? null,
            seeder: $data['seeder'] ?? null,
            description: $data['description'] ?? null,
            requires: array_values($data['requires'] ?? []),
        );
    }
}
