<?php

namespace App\Console\Commands;

use App\Notifications\NotificationService;
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
    public function handle(NotificationService $notificationService): int
    {
        $channel = $this->argument('channel');
        $message = $this->argument('message');
        $recipientJson = $this->argument('recipient');

        $recipient = json_decode($recipientJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON provided for recipient.');
            return self::FAILURE;
        }

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
}
