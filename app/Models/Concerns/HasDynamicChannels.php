<?php

namespace App\Models\Concerns;

use App\Models\User;

trait HasDynamicChannels
{
    protected function resolveChannels(User $notifiable, array $baseChannels): array
    {
        $prefs = $notifiable->notification_preferences ?? [];

        $filtered = array_values(array_filter($baseChannels, function (string $channel) use ($prefs): bool {
            return match ($channel) {
                'database' => $prefs['inapp'] ?? true,
                'webpush'  => $prefs['webpush'] ?? true,
                'mail'     => $prefs['email'] ?? true,
                default    => true,
            };
        }));

        if (($prefs['wa'] ?? false) && method_exists($this, 'toWhatsApp')) {
            $filtered[] = 'wa';
        }

        return $filtered;
    }
}
