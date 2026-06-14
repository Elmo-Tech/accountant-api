<?php

namespace App\Enums\Client;

enum ClientServiceDiscountType: int{

    case PERCENTAGE = 0;
    case FIXED = 1;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
