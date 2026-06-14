<?php

namespace App\Models\Client;

use App\Enums\Client\ClientServiceDiscountStatus;
use App\Enums\Client\ClientServiceDiscountType;
use App\Enums\Client\ClientShowStatus;
use App\Models\ServiceCategory\ServiceCategory;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientServiceDiscount extends Model
{
    use HasFactory, SoftDeletes, CreatedUpdatedBy;
    protected $fillable = [
        'client_id',
        'service_category_ids',
        'discount',
        'is_active',
        'type',
        'is_show',
        'category'
    ];

    protected $casts = [
        'is_active' => ClientServiceDiscountStatus::class,
        'type' => ClientServiceDiscountType::class,
        'is_show'=>ClientShowStatus::class
    ];

    public function getServiceCategoryIdsAttribute(): array
    {
        return $this->attributes['service_category_ids']
            ? explode(',', $this->attributes['service_category_ids'])
            : [];
    }

    public function setServiceCategoryIdsAttribute($value): void
    {
        $this->attributes['service_category_ids'] = is_array($value)
            ? implode(',', $value)
            : $value;
    }

    public function serviceNames()
    {

        return ServiceCategory::whereIn('id', $this->service_category_ids)->pluck('name')->toArray();
    }
}
