<?php

namespace App\Enums;

enum NotificationStatus: int
{
    case PENDING = 0;
    case SENT = 1;
    case ERROR = 2;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'pending',
            self::SENT->value => 'sent',
            self::ERROR->value => 'error',
        ];
    }

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'pending',
            self::SENT => 'sent',
            self::ERROR => 'error',
        };
    }
}