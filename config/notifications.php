<?php

return [
    'strategies' => [
        'email' => App\Notifications\Strategies\EmailNotificationStrategy::class,
        'telegram' => App\Notifications\Strategies\TelegramNotificationStrategy::class,
    ]
];