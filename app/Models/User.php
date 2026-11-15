<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A person in the household. There is no sign-in: each device picks who is
 * using it ("Who are you?") and remembers that person.
 */
#[Fillable(['name'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** People have no password; the remember-me cookie must not depend on one. */
    public function getAuthPassword(): string
    {
        return '';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->household_id !== null;
    }
}
