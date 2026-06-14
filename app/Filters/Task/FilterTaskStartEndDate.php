<?php

namespace App\Filters\Task;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FilterTaskStartEndDate implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        // // Split value into start and end dates
        // $dates = $value;
        // $startDate = $dates[0] ?? null;
        // $endDate = $dates[1] ?? null;

        // return $query
        //     ->when(!empty($startDate) && !empty($endDate), function ($query) use ($startDate, $endDate) {
        //         $query->whereBetween('start_date', [$startDate, $endDate])
        //             ->whereBetween('end_date', [$startDate, $endDate]);
        //     })
        //     ->when(!empty($startDate) && empty($endDate), function ($query) use ($startDate) {
        //         $query->where('start_date', '>=', $startDate);
        //     })
        //     ->when(!empty($endDate) && empty($startDate), function ($query) use ($endDate) {
        //         $query->where('end_date', '<=', $endDate);
        //     });

        if ($property === 'startDate') {
            return $query->where('start_date', '>=', $value);
        }

        if ($property === 'endDate') {
            return $query->where('end_date', '<=', $value);
        }

        return $query;
    }
}
