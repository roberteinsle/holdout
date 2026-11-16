<?php

namespace App\Modules\Inventory\Filament\Resources\Batches;

use App\Modules\Inventory\Enums\ExpiryStatus;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\CreateBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\EditBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\ListBatches;
use App\Modules\Inventory\Filament\Resources\Locations\LocationResource;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Product;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * All batches of the household, earliest best-before date first, so whatever
 * expires next is at the top. Per product they also appear on the product page.
 */
class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('Batch');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Batches');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forHousehold(auth()->user()->household_id)
            ->with(['product', 'location']);
    }

    public static function eatSoonDays(): int
    {
        return (int) auth()->user()->household->eat_soon_days;
    }

    /** @return array<int, string> product id => "Name · 500 g", only the current household's */
    public static function productOptions(): array
    {
        return Product::query()
            ->where('household_id', auth()->user()->household_id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => collect([$product->name, $product->brand, $product->packageLabel()])->filter()->implode(' · '),
            ])
            ->all();
    }

    /**
     * The batch fields. Without the product select they serve the batches
     * section on the product page, where the product is given.
     *
     * @return array<int, Component>
     */
    public static function fields(bool $withProduct = true): array
    {
        return array_values(array_filter([
            $withProduct ? Select::make('product_id')
                ->label(__('Product'))
                ->options(fn (): array => static::productOptions())
                ->searchable()
                ->required()
                ->in(fn (): array => array_keys(static::productOptions())) : null,
            TextInput::make('quantity')
                ->label(__('Packages'))
                ->helperText(__('How many packages with the same best-before date.'))
                ->integer()
                ->required()
                ->default(1)
                ->minValue(1)
                ->maxValue(10000),
            DatePicker::make('best_before')
                ->label(__('Best before'))
                ->helperText(__('Leave empty if the package has no date, e.g. salt or sugar.'))
                ->minDate('2000-01-01')
                ->maxDate('2100-12-31'),
            Select::make('location_id')
                ->label(__('Location'))
                ->options(fn (): array => LocationResource::options())
                ->searchable()
                ->in(fn (): array => array_keys(LocationResource::options()))
                ->createOptionForm(LocationResource::fields(ignoreRecord: false))
                ->createOptionUsing(fn (array $data): int => LocationResource::createForHousehold($data)->getKey()),
            TextInput::make('note')
                ->label(__('Note'))
                ->maxLength(150),
        ]));
    }

    /** e.g. "4 × 500 g · Cellar · bought on offer": amount, location, note. */
    public static function details(Batch $batch, bool $withAmount = true): ?string
    {
        return collect([$withAmount ? $batch->quantityLabel() : null, $batch->location?->name, $batch->note])->filter()->implode(' · ') ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::fields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile first: the product name carries amount, location and note.
                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->description(fn (Batch $record): ?string => static::details($record))
                    ->searchable(['name', 'brand', 'barcode']),
                static::bestBeforeColumn(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByBestBefore())
            ->filters([
                static::statusFilter(),
                SelectFilter::make('location_id')
                    ->label(__('Location'))
                    ->options(fn (): array => LocationResource::options()),
                SelectFilter::make('product_id')
                    ->label(__('Product'))
                    ->options(fn (): array => static::productOptions()),
            ])
            ->recordActions([])
            ->emptyStateHeading(__('No batches yet'))
            ->emptyStateDescription(__('Record what you have in stock: how many packages of a product and their best-before date.'));
    }

    /** Best-before date as a badge coloured by the expiry status. */
    public static function bestBeforeColumn(): TextColumn
    {
        return TextColumn::make('best_before')
            ->label(__('Best before'))
            ->badge()
            ->getStateUsing(fn (Batch $record): string => $record->best_before?->locale(app()->getLocale())->isoFormat('L') ?? __('No date'))
            ->color(fn (Batch $record): string => $record->status(static::eatSoonDays())->getColor())
            ->tooltip(fn (Batch $record): string => $record->status(static::eatSoonDays())->getLabel())
            ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByBestBefore($direction))
            ->alignEnd();
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('Status'))
            ->options(ExpiryStatus::class)
            ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                ? $query->withStatus(ExpiryStatus::from($data['value']), static::eatSoonDays())
                : $query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatches::route('/'),
            'create' => CreateBatch::route('/create'),
            'edit' => EditBatch::route('/{record}/edit'),
        ];
    }
}
