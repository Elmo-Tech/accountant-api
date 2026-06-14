<?php

namespace App\Models\Client;

use App\Models\Invoice\InvoiceDetail;
use App\Models\Parameter\ParameterValue;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPayInstallmentSubData extends Model
{
    use HasFactory, SoftDeletes, CreatedUpdatedBy;

    protected $table = 'client_pay_installment_sub_data';
    protected $fillable = [
        'price',
        'parameter_value_id',
        'client_pay_installment_id',
        //'payment_type_id'
    ];

    public function invoiceDetails()
    {
        return $this->morphMany(InvoiceDetail::class, 'invoiceable');
    }

    public function parameterValue(){
        return $this->belongsTo(ParameterValue::class);
    }
}
