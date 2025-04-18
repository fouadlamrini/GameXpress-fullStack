<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable)
    {
        return ['mail']; // This specifies email as the notification channel
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Welcome to Our Platform!')
                    ->greeting('Hello!')
                    ->line('Thank you for registering with us.')
                    ->action('Visit Website', url('/'))
                    ->line('We appreciate having you onboard.');
    }
}