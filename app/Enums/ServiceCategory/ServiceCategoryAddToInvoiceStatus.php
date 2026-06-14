<?php

namespace App\Enums\ServiceCategory;

enum ServiceCategoryAddToInvoiceStatus: int{

    case ADD = 1;
    case REMOVE = 0;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
