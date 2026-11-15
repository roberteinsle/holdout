<?php

namespace App\Filament\Pages;

use App\Support\Locales;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

/**
 * The profile of the person using this device: name and language.
 * Reached from the user menu, not from the navigation.
 */
class Profile extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function getTitle(): string
    {
        return __('Profile');
    }

    public function mount(): void
    {
        $this->form->fill([
            'name' => auth()->user()->name,
            'locale' => auth()->user()->locale ?? app()->getLocale(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()->schema([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(100),
                    Select::make('locale')
                        ->label(__('Language'))
                        ->options(Locales::SUPPORTED)
                        ->required()
                        ->rule(Rule::in(Locales::codes()))
                        ->selectablePlaceholder(false),
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
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $user->update(['name' => $data['name'], 'locale' => $data['locale']]);

        if ($user->wasChanged('locale')) {
            // Reload so the whole app switches to the new language.
            app()->setLocale($data['locale']);
            Notification::make()->title(__('Saved.'))->success()->send();
            $this->redirect(static::getUrl());

            return;
        }

        Notification::make()->title(__('Saved.'))->success()->send();
    }
}
