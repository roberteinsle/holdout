<?php

namespace App\Modules\Equipment\Filament\Resources\Equipment\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** The item's check log, newest first. Read-only: checks are added with "Check". */
class ChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'checks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Check log');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('checked_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->description(fn ($record): string => $record->user?->name ?? __('Former member')),
                TextColumn::make('result')
                    ->label(__('Result'))
                    ->badge()
                    ->description(fn ($record): ?string => $record->note),
            ])
            ->defaultSort('checked_at', 'desc')
            ->emptyStateHeading(__('Not checked yet'));
    }
}
