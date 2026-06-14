<?php

namespace App\Models\Client;

use App\Models\Parameter\ParameterValue;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientAddress extends Model
{
    use HasFactory, SoftDeletes, CreatedUpdatedBy;
    protected $fillable = [
        'address',
        'province',
        'cap',
        'city',
        'region',
        'latitude',
        'longitude',
        'note',
        'parameter_value_id',
        'client_id',
    ];

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ParameterValue::class, 'parameter_value_id');
    }


}
