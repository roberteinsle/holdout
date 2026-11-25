<?php

namespace App\Modules\Inventory\Filament\Pages;

use App\Modules\Inventory\Filament\Actions\TakeOutAction;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Product;
use App\Support\Barcode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

/**
 * Scan a barcode with the camera (or type it). A product in stock is shown with
 * its batches: take packages out or add a batch. A known product without stock
 * goes straight to "add batch", an unknown one to "add product" with the
 * barcode filled in.
 */
class Scan extends Page
{
    protected string $view = 'inventory::scan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'scan';

    public string $code = '';

    /** The scanned product in stock; null while scanning. */
    #[Locked]
    public ?int $productId = null;

    /** @var array<int, ?Batch> */
    private array $takeFrom = [];

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

        $product = $this->products()->where('barcode', $barcode)->first();

        if ($product === null) {
            $this->redirect(ProductResource::getUrl('create', ['barcode' => $barcode]));

            return;
        }

        // Nothing to take out: straight to adding.
        if (! $product->batches()->exists()) {
            $this->redirect(BatchResource::getUrl('create', ['product' => $product->getKey()]));

            return;
        }

        $this->productId = $product->getKey();
        $this->code = '';
    }

    public function scanNext(): void
    {
        $this->productId = null;
        $this->code = '';
    }

    /** "Take out" for one batch of the scanned product (argument "batch"). */
    public function takeOutAction(): Action
    {
        return TakeOutAction::make()
            ->record(fn (array $arguments): Batch => $this->batchToTakeFrom((int) ($arguments['batch'] ?? 0)));
    }

    /**
     * Kept for the request, because an emptied batch is deleted while its action
     * still runs. A batch that is gone (someone else emptied it) or belongs to
     * another product closes the dialog with a message.
     */
    private function batchToTakeFrom(int $id): Batch
    {
        if (! array_key_exists($id, $this->takeFrom)) {
            $this->takeFrom[$id] = $this->batches()?->with('product')->find($id);

            if ($this->takeFrom[$id] === null) {
                Notification::make()
                    ->title(__('This batch is no longer in stock.'))
                    ->body(__('Someone else may have taken some meanwhile.'))
                    ->danger()
                    ->send();
            }
        }

        return $this->takeFrom[$id] ?? throw new ActionNotResolvableException('The batch is gone.');
    }

    protected function getViewData(): array
    {
        $product = $this->productId === null ? null : $this->products()->with('category')->find($this->productId);

        return [
            'product' => $product,
            'batches' => $product === null ? collect() : $this->batches()->with(['product', 'location'])->orderByBestBefore()->orderBy('id')->get(),
            'eatSoonDays' => BatchResource::eatSoonDays(),
            'addUrl' => $product === null ? null : BatchResource::getUrl('create', ['product' => $product->getKey()]),
            'productUrl' => $product === null ? null : ProductResource::getUrl('edit', ['record' => $product]),
        ];
    }

    /** @return Builder<Product> the current household's products */
    private function products(): Builder
    {
        return Product::query()->where('household_id', auth()->user()->household_id);
    }

    /** @return ?Builder<Batch> the scanned product's batches, own household only */
    private function batches(): ?Builder
    {
        return $this->productId === null ? null : Batch::query()
            ->forHousehold(auth()->user()->household_id)
            ->where('product_id', $this->productId);
    }
}
