<?php

namespace App\Modules\Inventory\Filament\Resources\Products\RelationManagers;

use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Models\Batch;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** The product's batches on its edit page; add, edit and delete in a dialog. */
class BatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Batches');
    }

    protected static function getModelLabel(): ?string
    {
        return __('Batch');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __('Batches');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(BatchResource::fields(withProduct: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quantity')
                    ->label(__('Packages'))
                    ->formatStateUsing(fn (Batch $record): string => $record->quantityLabel())
                    ->description(fn (Batch $record): ?string => $record->note),
                BatchResource::bestBeforeColumn(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByBestBefore())
            ->headerActions([
                CreateAction::make()->label(__('Add batch')),
            ])
            // Tap a row to edit; the icons edit or delete.
            ->recordAction('edit')
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->emptyStateHeading(__('No batches yet'))
            ->emptyStateDescription(__('Add how many packages you have and their best-before date.'));
    }
}
