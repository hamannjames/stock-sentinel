<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class InOffice extends \App\Models\Filters\ModelFilter
{
    // return models where the transactor is in office
    public function applyFilter(Collection $models, $filter)
    {
        return $models->where('transactor.in_office', '=', true);
    }
}