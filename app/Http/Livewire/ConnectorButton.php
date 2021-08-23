<?php

namespace App\Http\Livewire;

use Livewire\Component;

// this simple class handles creating connections between the current user and the model passed into it
class ConnectorButton extends Component
{
    public $model;
    public $connected;
    
    public function connect()
    {
        // redirect if not logged in
        if (auth()->guest()) {
            return redirect('login');
        }

        // call add connection on the user model and dispatch a browser event if successfull
        auth()->user()->addConnection($this->model);
        $this->connected = true;
        $this->dispatchBrowserEvent('user-connected');
    }

    public function disconnect()
    {
        if (auth()->guest()) {
            return redirect('login');
        }

        // call remove connection on the user model and dispatch a browser event if successfull
        auth()->user()->removeConnection($this->model);
        $this->connected = false;
        $this->dispatchBrowserEvent('user-disconnected');
    }

    public function render()
    {
        return view('livewire.connector-button');
    }
}
