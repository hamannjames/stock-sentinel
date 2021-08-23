<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ConnectorButton extends Component
{
    public $model;
    public $connected;
    
    public function connect()
    {
        if (auth()->guest()) {
            return redirect('login');
        }

        auth()->user()->addConnection($this->model);
        $this->connected = true;
        $this->dispatchBrowserEvent('user-connected');
    }

    public function disconnect()
    {
        if (auth()->guest()) {
            return redirect('login');
        }

        auth()->user()->removeConnection($this->model);
        $this->connected = false;
        $this->dispatchBrowserEvent('user-disconnected');
    }

    public function render()
    {
        return view('livewire.connector-button');
    }
}
