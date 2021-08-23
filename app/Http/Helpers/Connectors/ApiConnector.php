<?php 

namespace App\Http\Helpers\Connectors;

// This interface is a contract for all api connectors
interface ApiConnector
{
    public function startSession();
    public function resetSession();
    public function closeSession();
    public function index($params);
    public function show($id);
    public function validateIndexParams($params);
}