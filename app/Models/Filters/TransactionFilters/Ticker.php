<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class Ticker extends \App\Models\Filters\ModelFilter
{
    // return models that are in specific ticker id array
    public function applyFilter(Collection $models, $filter)
    {
        return $models->whereIn('ticker_id', $filter);
    }
}