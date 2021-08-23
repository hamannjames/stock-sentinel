<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class AmountMax extends \App\Models\Filters\ModelFilter
{
    // return models where transaction amount max is less than or equal to value
    public function applyFilter(Collection $models, $filter)
    {
        return $models->where('transaction_amount_max', '<=', $filter);
    }
}