<?php

namespace App\Models\Filters\TransactionFilters;

use Illuminate\Database\Eloquent\Collection;

class Owner extends \App\Models\Filters\ModelFilter
{
    public function applyFilter(Collection $models, $filter)
    {
        return $models->when($filter === 'self',
                fn ($models) => $models->whereIn('transaction_owner', ['self', '--'])
            )
            ->when($filter !== 'self',
                fn($models) => $models->where('transaction_owner', '=', $filter)
            );
    }
}