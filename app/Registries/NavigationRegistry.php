<?php

namespace App\Registries;

/**
 * Modules register their Filament resources and pages here (in their service
 * provider); the panel provider reads them. Core never names a module's pages.
 */
class NavigationRegistry
{
    /** @var array<class-string, string> resource class => module */
    private array $resources = [];

    /** @var array<class-string, string> page class => module */
    private array $pages = [];

    /** @param class-string $resource */
    public function resource(string $module, string $resource): void
    {
        $this->resources[$resource] = $module;
    }

    /** @param class-string $page */
    public function page(string $module, string $page): void
    {
        $this->pages[$page] = $module;
    }

    /** @return list<class-string> */
    public function resources(): array
    {
        return array_keys($this->resources);
    }

    /** @return list<class-string> */
    public function pages(): array
    {
        return array_keys($this->pages);
    }
}
