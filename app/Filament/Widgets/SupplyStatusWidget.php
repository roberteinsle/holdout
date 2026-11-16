<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HouseholdSettings;
use App\Filament\Pages\ShoppingListPage;
use App\Support\StockLevel;
use App\Support\SupplyStatus;
use Filament\Widgets\Widget;

/**
 * Dashboard: how many days the supply lasts and, per category, how far the
 * stock covers the target. Details are on the Household page.
 */
class SupplyStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.supply-status';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $household = auth()->user()->household->load('members');
        $status = app(SupplyStatus::class)->for($household);
        $days = SupplyStatus::daysOfSupply($status);

        return [
            'status' => $status,
            'daysOfSupply' => $days,
            'target' => $household->target_days,
            'overall' => StockLevel::for($days, $household->target_days),
            'limitedBy' => SupplyStatus::limitedBy($status),
            'people' => $household->memberTotal(),
            'detailsUrl' => HouseholdSettings::getUrl(),
            'shoppingUrl' => ShoppingListPage::getUrl(),
        ];
    }
}
