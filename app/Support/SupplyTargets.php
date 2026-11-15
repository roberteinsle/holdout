<?php

namespace App\Support;

use App\Enums\MemberType;
use App\Models\Household;
use App\Registries\CategoryDefinition;
use App\Registries\CategoryRegistry;
use Illuminate\Support\Collection;

/**
 * Target amount per category: (adults × need per adult + children × need per
 * child) × target days. Reads the categories from the CategoryRegistry only.
 */
class SupplyTargets
{
    public function __construct(private CategoryRegistry $categories) {}

    /** @return Collection<string, SupplyTarget> keyed by category key, in display order */
    public function for(Household $household, ?int $days = null): Collection
    {
        $adults = $household->memberCount(MemberType::Adult);
        $children = $household->memberCount(MemberType::Child);
        $days ??= $household->target_days;

        return $this->categories->all()->map(function (CategoryDefinition $category) use ($adults, $children, $days): SupplyTarget {
            $perDay = $adults * $category->perAdultDay + $children * $category->perChild();

            return new SupplyTarget($category, $perDay, $perDay * $days);
        });
    }
}
