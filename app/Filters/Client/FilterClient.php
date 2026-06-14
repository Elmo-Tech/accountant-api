<?php

namespace App\Filters\Client;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FilterClient implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        return $query->where(function ($query) use ($value) {
            $query->where('cf', 'like', '%' . $value . '%')
                ->orWhere('iva', 'like', '%' . $value . '%');
        });
    }
}
