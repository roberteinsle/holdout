<?php

namespace App\Registries;

/**
 * Sections of the printout (paper emergency copy): core and modules register
 * them here; the printout page shows them ordered by sort().
 */
class PrintoutRegistry
{
    /** @var array<int, array{module: string, section: PrintSection}> */
    private array $sections = [];

    public function section(string $module, PrintSection $section): void
    {
        $this->sections[] = ['module' => $module, 'section' => $section];
    }

    /** @return list<PrintSection> */
    public function sections(): array
    {
        $sections = array_column($this->sections, 'section');
        usort($sections, fn (PrintSection $a, PrintSection $b): int => $a->sort() <=> $b->sort());

        return $sections;
    }
}
