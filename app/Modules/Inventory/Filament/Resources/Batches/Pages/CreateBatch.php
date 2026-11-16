<?php

namespace App\Modules\Inventory\Filament\Resources\Batches\Pages;

use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
