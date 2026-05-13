<?php

use App\Enums\NotificationChannel;

return [
    'strategies' => [
        NotificationChannel::EMAIL->value => App\Notifications\Strategies\EmailNotificationStrategy::class,
        NotificationChannel::TELEGRAM->value => App\Notifications\Strategies\TelegramNotificationStrategy::class,
    ]
];