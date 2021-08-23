<?php

namespace App\Http\Controllers;

use App\Models\Transactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Helpers\Connectors\ProPublicaConnector;

class ProPublicaController extends Controller
{
    public function show(Transactor $transactor)
    {
        // this class and function are used on individual senator pages to pull a bit more data
        // about them from pro publica. It is handles via an api call so it happens after page load
        $connector = App::make(ProPublicaConnector::class);
        $transactor = $connector->show($transactor->pro_publica_id);
        return $transactor;
    }
}
