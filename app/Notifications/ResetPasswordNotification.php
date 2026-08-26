<?php

namespace App\Notifications;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token)
    {
    }

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): Mailable
    {
        return (new ResetPasswordMail($this->token, $notifiable))->to($notifiable->email);
    }
}
