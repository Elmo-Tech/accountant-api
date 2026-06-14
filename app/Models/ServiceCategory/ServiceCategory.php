<?php

namespace App\Models\ServiceCategory;

use App\Enums\ServiceCategory\ServiceCategoryAddToInvoiceStatus;
use App\Models\Parameter\ParameterValue;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use HasFactory, CreatedUpdatedBy, SoftDeletes;
    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'add_to_invoice',
        'service_type_id',
        'extra_is_pricable',
        'extra_code',
        'extra_price',
        'extra_price_description'
    ];

    protected $casts = [
        'add_to_invoice' => ServiceCategoryAddToInvoiceStatus::class
    ];

    public function getPrice()
    {
        if ($this->add_to_invoice == ServiceCategoryAddToInvoiceStatus::REMOVE) {
            return 0;
        }
        return $this->price;
    }
    public function serviceType()
    {
      return $this->belongsTo(ParameterValue::class,'service_type_id');
    }
}
