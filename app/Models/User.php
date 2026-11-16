<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * A person with an account in the household. There are no passwords: people
 * sign in with a passkey, an email link or an authenticator-app code.
 */
#[Fillable(['name', 'email', 'locale'])]
#[Hidden(['remember_token', 'totp_secret', 'totp_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => Role::class,
            'totp_secret' => 'encrypted',
            'totp_confirmed_at' => 'datetime',
            'totp_recovery_codes' => 'encrypted:array',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return HasMany<LoginToken, $this> */
    public function loginTokens(): HasMany
    {
        return $this->hasMany(LoginToken::class);
    }

    public function hasTotpEnabled(): bool
    {
        return $this->totp_confirmed_at !== null;
    }

    public function isOwner(): bool
    {
        return $this->role === Role::Owner;
    }

    /** There is no password; the remember-me cookie must not depend on one. */
    public function getAuthPassword(): string
    {
        return '';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->household_id !== null;
    }
}
