<?php

namespace App\Modules\Inventory\Filament\Resources\Batches\Pages;

use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    /** Coming from the scanner: ?product=<id> preselects the product (own household only). */
    protected function fillForm(): void
    {
        parent::fillForm();

        $productId = (int) request()->query('product');

        if ($productId > 0 && array_key_exists($productId, BatchResource::productOptions())) {
            $this->data['product_id'] = $productId;
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
