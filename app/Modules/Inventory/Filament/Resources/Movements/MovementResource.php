<?php

namespace App\Modules\Inventory\Filament\Resources\Movements;

use App\Models\User;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Movements\Pages\ListMovements;
use App\Modules\Inventory\Models\Movement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The stock history: what was added, eaten, thrown away or corrected, by whom
 * and when. Read only; entries come from the StockLedger.
 */
class MovementResource extends Resource
{
    protected static ?string $model = Movement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 50;

    public static function getModelLabel(): string
    {
        return __('Movement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('History');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forHousehold(auth()->user()->household_id)
            ->with(['product', 'user']);
    }

    /** e.g. "−2 × 500 g · Alex · for the soup": change, person, note. */
    public static function details(Movement $movement, bool $withProduct = false): string
    {
        return collect([
            $withProduct ? $movement->product->name : null,
            $movement->quantityLabel(),
            $movement->user?->name ?? __('Unknown'),
            $movement->note,
        ])->filter()->implode(' · ');
    }

    /** Type badge with the date and time underneath. */
    public static function typeColumn(): TextColumn
    {
        return TextColumn::make('type')
            ->label(__('What'))
            ->badge()
            ->description(fn (Movement $record): string => $record->created_at->locale(app()->getLocale())->isoFormat('L LT'))
            ->alignEnd();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->description(fn (Movement $record): string => static::details($record))
                    ->searchable(['name', 'brand', 'barcode']),
                static::typeColumn(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('created_at')->orderByDesc('id'))
            ->filters([
                SelectFilter::make('type')
                    ->label(__('What'))
                    ->options(MovementType::class),
                SelectFilter::make('user_id')
                    ->label(__('Person'))
                    ->options(fn (): array => User::query()->where('household_id', auth()->user()->household_id)->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('product_id')
                    ->label(__('Product'))
                    ->options(fn (): array => BatchResource::productOptions()),
            ])
            ->recordActions([])
            ->emptyStateHeading(__('Nothing has happened yet'))
            ->emptyStateDescription(__('Adding batches and taking packages out is recorded here, with who did it.'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMovements::route('/'),
        ];
    }
}
