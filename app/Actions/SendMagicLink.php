<?php

namespace App\Actions;

use App\Mail\MagicLinkMail;
use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendMagicLink
{
    /**
     * Send a sign-in link if an account exists. Callers must not reveal whether it did.
     */
    public function __invoke(string $email): void
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->first();

        if ($user === null) {
            return;
        }

        $this->forUser($user);
    }

    public function forUser(User $user): void
    {
        // Drop expired tokens so the table does not grow forever.
        $user->loginTokens()->where('expires_at', '<=', now())->delete();

        $token = LoginToken::issueFor($user);

        Mail::to($user)->queue(
            (new MagicLinkMail($user, route('login.confirm', $token)))
                ->locale($user->locale ?? app()->getLocale())
        );
    }
}
