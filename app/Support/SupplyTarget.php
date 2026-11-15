<?php

namespace App\Support;

use App\Registries\CategoryDefinition;

/**
 * What a household needs of one category: per day and for its target days.
 */
final readonly class SupplyTarget
{
    public function __construct(
        public CategoryDefinition $category,
        public float $perDay,
        public float $total,
    ) {}
}
