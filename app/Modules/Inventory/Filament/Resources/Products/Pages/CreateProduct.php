<?php

namespace App\Modules\Inventory\Filament\Resources\Products\Pages;

use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Support\Barcode;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** Coming from the scanner: ?barcode=<code> fills the barcode and looks it up. */
    protected function fillForm(): void
    {
        parent::fillForm();

        $barcode = Barcode::normalize((string) request()->query('barcode'));

        if ($barcode !== null) {
            $this->data['barcode'] = $barcode;
            $this->form->callAfterStateUpdated('data.barcode');
        }
    }

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
