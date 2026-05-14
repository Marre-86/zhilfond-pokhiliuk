<?php

namespace App\Enums;

enum NotificationStatus: int
{
    case PENDING = 0;
    case PROCESSING = 1;
    case SENT = 2;
    case ERROR = 3;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'pending',
            self::PROCESSING->value => 'processing',
            self::SENT->value => 'sent',
            self::ERROR->value => 'error',
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::PROCESSING => 'processing',
            self::SENT => 'sent',
            self::ERROR => 'error',
        };
    }
}
