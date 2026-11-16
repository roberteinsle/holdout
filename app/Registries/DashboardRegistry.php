<?php

namespace App\Registries;

/**
 * Widgets on the dashboard. Modules (and core) register Filament widget classes
 * here in their service provider; the panel provider reads them. The order
 * comes from each widget's $sort.
 */
class DashboardRegistry
{
    /** @var array<class-string, string> widget class => module */
    private array $widgets = [];

    /** @param class-string $widget */
    public function widget(string $module, string $widget): void
    {
        $this->widgets[$widget] = $module;
    }

    /** @return list<class-string> */
    public function widgets(): array
    {
        return array_keys($this->widgets);
    }
}
