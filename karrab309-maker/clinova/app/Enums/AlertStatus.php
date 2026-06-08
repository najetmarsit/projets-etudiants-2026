<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Sent = 'sent';
    case Acknowledged = 'acknowledged';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Escalated = 'escalated';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public static function activeValues(): array
    {
        return [
            self::Sent->value,
            self::Acknowledged->value,
            self::InProgress->value,
            self::Escalated->value,
        ];
    }
}

