<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $token,
        public User $user,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your CarpoolHub password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'resetUrl' => url(route('password.reset', [
                    'token' => $this->token,
                    'email' => $this->user->email,
                ], false)),
                'expireMinutes' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60),
            ],
        );
    }
}
