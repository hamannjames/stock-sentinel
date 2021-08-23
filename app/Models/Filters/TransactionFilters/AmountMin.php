<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class AmountMin extends \App\Models\Filters\ModelFilter
{
    // return models where transaction amount is more than or equal to value
    public function applyFilter(Collection $models, $filter)
    {
        return $models->where('transaction_amount_min', '>=', $filter);
    }
}