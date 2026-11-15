<?php

namespace App\Registries;

/**
 * A supply category as a module registers it: how much one person needs per day.
 * Names are English source strings, translated with __() when shown.
 */
final readonly class CategoryDefinition
{
    public function __construct(
        public string $key,
        public string $name,
        public string $unit,
        public float $perAdultDay,
        public ?float $perChildDay = null,
        public bool $isWater = false,
        public int $sort = 100,
        public ?string $source = null,
    ) {
        if (! in_array($unit, ['kg', 'l'], true)) {
            throw new \InvalidArgumentException("Unit must be kg or l, got {$unit}.");
        }
    }

    /** Children count like adults unless the module gives a separate value. */
    public function perChild(): float
    {
        return $this->perChildDay ?? $this->perAdultDay;
    }
}
