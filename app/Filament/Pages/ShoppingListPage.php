<?php

namespace App\Filament\Pages;

use App\Models\Household;
use App\Support\ShoppingList;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Shopping list: what is missing per category to reach the household's target
 * days (ShoppingList, from SupplyStatus), with a text to share or copy.
 */
class ShoppingListPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'shopping-list';

    protected string $view = 'filament.pages.shopping-list';

    public static function getNavigationLabel(): string
    {
        return __('Shopping list');
    }

    public function getTitle(): string
    {
        return __('Shopping list');
    }

    /** Number of categories with something missing. */
    public static function getNavigationBadge(): ?string
    {
        $household = auth()->user()?->household;
        $count = $household ? app(ShoppingList::class)->for($household->load('members'))->count() : 0;

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    protected function getViewData(): array
    {
        /** @var Household $household */
        $household = auth()->user()->household->load('members');
        $items = app(ShoppingList::class)->for($household);

        return [
            'items' => $items,
            'targetDays' => $household->target_days,
            'people' => $household->memberTotal(),
            'text' => ShoppingList::text($items, $household->target_days),
            'householdUrl' => HouseholdSettings::getUrl(),
        ];
    }
}
