<?php

namespace App\Modules\Equipment\Filament\Resources\Equipment\Pages;

use App\Modules\Equipment\Filament\Resources\Equipment\EquipmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipment extends EditRecord
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EquipmentResource::checkAction()->record($this->getRecord())
                ->after(fn () => $this->refreshFormData(['available', 'expires_on'])),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
