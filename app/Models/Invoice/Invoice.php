<?php

namespace App\Models\Invoice;

use App\Models\Client\Client;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;//, CreatedUpdatedBy;

    protected $fillable = [
        'client_id',
        'end_at',
        'start_date',
        'payment_type_id',
        'discount_type',
        'discount_amount',
        'bank_account_id',
    ];

    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $model->number = 'IN_' . str_pad($model->id, 5, '0', STR_PAD_LEFT);
            $model->save();
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoiceDetails(): HasMany
    {
        return $this->hasMany(InvoiceDetail::class);
    }

}
