<?php

namespace App\Notifications\Strategies;

use App\Contracts\NotificationStrategy;
use Illuminate\Support\Facades\Log;

class EmailNotificationStrategy implements NotificationStrategy
{
    public function send(string $message, array $recipient): void
    {
        // Stub: Log instead of sending real email
        Log::info('EMAIL STUB: Sending to ' . $recipient['email'] . ': ' . $message);
    }
}
