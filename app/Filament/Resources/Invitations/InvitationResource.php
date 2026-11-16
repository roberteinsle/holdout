<?php

namespace App\Filament\Resources\Invitations;

use App\Actions\InviteMember;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Models\Invitation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pending invitations. Only owners see this.
 */
class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 85;

    public static function getModelLabel(): string
    {
        return __('Invitation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Invitations');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('household_id', auth()->user()->household_id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->description(fn (Invitation $record) => $record->name),
                TextColumn::make('expires_at')
                    ->label(__('Valid until'))
                    ->date()
                    ->color(fn (Invitation $record) => $record->isExpired() ? 'danger' : null)
                    ->description(fn (Invitation $record) => $record->isExpired() ? __('Expired') : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->emptyStateHeading(__('No open invitations'))
            ->emptyStateDescription(__('Invite family members from the Family page.'))
            ->recordActions([
                Action::make('resend')
                    ->label(__('Resend'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->authorize(fn (): bool => auth()->user()->can('create', Invitation::class))
                    ->action(function (Invitation $record, InviteMember $inviteMember): void {
                        $inviteMember(auth()->user(), $record->email, $record->name);

                        Notification::make()->title(__('Invitation sent to :email.', ['email' => $record->email]))->success()->send();
                    }),
                DeleteAction::make()->label(__('Revoke')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitations::route('/'),
        ];
    }
}
