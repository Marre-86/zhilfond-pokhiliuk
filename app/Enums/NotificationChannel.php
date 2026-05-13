<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case TELEGRAM = 'telegram';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::EMAIL->value => 'Email',
            self::TELEGRAM->value => 'Telegram',
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::TELEGRAM => 'Telegram',
        };
    }
}
