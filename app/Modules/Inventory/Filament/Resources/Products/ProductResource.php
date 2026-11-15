<?php

namespace App\Modules\Inventory\Filament\Resources\Products;

use App\Models\Category;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\ListProducts;
use App\Modules\Inventory\Models\Product;
use App\Registries\ProductLookupRegistry;
use App\Registries\ProductLookupUnavailable;
use App\Support\Barcode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

/**
 * The household's products: name, brand, category and package size.
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Products');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('household_id', auth()->user()->household_id)
            ->with('category');
    }

    /** @return array<int, string> category id => translated name, in display order */
    public static function categoryOptions(): array
    {
        return Category::query()->orderBy('sort')->get()
            ->mapWithKeys(fn (Category $category): array => [$category->id => $category->label()])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        // Barcode first: it fills the other fields from the product database.
        return $schema->components([
            TextInput::make('barcode')
                ->label(__('Barcode'))
                ->helperText(__('Optional. The number under the barcode (8 to 14 digits). Name, brand and size are looked up automatically.'))
                ->inputMode('numeric')
                ->regex('/^\d{8,14}$/')
                ->maxLength(14)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => static::lookUpBarcode($state, $get, $set, overwrite: false))
                ->suffixAction(
                    Action::make('lookUp')
                        ->label(__('Look up'))
                        ->icon(Heroicon::MagnifyingGlass)
                        ->action(fn (Get $get, Set $set) => static::lookUpBarcode($get('barcode'), $get, $set, overwrite: true)),
                )
                ->dehydrateStateUsing(fn (?string $state): ?string => Barcode::normalize($state))
                ->unique(
                    table: Product::class,
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('household_id', auth()->user()->household_id),
                ),
            TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(150),
            TextInput::make('brand')
                ->label(__('Brand'))
                ->maxLength(100),
            Select::make('category_id')
                ->label(__('Category'))
                ->options(fn (): array => static::categoryOptions())
                ->required()
                ->in(fn (): array => array_keys(static::categoryOptions())),
            Grid::make(2)->schema([
                TextInput::make('package_value')
                    ->label(__('Package size'))
                    ->numeric()
                    ->required()
                    ->minValue(0.001)
                    ->maxValue(100000),
                Select::make('package_unit')
                    ->label(__('Unit'))
                    ->options(PackageUnit::class)
                    ->default(PackageUnit::Gram)
                    ->required()
                    ->selectablePlaceholder(false),
            ]),
        ]);
    }

    /**
     * Fill the form from a product data source (via the ProductLookupRegistry).
     * After leaving the field only empty fields are filled; the button overwrites.
     */
    public static function lookUpBarcode(?string $barcode, Get $get, Set $set, bool $overwrite): void
    {
        $normalized = Barcode::normalize($barcode);

        if ($normalized === null) {
            return;
        }

        $set('barcode', $normalized);

        $registry = app(ProductLookupRegistry::class);

        if (! $registry->hasSources() || (! $overwrite && filled($get('name')))) {
            return;
        }

        try {
            $info = $registry->lookup($normalized, app()->getLocale());
        } catch (ProductLookupUnavailable) {
            Notification::make()
                ->title(__('The product database cannot be reached right now.'))
                ->body(__('Enter the details by hand; you can look the barcode up again later.'))
                ->warning()
                ->send();

            return;
        }

        if ($info === null) {
            if ($overwrite) {
                Notification::make()->title(__('No product found for this barcode.'))->body(__('Enter the details by hand.'))->warning()->send();
            }

            return;
        }

        $fill = fn (string $field, mixed $value) => ($value !== null && ($overwrite || blank($get($field)))) ? $set($field, $value) : null;

        $fill('name', $info->name);
        $fill('brand', $info->brand);
        $unit = PackageUnit::tryFrom((string) $info->packageUnit);
        if ($info->packageValue !== null && $unit !== null && ($overwrite || blank($get('package_value')))) {
            $set('package_value', $info->packageValue);
            $set('package_unit', $unit);
        }
        if ($info->categoryKey !== null) {
            $fill('category_id', Category::query()->where('key', $info->categoryKey)->value('id'));
        }

        Notification::make()
            ->title(__('Found: :name', ['name' => $info->name ?? $normalized]))
            ->body(__('Data from :source. Please check it.', ['source' => $info->source]))
            ->success()
            ->send();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile first: the name column carries brand and package size, so the
                // list works on a phone without scrolling sideways. Tap a row to edit.
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->description(fn (Product $record): string => collect([$record->brand, $record->packageLabel()])->filter()->implode(' · '))
                    ->searchable(['name', 'brand', 'barcode'])
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->formatStateUsing(fn (string $state): string => __($state))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy(Category::query()->select('sort')->whereColumn('categories.id', 'products.category_id'), $direction))
                    ->visibleFrom('sm'),
                TextColumn::make('package_value')
                    ->label(__('Package size'))
                    ->formatStateUsing(fn (Product $record): string => $record->packageLabel())
                    ->alignEnd()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('Category'))
                    ->options(fn (): array => static::categoryOptions()),
            ])
            // Deleting is in the edit view, which keeps the list narrow.
            ->recordActions([])
            ->emptyStateHeading(__('No products yet'))
            ->emptyStateDescription(__('Add the products you keep in your supply.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
