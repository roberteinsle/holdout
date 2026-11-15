<?php

namespace App\Modules\Inventory\Filament\Resources\Products;

use App\Models\Category;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\ListProducts;
use App\Modules\Inventory\Models\Product;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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
        return $schema->components([
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
            TextInput::make('barcode')
                ->label(__('Barcode'))
                ->helperText(__('Optional. The number under the barcode (8 to 14 digits).'))
                ->regex('/^\d{8,14}$/')
                ->maxLength(14)
                ->unique(
                    table: Product::class,
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('household_id', auth()->user()->household_id),
                ),
        ]);
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
