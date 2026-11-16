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
        if ($perAdultDay < 0 || ($perChildDay !== null && $perChildDay < 0)) {
            throw new \InvalidArgumentException('The daily need cannot be negative.');
        }

        if (! in_array($unit, ['kg', 'l'], true)) {
            throw new \InvalidArgumentException("Unit must be kg or l, got {$unit}.");
        }
    }

    /**
     * Categories without a daily need (e.g. "Other") hold products but have no
     * target; they never count towards targets or days of supply.
     */
    public function hasTarget(): bool
    {
        return $this->perAdultDay > 0 || $this->perChild() > 0;
    }

    /** Children count like adults unless the module gives a separate value. */
    public function perChild(): float
    {
        return $this->perChildDay ?? $this->perAdultDay;
    }
}
