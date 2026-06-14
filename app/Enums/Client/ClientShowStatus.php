<?php
namespace App\Enums\Client;
enum ClientShowStatus :int{
    case SHOW = 1;
    case HIDDEN = 0;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

