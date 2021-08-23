<?php 

namespace App\Http\Helpers\Connectors;

interface ApiConnector
{
    public function startSession();
    public function resetSession();
    public function closeSession();
    public function index($params);
    public function show($id);
    public function validateIndexParams($params);
}