<?php

namespace App\Modules\Inventory\Filament\Resources\Movements\Pages;

use App\Modules\Inventory\Filament\Resources\Movements\MovementResource;
use Filament\Resources\Pages\ListRecords;

class ListMovements extends ListRecords
{
    protected static string $resource = MovementResource::class;
}
