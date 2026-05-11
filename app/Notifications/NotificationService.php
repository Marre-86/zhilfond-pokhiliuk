<?php

namespace App\Notifications;

use App\Contracts\NotificationStrategy;

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

    public function notify(string $message, array $recipient): void
    {
        if (!$this->strategy) {
            throw new \RuntimeException('Notification strategy not set');
        }
        $this->strategy->send($message, $recipient);
    }
}
