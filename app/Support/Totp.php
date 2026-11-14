<?php

namespace App\Support;

use App\Models\User;
use chillerlan\QRCode\QRCode;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Authenticator-app codes (TOTP, RFC 6238) as a backup way to sign in.
 */
class Totp
{
    public const RECOVERY_CODE_COUNT = 8;

    /** No 0/O, 1/I/L: the codes are meant to be printed and typed in by hand. */
    private const RECOVERY_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(private Google2FA $google2fa = new Google2FA) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /** The QR code as an SVG data URI, for an <img> tag. */
    public function qrCode(User $user, string $secret): string
    {
        $uri = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);

        return (new QRCode)->render($uri);
    }

    public function verifySecret(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $this->normalize($code), 1);
    }

    /**
     * Check a code for a user with TOTP enabled. A code that was already used
     * (or an older one) is rejected, so a code seen over someone's shoulder
     * cannot be replayed.
     */
    public function verifyForUser(User $user, string $code): bool
    {
        if ($user->totp_confirmed_at === null || $user->totp_secret === null) {
            return false;
        }

        $step = $this->google2fa->verifyKeyNewer(
            $user->totp_secret,
            $this->normalize($code),
            // 0 instead of null: the library then returns the matched time step.
            $user->totp_last_used_step ?? 0,
            1,
        );

        if ($step === false) {
            return false;
        }

        $user->forceFill(['totp_last_used_step' => $step])->save();

        return true;
    }

    /** Recovery codes work once each. */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        if ($user->totp_confirmed_at === null) {
            return false;
        }

        $code = Str::upper(trim($code));
        $codes = $user->totp_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals($stored, $code)) {
                unset($codes[$index]);
                $user->forceFill(['totp_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => $this->randomChars(4).'-'.$this->randomChars(4))
            ->all();
    }

    private function randomChars(int $length): string
    {
        $chars = '';

        for ($i = 0; $i < $length; $i++) {
            $chars .= self::RECOVERY_ALPHABET[random_int(0, strlen(self::RECOVERY_ALPHABET) - 1)];
        }

        return $chars;
    }

    private function normalize(string $code): string
    {
        return preg_replace('/\D/', '', $code) ?? '';
    }
}
