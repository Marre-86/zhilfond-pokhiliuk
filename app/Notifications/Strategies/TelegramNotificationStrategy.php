<?php

namespace App\Notifications\Strategies;

use App\Contracts\NotificationStrategy;
use Illuminate\Support\Facades\Log;

class TelegramNotificationStrategy implements NotificationStrategy
{
    public function send(string $message, array $recipient): void
    {
        // Stub: Log instead of sending real email
        Log::info('TELEGRAM STUB: Sending to ' . $recipient['telegram_chat_id'] . ': ' . $message);
    }
}
