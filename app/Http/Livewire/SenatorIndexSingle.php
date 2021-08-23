<?php

namespace App\Http\Livewire;

use Livewire\Component;

class SenatorIndexSingle extends Component
{
    public $senator;

    public function render()
    {
        return view('livewire.senator-index-single');
    }
}
