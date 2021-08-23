<?php

namespace App\Http\Livewire;

use Livewire\Component;

// this class is simple and seems unnecessary, but it ensures that each component fetches fresh data
// this will be more clear in the view template for it
class SenatorIndexSingle extends Component
{
    public $senator;

    public function render()
    {
        return view('livewire.senator-index-single');
    }
}
