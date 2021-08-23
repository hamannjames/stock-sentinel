<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class Transactor extends \App\Models\Filters\ModelFilter
{
    // return models that are in specific transactor id array
    public function applyFilter(Collection $models, $filter)
    {
        return $models->whereIn('transactor_id', $filter);
    }
}