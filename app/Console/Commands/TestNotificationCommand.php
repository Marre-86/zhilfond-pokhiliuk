<?php

namespace App\Console\Commands;

use App\Notifications\NotificationService;
use App\Services\NotificationCreator;
use App\Models\User;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:test
        {channel : Notification channel (email or telegram)}
        {message : Message to send}
        {recipient : Recipient data as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the NotificationService by sending a notification via a specified channel';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService, NotificationCreator $notificationCreator): int
    {
        $channel = $this->argument('channel');
        $message = $this->argument('message');
        $recipientJson = $this->argument('recipient');

        $recipient = json_decode($recipientJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON provided for recipient.');
            return self::FAILURE;
        }

        // Get default user (test user from seeder)
        $user = $this->getDefaultUser();
        $userId = $user->id;

        // Create notification in database
        try {
            $notification = $notificationCreator->create([
                'message' => $message,
                'user_id' => $userId,
                'channel' => $channel,
                'status' => 0, // PENDING
            ]);
            $this->info('Notification created in database with ID: ' . $notification->id);
        } catch (InvalidArgumentException $e) {
            $this->error('Failed to create notification: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Send notification via service (logs)
        try {
            $notificationService->setStrategyByChannel($channel);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        try {
            $notificationService->notify($notification, $recipient);

            // Refresh notification to get updated status
            $notification->refresh();

            if ($notification->status->value === \App\Enums\NotificationStatus::SENT->value) {
                $this->info('Notification sent successfully. Status: SENT');
            } else {
                $this->warn('Notification failed. Status: ERROR - ' . $notification->error_message);

                if ($notification->retry_count < $notification->max_retries) {
                    $this->info(sprintf(
                        'Notification will be retried (attempt %d of %d)',
                        $notification->retry_count,
                        $notification->max_retries
                    ));
                }
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Get the default user (test user from seeder).
     *
     * @return User
     */
    private function getDefaultUser(): User
    {
        $testUserEmail = config('app.user_email');
        $user = User::where('email', $testUserEmail)->first();

        return $user;
    }
}
