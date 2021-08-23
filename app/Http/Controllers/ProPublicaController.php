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
        $connector = App::make(ProPublicaConnector::class);
        $transactor = $connector->show($transactor->pro_publica_id);
        return $transactor;
    }
}
