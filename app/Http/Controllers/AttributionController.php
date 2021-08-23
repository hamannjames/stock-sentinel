<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttributionController extends Controller
{
    public function __invoke()
    {
        return view('attributions');
    }
}
