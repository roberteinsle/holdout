<?php

namespace App\Modules\Inventory\Printout;

use App\Models\Household;
use App\Modules\Inventory\Models\Batch;
use App\Registries\PrintSection;
use Illuminate\Contracts\Support\Renderable;

/** Printout: expired and soon-expiring batches, earliest first: what to eat first. */
class EatFirstSection implements PrintSection
{
    public function sort(): int
    {
        return 20;
    }

    public function render(Household $household): ?Renderable
    {
        $batches = Batch::query()
            ->forHousehold($household->id)
            ->with(['product', 'location'])
            ->whereNotNull('best_before')
            ->whereDate('best_before', '<=', today()->addDays($household->eat_soon_days)->toDateString())
            ->orderByBestBefore()
            ->get();

        return view('inventory::printout.eat-first', [
            'batches' => $batches,
            'days' => $household->eat_soon_days,
        ]);
    }
}
