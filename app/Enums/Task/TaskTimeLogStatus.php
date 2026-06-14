<?php

namespace App\Enums\Task;

enum TaskTimeLogStatus: int{

    case START = 0;
    case PAUSE = 1;
    case STOP = 2;
    case NOT_STARTED = 3;
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
