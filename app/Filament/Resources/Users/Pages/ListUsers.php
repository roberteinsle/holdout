<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\InviteMember;
use App\Filament\Resources\Users\UserResource;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label(__('Invite'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->authorize(fn (): bool => auth()->user()->can('create', Invitation::class))
                ->modalHeading(__('Invite a family member'))
                ->modalDescription(__('They get an email with a link that is valid for :days days.', ['days' => Invitation::LIFETIME_DAYS]))
                ->modalSubmitActionLabel(__('Send invitation'))
                ->schema([
                    TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(User::class, 'email')
                        ->validationMessages(['unique' => __('This person already has an account.')]),
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->maxLength(100),
                ])
                ->action(function (array $data, InviteMember $inviteMember): void {
                    $inviteMember(auth()->user(), $data['email'], $data['name'] ?? null);

                    Notification::make()
                        ->title(__('Invitation sent to :email.', ['email' => $data['email']]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
