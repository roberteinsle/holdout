<?php

namespace App\Modules\Equipment\Filament\Resources\Equipment\Pages;

use App\Modules\Equipment\Enums\EquipmentArea;
use App\Modules\Equipment\Filament\Resources\Equipment\EquipmentResource;
use App\Modules\Equipment\Support\Suggestions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListEquipment extends ListRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('Add item')),
            Action::make('suggestions')
                ->label(__('Add suggestions'))
                ->icon(Heroicon::OutlinedListBullet)
                ->color('gray')
                ->modalHeading(__('Add suggested items'))
                ->modalDescription(__('Items from the BBK checklist are added as missing; tick them off once you have them. Items you already added are skipped.'))
                ->modalSubmitActionLabel(__('Add'))
                ->schema([
                    CheckboxList::make('areas')
                        ->label(__('Areas'))
                        ->options(EquipmentArea::class)
                        ->default(array_map(fn (EquipmentArea $area): string => $area->value, EquipmentArea::cases()))
                        ->required()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data): void {
                    $areas = array_map(
                        fn (EquipmentArea|string $area): EquipmentArea => $area instanceof EquipmentArea ? $area : EquipmentArea::from($area),
                        $data['areas'],
                    );
                    $added = Suggestions::addTo(auth()->user()->household, $areas);

                    Notification::make()
                        ->title(trans_choice(':count item added.|:count items added.', $added, ['count' => $added]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
