<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Printout: explains the paper emergency copy and opens the printable page
 * (route "printout", sections from the PrintoutRegistry).
 */
class Printout extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'print';

    protected string $view = 'filament.pages.printout';

    public static function getNavigationLabel(): string
    {
        return __('Printout');
    }

    public function getTitle(): string
    {
        return __('Printout');
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
