<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class Gender extends \App\Models\Filters\ModelFilter
{
    // return models where gender equals filter (currently unused)
    public function applyFilter(Collection $models, $filter)
    {
        return $models->where('transactor.gender', '=', $filter);
    }
}