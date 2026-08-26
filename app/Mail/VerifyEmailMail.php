<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your CarpoolHub email address',
        );
    }

    public function content(): Content
    {
        $expireMinutes = 60;

        return new Content(
            view: 'emails.verify-email',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'verifyUrl' => URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes($expireMinutes),
                    [
                        'id' => $this->user->getKey(),
                        'hash' => sha1($this->user->getEmailForVerification()),
                    ]
                ),
                'expireMinutes' => $expireMinutes,
            ],
        );
    }
}
