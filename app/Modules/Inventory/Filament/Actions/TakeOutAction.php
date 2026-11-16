<?php

namespace App\Modules\Inventory\Filament\Actions;

use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Support\NotEnoughStock;
use App\Modules\Inventory\Support\StockLedger;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * "Take out": packages leave a batch because they were eaten or thrown away.
 * Used in the batch list, on the batch page and on the product page.
 */
class TakeOutAction
{
    public static function make(string $name = 'takeOut'): Action
    {
        return Action::make($name)
            ->label(__('Take out'))
            ->icon(Heroicon::OutlinedMinusCircle)
            ->color('primary')
            ->authorize(fn (Batch $record): bool => auth()->user()->can('update', $record))
            ->modalHeading(fn (Batch $record): string => __('Take out: :product', ['product' => $record->product->name]))
            ->modalDescription(fn (Batch $record): string => __(':count packages left.', ['count' => $record->quantity]))
            ->modalSubmitActionLabel(__('Take out'))
            ->schema(fn (Batch $record): array => [
                TextInput::make('quantity')
                    ->label(__('Packages'))
                    ->integer()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue($record->quantity),
                ToggleButtons::make('type')
                    ->label(__('Reason'))
                    ->options(collect(MovementType::takeOutReasons())->mapWithKeys(fn (MovementType $type): array => [$type->value => $type->getLabel()])->all())
                    ->icons(collect(MovementType::takeOutReasons())->mapWithKeys(fn (MovementType $type): array => [$type->value => $type->getIcon()])->all())
                    ->colors(collect(MovementType::takeOutReasons())->mapWithKeys(fn (MovementType $type): array => [$type->value => $type->getColor()])->all())
                    ->default(MovementType::Consumed->value)
                    ->required()
                    ->inline()
                    ->in(collect(MovementType::takeOutReasons())->map->value->all()),
                TextInput::make('note')
                    ->label(__('Note'))
                    ->maxLength(150),
            ])
            ->action(function (Batch $record, array $data, Action $action): void {
                try {
                    app(StockLedger::class)->takeOut($record, (int) $data['quantity'], MovementType::from($data['type']), $data['note'] ?? null);
                } catch (NotEnoughStock $e) {
                    Notification::make()
                        ->title(__('Only :count packages left.', ['count' => $e->left]))
                        ->body(__('Someone else may have taken some meanwhile.'))
                        ->danger()
                        ->send();
                    $action->halt();
                }

                Notification::make()
                    ->title(__('Taken out: :count × :product', ['count' => $data['quantity'], 'product' => $record->product->name]))
                    ->success()
                    ->send();
            });
    }
}
