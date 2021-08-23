<?php

namespace App\View\Components;

use Illuminate\View\Component;

// this is a layout component for the front end of the app. Data can be shared here with any view
// that is used on the front end
class FrontendLayout extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('layouts.frontend');
    }
}
