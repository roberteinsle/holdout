<?php

namespace App\Modules\Equipment\Filament\Resources\Equipment;

use App\Modules\Equipment\Enums\CheckResult;
use App\Modules\Equipment\Enums\EquipmentArea;
use App\Modules\Equipment\Enums\EquipmentStatus;
use App\Modules\Equipment\Filament\Resources\Equipment\Pages\CreateEquipment;
use App\Modules\Equipment\Filament\Resources\Equipment\Pages\EditEquipment;
use App\Modules\Equipment\Filament\Resources\Equipment\Pages\ListEquipment;
use App\Modules\Equipment\Filament\Resources\Equipment\RelationManagers\ChecksRelationManager;
use App\Modules\Equipment\Models\EquipmentItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Equipment and checklists beyond food: home pharmacy, power outage, hygiene,
 * documents, cash, grab bag, fire safety, staying informed, other equipment.
 */
class EquipmentResource extends Resource
{
    protected static ?string $model = EquipmentItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?int $navigationSort = 45;

    protected static ?string $slug = 'equipment';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Equipment item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Equipment');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('household_id', auth()->user()->household_id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(150),
            Select::make('area')
                ->label(__('Area'))
                ->options(EquipmentArea::class)
                ->default(EquipmentArea::Other)
                ->required()
                ->selectablePlaceholder(false),
            Toggle::make('available')
                ->label(__('Available'))
                ->helperText(__('Switch on when the household has it.'))
                ->default(true),
            Grid::make(2)->schema([
                TextInput::make('quantity')
                    ->label(__('Quantity'))
                    ->integer()
                    ->minValue(0)
                    ->maxValue(100000),
                TextInput::make('location')
                    ->label(__('Location'))
                    ->maxLength(100),
            ]),
            Grid::make(2)->schema([
                DatePicker::make('expires_on')
                    ->label(__('Expires on'))
                    ->helperText(__('e.g. gas cartridge, medication, fire extinguisher service.'))
                    ->minDate('2000-01-01')
                    ->maxDate('2100-12-31'),
                TextInput::make('check_interval_months')
                    ->label(__('Check every'))
                    ->helperText(__('Leave empty if it needs no regular check.'))
                    ->integer()
                    ->minValue(1)
                    ->maxValue(120)
                    ->suffix(__('months')),
            ]),
            TextInput::make('note')
                ->label(__('Note'))
                ->maxLength(255),
        ]);
    }

    /** The "Check" action: one tap in the list, recorded in the item's check log. */
    public static function checkAction(): Action
    {
        return Action::make('check')
            ->label(__('Check'))
            ->icon(Heroicon::OutlinedCheckBadge)
            ->tooltip(__('Check'))
            ->modalHeading(fn (EquipmentItem $record): string => __('Check: :name', ['name' => $record->name]))
            ->modalSubmitActionLabel(__('Save check'))
            ->schema([
                Radio::make('result')
                    ->label(__('Result'))
                    ->options(CheckResult::class)
                    ->default(CheckResult::Ok->value)
                    ->required()
                    ->live(),
                DatePicker::make('expires_on')
                    ->label(__('New expiry date'))
                    ->visible(fn (Get $get): bool => $get('result') === CheckResult::Replaced->value || $get('result') === CheckResult::Replaced)
                    ->minDate('2000-01-01')
                    ->maxDate('2100-12-31'),
                TextInput::make('note')
                    ->label(__('Note'))
                    ->maxLength(255),
            ])
            ->action(function (EquipmentItem $record, array $data): void {
                $result = $data['result'] instanceof CheckResult ? $data['result'] : CheckResult::from($data['result']);
                $record->recordCheck($result, auth()->user(), $data['note'] ?? null, $data['expires_on'] ?? null);

                Notification::make()->title(__('Check saved.'))->success()->send();
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile first: quantity and location under the name.
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->description(fn (EquipmentItem $record): ?string => collect([
                        $record->quantity !== null ? __('Quantity: :count', ['count' => $record->quantity]) : null,
                        $record->location,
                    ])->filter()->implode(' · ') ?: null)
                    ->searchable(['name', 'location', 'note'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->state(fn (EquipmentItem $record): EquipmentStatus => $record->status())
                    ->badge(),
                TextColumn::make('expires_on')
                    ->label(__('Expires on'))
                    ->date()
                    ->placeholder('–')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('last_checked_at')
                    ->label(__('Last check'))
                    ->since()
                    ->placeholder(__('never'))
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->defaultGroup(
                Group::make('area')
                    ->label(__('Area'))
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (EquipmentItem $record): string => $record->area->getLabel())
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query->orderByRaw(static::areaOrderSql().' '.($direction === 'desc' ? 'desc' : 'asc')))
                    ->collapsible(),
            )
            ->defaultSort('name')
            ->paginated(false)
            ->filters([
                SelectFilter::make('area')
                    ->label(__('Area'))
                    ->options(EquipmentArea::class),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(EquipmentStatus::class)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->withStatus(EquipmentStatus::from($data['value']))
                        : $query),
            ])
            // Icon button in the list, so rows fit on a phone without scrolling sideways.
            ->recordActions([
                static::checkAction()->iconButton()->size('lg'),
            ])
            ->toolbarActions([
                BulkAction::make('checkedOk')
                    ->label(__('Checked: all OK'))
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(fn (EquipmentItem $item) => $item->recordCheck(CheckResult::Ok, auth()->user()));

                        Notification::make()->title(__('Check saved.'))->success()->send();
                    }),
            ])
            ->emptyStateHeading(__('No equipment yet'))
            ->emptyStateDescription(__('Add the suggested items from the BBK checklist and tick off what you already have.'));
    }

    /** Areas in their checklist order, not alphabetically. */
    private static function areaOrderSql(): string
    {
        $cases = collect(EquipmentArea::cases())
            ->map(fn (EquipmentArea $area): string => "when '{$area->value}' then {$area->sort()}")
            ->implode(' ');

        return "case area {$cases} else 99 end";
    }

    public static function getRelations(): array
    {
        return [
            ChecksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipment::route('/'),
            'create' => CreateEquipment::route('/create'),
            'edit' => EditEquipment::route('/{record}/edit'),
        ];
    }
}
