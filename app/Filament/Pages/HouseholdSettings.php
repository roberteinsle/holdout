<?php

namespace App\Filament\Pages;

use App\Enums\MemberType;
use App\Models\Household;
use App\Support\SupplyTargets;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

/**
 * Household settings: name, size (adults/children), target days and the
 * "eat soon" window, plus the resulting target amount per
 * category (SupplyTargets, from the CategoryRegistry).
 */
class HouseholdSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'household';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Household');
    }

    public function getTitle(): string
    {
        return __('Household');
    }

    public function mount(): void
    {
        $household = $this->household();

        $this->form->fill([
            'name' => $household->name,
            'adults' => $household->memberCount(MemberType::Adult),
            'children' => $household->memberCount(MemberType::Child),
            'target_days' => $household->target_days,
            'eat_soon_days' => $household->eat_soon_days,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Household'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Household name'))
                            ->required()
                            ->maxLength(100),
                    ]),
                Section::make(__('Who is the supply for?'))
                    ->description(__('Everyone who lives in the household, including small children. The amounts you need are calculated from this.'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('adults')
                            ->label(MemberType::Adult->getLabel())
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(30),
                        TextInput::make('children')
                            ->label(MemberType::Child->getLabel())
                            ->helperText(__('Up to 12 years, as in the BLE guidance'))
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->maxValue(30),
                    ]),
                Section::make(__('Planning'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('target_days')
                            ->label(__('Target days'))
                            ->helperText(__('How many days the supply should last. The German Federal Office of Civil Protection (BBK) recommends a supply for 10 days.'))
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix(__('days')),
                        TextInput::make('eat_soon_days')
                            ->label(__('"Eat soon" window'))
                            ->helperText(__('Items that expire within this many days appear on the "Eat soon" list.'))
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix(__('days')),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('Save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
            Section::make(fn (): string => __('What you need for :days days', ['days' => $this->household()->target_days]))
                ->description(__('Calculated from the household size and the target days. Updated when you save.'))
                ->schema([
                    View::make('filament.pages.household-targets')
                        ->viewData(fn (): array => $this->targetsViewData()),
                ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function targetsViewData(): array
    {
        $household = $this->household()->fresh('members');

        return [
            'targets' => app(SupplyTargets::class)->for($household),
            'days' => $household->target_days,
            'people' => $household->memberTotal(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $household = $this->household();

        DB::transaction(function () use ($household, $data): void {
            $household->update([
                'name' => $data['name'],
                'target_days' => (int) $data['target_days'],
                'eat_soon_days' => (int) $data['eat_soon_days'],
            ]);
            $household->setMemberCount(MemberType::Adult, (int) $data['adults']);
            $household->setMemberCount(MemberType::Child, (int) $data['children']);
        });

        Notification::make()->title(__('Saved.'))->success()->send();
    }

    private function household(): Household
    {
        return auth()->user()->household;
    }
}
