<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public string $phone,
        public string $message,
    ) {}

    public function handle(): void
    {
        $baseUrl = config('services.wa.base_url');
        $apiKey  = config('services.wa.api_key');

        if (! $baseUrl || ! $apiKey) {
            return;
        }

        Http::withHeaders([
            'Authorization' => $apiKey,
        ])->post("{$baseUrl}/send", [
            'target' => $this->phone,
            'message' => $this->message,
        ]);
    }
}
