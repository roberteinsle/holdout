<?php

namespace App\Modules\Inventory\Filament\Resources\Products\RelationManagers;

use App\Modules\Inventory\Filament\Resources\Movements\MovementResource;
use App\Modules\Inventory\Models\Movement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** The product's history on its page, read only. */
class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('History');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quantity')
                    ->label(__('Packages'))
                    ->formatStateUsing(fn (Movement $record): string => $record->quantityLabel())
                    ->description(fn (Movement $record): string => collect([$record->user?->name ?? __('Unknown'), $record->note])->filter()->implode(' · ')),
                MovementResource::typeColumn(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product', 'user']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('created_at')->orderByDesc('id'))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('Nothing has happened yet'));
    }
}
