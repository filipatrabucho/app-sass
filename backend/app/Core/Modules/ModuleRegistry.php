<?php

namespace App\Core\Modules;

use Illuminate\Support\Collection;

/**
 * Descobre os módulos instalados em Modules/{Nome}/module.json.
 */
class ModuleRegistry
{
    /** @var Collection<string, ModuleManifest>|null */
    private ?Collection $modules = null;

    public function __construct(private readonly string $modulesPath) {}

    /**
     * @return Collection<string, ModuleManifest>
     */
    public function all(): Collection
    {
        return $this->modules ??= collect(glob($this->modulesPath.'/*/module.json') ?: [])
            ->map(fn (string $file) => ModuleManifest::fromFile($file))
            ->keyBy('key')
            ->sortKeys();
    }

    public function find(string $key): ?ModuleManifest
    {
        return $this->all()->get($key);
    }

    public function has(string $key): bool
    {
        return $this->all()->has($key);
    }
}
