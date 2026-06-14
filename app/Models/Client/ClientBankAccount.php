<?php

namespace App\Models\Client;

use App\Models\Parameter\ParameterValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientBankAccount extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'client_id',
        'bank_id',
        'iban',
        'abi',
        'cab',
        'is_main',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(ParameterValue::class, 'bank_id');
    }
}
