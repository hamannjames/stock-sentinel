<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DateRange extends \App\Models\Filters\ModelFilter
{
    // return models that fall into date range
    public function applyFilter(Collection $models, $filter)
    {
        return $models->whereBetween('transaction_date', [$filter['startDate'], $filter['endDate']]);
    }
}