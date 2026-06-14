<?php

namespace App\Services\Select;

use App\Models\ServiceCategory\ServiceCategory;

class ServiceCategorySelectService
{
    public function getAllServiceCategories()
    {
        return ServiceCategory::all(['id as value', 'name as label']);
    }
}
