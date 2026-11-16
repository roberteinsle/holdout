<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Emergency copy: opens the printable page (route "printout", sections from the
 * PrintoutRegistry) and shows the offline copy on this device (public/sw.js, #14).
 */
class Printout extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'print';

    protected string $view = 'filament.pages.printout';

    public static function getNavigationLabel(): string
    {
        return __('Emergency copy');
    }

    public function getTitle(): string
    {
        return __('Emergency copy');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->openAction(),
        ];
    }

    public function openAction(): Action
    {
        return Action::make('open')
            ->label(__('Open printout'))
            ->icon(Heroicon::OutlinedPrinter)
            ->size('xl')
            ->url(route('printout'))
            ->openUrlInNewTab();
    }
}
