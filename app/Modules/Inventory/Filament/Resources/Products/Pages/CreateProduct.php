<?php

namespace App\Modules\Inventory\Filament\Resources\Products\Pages;

use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Product;
use App\Support\Barcode;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** The product fields, plus its first batch (packages, best-before date, location). */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ...ProductResource::fields(),
            Section::make(__('Put into stock'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('add_batch')
                        ->label(__('Put into stock right away'))
                        ->helperText(__('Saves the packages and their best-before date together with the product.'))
                        ->live(),
                    Group::make(BatchResource::fields(withProduct: false))
                        ->statePath('batch')
                        ->visible(fn (Get $get): bool => (bool) $get('add_batch')),
                ]),
        ]);
    }

    /**
     * Coming from the scanner: ?barcode=<code> fills the barcode, looks it up
     * and offers to put the packages into stock.
     */
    protected function fillForm(): void
    {
        parent::fillForm();

        $barcode = Barcode::normalize((string) request()->query('barcode'));

        if ($barcode !== null) {
            $this->data['barcode'] = $barcode;
            $this->data['add_batch'] = true;
            $this->form->callAfterStateUpdated('data.barcode');
        }
    }

    /** household_id is not mass assignable; set it from the current person. */
    protected function handleRecordCreation(array $data): Model
    {
        $batch = ($data['add_batch'] ?? false) ? ($data['batch'] ?? null) : null;
        unset($data['add_batch'], $data['batch']);

        return DB::transaction(function () use ($data, $batch): Product {
            /** @var Product $product */
            $product = auth()->user()->household->hasMany(Product::class)->create($data);

            if ($batch !== null) {
                $product->batches()->create($batch);
            }

            return $product;
        });
    }

    /** On to the product page with its batches. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
