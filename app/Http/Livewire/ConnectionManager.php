<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ConnectionManager extends Component
{
    public $transactorConnections;
    public $tickerConnections;

    public function render()
    {
        return view('livewire.connection-manager');
    }
}
