<?php

namespace App\Enums\Task;

enum TaskTimeLogType: int{

    case TIME_LOG = 0;
    case BACK_TIME_LOG = 1;
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
