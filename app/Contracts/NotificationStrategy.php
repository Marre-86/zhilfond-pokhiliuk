<?php

namespace App\Contracts;

interface NotificationStrategy
{
    public function send(string $message, array $recipient): void;
}
