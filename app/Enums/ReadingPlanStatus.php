<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Planned => '未読',
            self::Completed => '読了',
            self::Expired => '期限切れ',
        };
    }
}
