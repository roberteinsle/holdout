<?php

namespace App\Modules\Inventory\Filament\Resources\Locations;

use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Locations\Pages\ManageLocations;
use App\Modules\Inventory\Models\Location;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

/**
 * Storage locations of the household. Short list, so everything happens in
 * dialogs on one page; the batches of a location are one tap away.
 */
class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Location');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Locations');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('household_id', auth()->user()->household_id)
            ->withCount('batches')
            ->withSum('batches', 'quantity');
    }

    /** @return array<int, string> location id => name, only the current household's */
    public static function options(): array
    {
        return Location::query()
            ->where('household_id', auth()->user()->household_id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Also used to add a location from the batch form; there the form's record
     * is a batch, so the unique check must not ignore it.
     *
     * @return array<int, Component>
     */
    public static function fields(bool $ignoreRecord = true): array
    {
        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->placeholder(__('e.g. Cellar shelf'))
                ->required()
                ->maxLength(100)
                ->unique(
                    table: Location::class,
                    ignoreRecord: $ignoreRecord,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('household_id', auth()->user()->household_id),
                ),
            TextInput::make('note')
                ->label(__('Note'))
                ->maxLength(150),
        ];
    }

    /** Create a location for the current household (household_id is not mass assignable). */
    public static function createForHousehold(array $data): Location
    {
        return auth()->user()->household->hasMany(Location::class)->create($data);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::fields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->description(fn (Location $record): ?string => $record->note)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('batches_sum_quantity')
                    ->label(__('Packages'))
                    ->formatStateUsing(fn (Location $record): string => (int) $record->batches_sum_quantity.' ×')
                    ->default(0)
                    ->url(fn (Location $record): string => BatchResource::getUrl('index', ['filters' => ['location_id' => ['value' => $record->id]]]))
                    ->color('primary')
                    ->alignEnd(),
            ])
            ->defaultSort('name')
            ->recordAction('edit')
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()
                    ->iconButton()
                    ->modalDescription(__('The batches stored here stay in the supply, without a location.')),
            ])
            ->emptyStateHeading(__('No locations yet'))
            ->emptyStateDescription(__('Add the places where you keep your supply, e.g. the cellar or a kitchen cupboard.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLocations::route('/'),
        ];
    }
}
