<?php

namespace App\Models\Filters;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

// this abstracy class contains most of the code for these filters. These filters are used
// exclusively by the stock transaction timeline at this time, in the pipeline
// the Pipeline class expects a handle method
abstract class ModelFilter implements Filter
{
  public function handle($state, Closure $next)
  {
    $filterName = $this->filterName();
    
    // if the state passed in does not have filter name or a value, simply move on
    if ( !$state->has($filterName) || empty($state->get($filterName)) || !$state->get($filterName) ) {
        // next is the magic pipeline closure that passes state off to the next part of pipeline
        return $next($state);
    }

    // get filter value from state and call apply filter on it. The underlying query class will
    // handle data sanitization (escaping characters, etcetera)
    $filter = $state->get($filterName);
    $state['models'] = $this->applyFilter($state->get('models'), $filter);
    
    // move on
    return $next($state);
  }

  public abstract function applyFilter(Collection $models, $filter);

  protected function filterName()
  {
      // I get the snaked name of the class which should mapto a filter in state
    return Str::snake(class_basename($this));
  }
}