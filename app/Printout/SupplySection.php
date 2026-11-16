<?php

namespace App\Printout;

use App\Enums\MemberType;
use App\Models\Household;
use App\Registries\PrintSection;
use App\Support\SupplyStatus;
use Illuminate\Contracts\Support\Renderable;

/** Printout: how long the supply lasts and how much the household needs per day (rations). */
class SupplySection implements PrintSection
{
    public function __construct(private SupplyStatus $status) {}

    public function sort(): int
    {
        return 10;
    }

    public function render(Household $household): ?Renderable
    {
        $household->loadMissing('members');
        $status = $this->status->for($household);

        return view('printout.supply', [
            'status' => $status,
            'daysOfSupply' => SupplyStatus::daysOfSupply($status),
            'targetDays' => $household->target_days,
            'adults' => $household->memberCount(MemberType::Adult),
            'children' => $household->memberCount(MemberType::Child),
        ]);
    }
}
