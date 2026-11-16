<?php

namespace App\Modules\Inventory\Filament\Resources\Products\Pages;

use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** household_id is not mass assignable; set it from the current person. */
    protected function handleRecordCreation(array $data): Model
    {
        return auth()->user()->household->hasMany(static::getModel())->create($data);
    }

    /** On to the product page, where its batches are added. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
