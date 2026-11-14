<?php

namespace App\Mail;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your sign-in link for :app', ['app' => config('app.name')]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.magic-link', with: [
            'minutes' => LoginToken::LIFETIME_MINUTES,
        ]);
    }
}
