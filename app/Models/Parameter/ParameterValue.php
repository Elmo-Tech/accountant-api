<?php

namespace App\Models\Parameter;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ParameterValue extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CreatedUpdatedBy;
    protected $fillable = [
        'parameter_id',
        'parameter_value',
        'description',
        'parameter_order',
        'is_default',
        'code',
        'description2',
        'description3'
    ];


    public function scopeParameterOrder($query, $paraOrder)
    {
        return $query->where('parameter_id', $paraOrder);
    }

}
