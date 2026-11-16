<?php

namespace App\Actions;

use App\Enums\Role;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteMember
{
    /**
     * Invite someone to the inviter's household. Inviting the same address again
     * renews the link and the 7-day expiry.
     */
    public function __invoke(User $inviter, string $email, ?string $name = null): Invitation
    {
        abort_unless($inviter->isOwner(), 403);

        $email = mb_strtolower(trim($email));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('This person already has an account.'),
            ]);
        }

        $plain = Str::random(64);

        $invitation = $inviter->household->invitations()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => Role::Member,
                'invited_by' => $inviter->id,
                'token_hash' => hash('sha256', $plain),
                'expires_at' => now()->addDays(Invitation::LIFETIME_DAYS),
            ],
        );

        Mail::to($email)->queue(
            (new InvitationMail($invitation, route('invitations.show', $plain)))
                ->locale(app()->getLocale())
        );

        return $invitation;
    }
}
