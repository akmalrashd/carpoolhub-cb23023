<?php

namespace App\Notifications;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): Mailable
    {
        return (new VerifyEmailMail($notifiable))->to($notifiable->email);
    }
}
