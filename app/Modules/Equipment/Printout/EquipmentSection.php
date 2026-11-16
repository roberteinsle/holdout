<?php

namespace App\Modules\Equipment\Printout;

use App\Models\Household;
use App\Modules\Equipment\Models\EquipmentItem;
use App\Registries\PrintSection;
use Illuminate\Contracts\Support\Renderable;

/**
 * Printout: the equipment checklist by area, with a box per item (ticked when
 * available), status, expiry and last check, and space for a manual check mark.
 */
class EquipmentSection implements PrintSection
{
    public function sort(): int
    {
        return 40;
    }

    public function render(Household $household): ?Renderable
    {
        $items = EquipmentItem::query()
            ->where('household_id', $household->id)
            ->get()
            ->sortBy([
                fn (EquipmentItem $a, EquipmentItem $b): int => $a->area->sort() <=> $b->area->sort(),
                fn (EquipmentItem $a, EquipmentItem $b): int => strnatcasecmp($a->name, $b->name),
            ]);

        if ($items->isEmpty()) {
            return null;
        }

        return view('equipment::printout.equipment', [
            'groups' => $items->groupBy(fn (EquipmentItem $item): string => $item->area->value),
        ]);
    }
}
