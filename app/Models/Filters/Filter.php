<?php

namespace App\Models\Filters;

use Closure;
use Illuminate\Database\Eloquent\Collection;

// my interface for model filters
interface Filter
{
  public function handle($data, Closure $next);
  public function applyFilter(Collection $models, $filter);
}