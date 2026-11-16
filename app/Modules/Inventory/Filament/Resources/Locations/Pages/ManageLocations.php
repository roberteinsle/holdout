<?php

namespace App\Modules\Inventory\Filament\Resources\Locations\Pages;

use App\Modules\Inventory\Filament\Resources\Locations\LocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageLocations extends ManageRecords
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add location'))
                ->using(fn (array $data): Model => LocationResource::createForHousehold($data)),
        ];
    }
}
