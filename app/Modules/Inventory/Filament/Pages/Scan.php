<?php

namespace App\Modules\Inventory\Filament\Pages;

use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Product;
use App\Support\Barcode;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Scan a barcode with the camera (or type it): a known product goes straight
 * to "add batch", an unknown one to "add product" with the barcode filled in.
 */
class Scan extends Page
{
    protected string $view = 'inventory::scan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'scan';

    public string $code = '';

    public static function getNavigationLabel(): string
    {
        return __('Scan');
    }

    public function getTitle(): string
    {
        return __('Scan');
    }

    /** Called by the camera with each recognised code, and by the manual form. */
    public function scanned(?string $code = null): void
    {
        $barcode = Barcode::normalize($code ?? $this->code);

        if ($barcode === null) {
            Notification::make()->title(__('That is not a valid barcode (8 to 14 digits).'))->danger()->send();

            return;
        }

        $product = Product::query()
            ->where('household_id', auth()->user()->household_id)
            ->where('barcode', $barcode)
            ->first();

        if ($product !== null) {
            $this->redirect(BatchResource::getUrl('create', ['product' => $product->getKey()]));

            return;
        }

        $this->redirect(ProductResource::getUrl('create', ['barcode' => $barcode]));
    }
}
