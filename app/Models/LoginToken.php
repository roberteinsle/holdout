<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Single-use magic-link token. Only the SHA-256 hash is stored.
 */
#[Fillable(['token_hash', 'expires_at'])]
class LoginToken extends Model
{
    public const LIFETIME_MINUTES = 15;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a token for the user and return the plain value for the link.
     */
    public static function issueFor(User $user): string
    {
        $plain = Str::random(64);

        $user->loginTokens()->create([
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
        ]);

        return $plain;
    }

    public static function findValid(string $plain): ?self
    {
        return self::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where('expires_at', '>', now())
            ->first();
    }
}
