<?php

namespace App\Models\Client;

use App\Models\Parameter\ParameterValue;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientContact extends Model
{
    use HasFactory, SoftDeletes, CreatedUpdatedBy;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'prefix',
        'note',
        'client_id',
        'parameter_id',
        'parameter_value_id',
        'cf'
    ];

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ParameterValue::class, 'parameter_value_id');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name != '' && $this->last_name != '' ? $this->first_name . ' ' . $this->last_name : ($this->first_name? $this->first_name : $this->last_name);
    }

}
