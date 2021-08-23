<?php

namespace App\Models\Filters;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

abstract class ModelFilter implements Filter
{
  public function handle($state, Closure $next)
  {
    $filterName = $this->filterName();
    
    if ( !$state->has($filterName) || empty($state->get($filterName)) || !$state->get($filterName) ) {
        return $next($state);
    }

    $filter = $state->get($filterName);
    $state['models'] = $this->applyFilter($state->get('models'), $filter);
    
    return $next($state);
  }

  public abstract function applyFilter(Collection $models, $filter);

  protected function filterName()
  {
    return Str::snake(class_basename($this));
  }
}