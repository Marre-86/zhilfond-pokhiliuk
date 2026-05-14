<?php

namespace App\Notifications;

use App\Contracts\NotificationStrategy;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected ?NotificationStrategy $strategy = null;

    public function setStrategyByChannel(string $channel): void
    {
        $strategies = config('notifications.strategies');

        if (!isset($strategies[$channel])) {
            throw new \InvalidArgumentException("Unknown channel: $channel");
        }

        $this->strategy = app($strategies[$channel]);
    }

    /**
     * Send a notification and update its status accordingly.
     */
    public function notify(Notification $notification, array $recipient): void
    {
        if (!$this->strategy) {
            throw new \RuntimeException('Notification strategy not set');
        }

        $notification->update([
            'status' => NotificationStatus::PROCESSING,
        ]);

        try {
            // Send notification using strategy
            $result = $this->strategy->send($notification->message, $recipient);

            if ($result->success) {
                // Success: update to SENT
                $notification->update([
                    'status' => NotificationStatus::SENT,
                    'sent_at' => now(),
                    'error_message' => null,
                    'error_code' => null,
                    'retry_count' => $notification->retry_count + 1,
                ]);

                Log::info("Notification {$notification->id} sent successfully via {$notification->channel}");
            } else {
                // Failure: update to ERROR
                $notification->update([
                    'status' => NotificationStatus::ERROR,
                    'failed_at' => now(),
                    'error_message' => $result->errorMessage,
                    'error_code' => $result->errorCode,
                    'retry_count' => $notification->retry_count + 1,
                ]);

                Log::warning("Notification {$notification->id} failed: {$result->errorMessage}");

                // Check if we should retry
                if ($result->shouldRetry && $notification->retry_count < $notification->max_retries) {
                    $this->scheduleRetry($notification, $result->retryDelay);
                }
            }
        } catch (\Exception $e) {
            // Unexpected exception
            $notification->update([
                'status' => NotificationStatus::ERROR,
                'failed_at' => now(),
                'error_message' => 'Unexpected error: ' . $e->getMessage(),
                'error_code' => 'unexpected_error',
                'retry_count' => $notification->retry_count + 1,
            ]);

            Log::error("Notification {$notification->id} unexpected error: " . $e->getMessage());

            // Check if we should retry (for unexpected errors, we retry once)
            if ($notification->retry_count < $notification->max_retries) {
                $this->scheduleRetry($notification, 60); // 1 minute delay
            }
        }
    }

    /**
     * Schedule a retry for a failed notification.
     */
    protected function scheduleRetry(Notification $notification, ?int $delaySeconds = null): void
    {
        $delay = $delaySeconds ?? config('notifications.retry.base_delay_seconds', 60);

        // In a real application, you would dispatch a job with delay
        // For this test project, we'll just log the retry
        Log::info(
            "Notification {$notification->id} scheduled for retry " .
            "in {$delay} seconds (attempt {$notification->retry_count})"
        );

        // You could implement a job dispatch here:
        // SendNotificationJob::dispatch($notification)->delay(now()->addSeconds($delay));
    }
}
