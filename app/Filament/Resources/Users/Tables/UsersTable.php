<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->description(fn ($record) => $record->email)
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('Role'))
                    ->badge(),
            ])
            ->defaultSort('name')
            ->paginated(false)
            ->recordActions([
                DeleteAction::make()->label(__('Remove')),
            ]);
    }
}
