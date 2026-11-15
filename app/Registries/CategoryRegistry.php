<?php

namespace App\Registries;

use Illuminate\Support\Collection;

/**
 * Modules register their supply categories here (in their service provider).
 * Core features such as the target calculation and the dashboard only read
 * from this registry and never access a module directly.
 */
class CategoryRegistry
{
    /** @var array<string, array{definition: CategoryDefinition, module: string}> */
    private array $categories = [];

    public function register(string $module, CategoryDefinition $definition): void
    {
        if (isset($this->categories[$definition->key])) {
            throw new \LogicException("Category [{$definition->key}] is already registered by module [{$this->categories[$definition->key]['module']}].");
        }

        $this->categories[$definition->key] = ['definition' => $definition, 'module' => $module];
    }

    /** @return Collection<string, CategoryDefinition> keyed by category key, in display order */
    public function all(): Collection
    {
        return collect($this->categories)
            ->map(fn (array $entry): CategoryDefinition => $entry['definition'])
            ->sortBy(fn (CategoryDefinition $category): array => [$category->sort, $category->key]);
    }

    public function get(string $key): ?CategoryDefinition
    {
        return $this->categories[$key]['definition'] ?? null;
    }

    public function moduleOf(string $key): ?string
    {
        return $this->categories[$key]['module'] ?? null;
    }
}
