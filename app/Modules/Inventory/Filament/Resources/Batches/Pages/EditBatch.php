<?php

namespace App\Modules\Inventory\Filament\Resources\Batches\Pages;

use App\Modules\Inventory\Filament\Actions\TakeOutAction;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Models\Batch;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBatch extends EditRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // An emptied batch is gone: back to the list; otherwise show the new amount.
            TakeOutAction::make()->after(function (Batch $record): void {
                if (! Batch::query()->whereKey($record->getKey())->exists()) {
                    $this->redirect(static::getResource()::getUrl('index'));

                    return;
                }

                $this->record->refresh();
                $this->refreshFormData(['quantity']);
            }),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
