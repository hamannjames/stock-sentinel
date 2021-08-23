<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class Party extends \App\Models\Filters\ModelFilter
{
    public function applyFilter(Collection $models, $filter)
    {
        return $models->where('transactor.party.symbol', '=', $filter);
    }
}