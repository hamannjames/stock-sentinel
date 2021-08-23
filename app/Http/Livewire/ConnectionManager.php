<?php

namespace App\Http\Livewire;

use Livewire\Component;

// this class is not yet used, but I am going to use it to manage a list of connections in the dashboard
class ConnectionManager extends Component
{
    public $transactorConnections;
    public $tickerConnections;

    public function render()
    {
        return view('livewire.connection-manager');
    }
}
