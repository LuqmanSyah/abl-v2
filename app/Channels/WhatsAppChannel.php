<?php

namespace App\Channels;

use App\Jobs\SendWhatsAppNotification;
use App\Models\User;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function send(User $notifiable, Notification $notification): void
    {
        if (! $notifiable->phone || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        SendWhatsAppNotification::dispatch(
            phone: $notifiable->phone,
            message: $message,
        );
    }
}
