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
        {recipient : Recipient data as JSON}
        {--user-id= : User ID for the notification (defaults to first user or creates one)}';

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

        // Get default user (admin from seeder)
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
            $notificationService->notify($message, $recipient);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Notification sent successfully (logged).');
        return self::SUCCESS;
    }

    /**
     * Get the default user (admin user from seeder).
     *
     * @return User
     */
    private function getDefaultUser(): User
    {
        $adminEmail = env('ADMIN_EMAIL');
        $user = User::where('email', $adminEmail)->first();

        return $user;
    }
}
