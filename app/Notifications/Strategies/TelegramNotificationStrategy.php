<?php

namespace App\Notifications\Strategies;

use App\Contracts\NotificationStrategy;
use App\Notifications\SendResult;
use Illuminate\Support\Facades\Log;

class TelegramNotificationStrategy implements NotificationStrategy
{
    public function send(string $message, array $recipient): SendResult
    {
        // Stub: Log instead of sending real telegram message
        Log::info('TELEGRAM STUB: Sending to ' . $recipient['telegram_chat_id'] . ': ' . $message);

        // Simulate random success/failure for study project
        $successRate = config('notifications.mock.success_rate.telegram', 0.85); // 85% success rate
        $shouldSucceed = mt_rand(1, 100) <= ($successRate * 100);

        if ($shouldSucceed) {
            // Simulate random delay
            $delayMs = config('notifications.mock.average_delay_ms.telegram', 200);
            $actualDelay = mt_rand((int)($delayMs * 0.5), (int)($delayMs * 1.5));
            usleep($actualDelay * 1000); // Convert to microseconds

            return SendResult::success();
        } else {
            // Simulate failure
            $errorTypes = [
                'network_error' => 'Network error connecting to Telegram API',
                'chat_not_found' => 'Chat ID not found or bot not added',
                'message_too_long' => 'Message exceeds Telegram length limit',
                'rate_limit' => 'Telegram API rate limit exceeded',
            ];

            $errorKey = array_rand($errorTypes);
            $errorMessage = $errorTypes[$errorKey];

            // Determine if error is transient (should retry)
            $transientErrors = ['network_error', 'rate_limit'];
            $shouldRetry = in_array($errorKey, $transientErrors);
            $retryDelay = $shouldRetry ? mt_rand(1, 3) * 60 : null; // 1-3 minutes

            return SendResult::failure($errorMessage, $errorKey, $shouldRetry, $retryDelay);
        }
    }
}
