<?php 

namespace App\Http\Helpers;

interface ApiConnector
{
    public function startSession();
    public function resetSession();
    public function closeSession();
    public function index($start, $end);
    public function show();
    public function validateIndexParams($start, $end);
}