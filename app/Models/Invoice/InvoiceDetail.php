<?php

namespace App\Models\Invoice;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceDetail extends Model
{
    use HasFactory, SoftDeletes; //CreatedUpdatedBy;

    protected $fillable = [
        'invoice_id',
        'invoiceable_id',
        'invoiceable_type',
        'price',
        'price_after_discount',
        'extra_price',
        'description'
    ];
}
