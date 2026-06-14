<?php

namespace App\Models\Client;

use App\Enums\Client\AddableToBulk;
use App\Enums\Client\ClientServiceDiscountStatus;
use App\Enums\Client\ClientServiceDiscountType;
use App\Enums\ServiceCategory\ServiceCategoryAddToInvoiceStatus;
use App\Models\ServiceCategory\ServiceCategory;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, CreatedUpdatedBy;

    protected $fillable = [
        'iva',
        'ragione_sociale',
        'cf',
        'note',
        'email',
        'phone',
        'note',
        'price',
        'monthly_price',
        'hours_per_month',
        'payment_type_id' ,
        'pay_steps_id',
        'payment_type_two_id',
        'iban',
        'abi',
        'cab',
        'addable_to_bulk_invoice',
        'allowed_days_to_pay',
        'is_company',
        'total_tax',
        'total_tax_description',
        'email_f24',
        'sdi',
        'proforma',
        'limit_decreto',
    ];
    protected $casts = [
        'addable_to_bulk_invoice' => AddableToBulk::class
    ];
    public function addresses()
    {
        return $this->hasMany(ClientAddress::class, 'client_id');
    }

    public function contacts()
    {
        return $this->hasMany(ClientContact::class, 'client_id');
    }

    public function getClientDiscount($serviceId)
    {
        $discount = ClientServiceDiscount::where('client_id', $this->id)
        ->whereRaw("FIND_IN_SET(?, service_category_ids)", [$serviceId])
        ->where('is_active', ClientServiceDiscountStatus::ACTIVE)
        ->latest()
        ->first();

        $service = ServiceCategory::find($serviceId);

        if ($service->add_to_invoice == ServiceCategoryAddToInvoiceStatus::REMOVE) {
            return 0;
        }

        if ($discount) {
            if ($discount->type == ClientServiceDiscountType::PERCENTAGE) {
                return $service->price - (($discount->discount / 100) * $service->price);
            } else {
                return $service->price - $discount->discount;
            }
        } else {
            return $service->price;
        }
    }

    public function payInstallments()
    {
        return $this->hasMany(ClientPayInstallment::class, 'client_id');
    }

}
