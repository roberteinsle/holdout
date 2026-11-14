<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['email', 'name', 'role', 'invited_by', 'token_hash', 'expires_at'])]
class Invitation extends Model
{
    public const LIFETIME_DAYS = 7;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'role' => Role::class,
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Give the invitation a fresh token and expiry; returns the plain token for the link.
     */
    public function refreshToken(): string
    {
        $plain = Str::random(64);

        $this->forceFill([
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ])->save();

        return $plain;
    }

    public static function findValid(string $plain): ?self
    {
        return self::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where('expires_at', '>', now())
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
