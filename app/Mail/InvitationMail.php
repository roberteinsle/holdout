<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __(':name invited you to :household', [
            'name' => $this->invitation->inviter?->name ?? config('app.name'),
            'household' => $this->invitation->household->name,
        ]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.invitation', with: [
            'days' => Invitation::LIFETIME_DAYS,
        ]);
    }
}
