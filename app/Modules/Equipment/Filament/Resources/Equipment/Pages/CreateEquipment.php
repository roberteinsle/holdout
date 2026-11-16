<?php

namespace App\Modules\Equipment\Filament\Resources\Equipment\Pages;

use App\Modules\Equipment\Filament\Resources\Equipment\EquipmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEquipment extends CreateRecord
{
    protected static string $resource = EquipmentResource::class;

    /** household_id is not mass assignable; set it from the signed-in person. */
    protected function handleRecordCreation(array $data): Model
    {
        return auth()->user()->household->hasMany(static::getModel())->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
